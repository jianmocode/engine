<?php

namespace Xpmse;

require_once( __DIR__ . '/Mem.php');
require_once( __DIR__ . '/Log.php');

use \Exception as Exception;
use \Xpmse\Conf;
use \Xpmse\Log;
use \Xpmse\Utils;
use \Mina\Cache\Redis as Cache;

/**
 * 任务队列
 * 
 * @server : 
 * 
 *   $config =[
 *      "host" => "127.0.0.1",
 *      "port" => "7749",
 *      "worker_num" => 1,
 *      "user" => 0
 *   ];
 * 
 *   $job = new Job(["name" => "Behavior"]);
 *   $job->server($config);
 * 
 * 
 * @client :
 * 
 *   $slug = "helloworld";
 *   $job = new Job(["name"=>"Behavior"]);
 *   if ( $job->isRunning($slug) ) {
 *       // job is running 
 *   }
 *   $job_id = $job->call( $slug, "SomeModel", "SomeMethod", $args... ); * 
 */
class Job  {

    public  $options =[];
    private $cache = null;
    private $server = [];

    // 日志处理器
    private $log = null;

    function forUnitTest(  $file, $sleep =0, $created_at =null ) {
        if ( $sleep > 0 ) {
            sleep($sleep);
        }

        $response = [
            "created_at" => $created_at,
            "run_at"=> date("Y-m-d H:i:s"),
            "microtime" => microtime()
        ];

        $text = json_encode($response);
        file_put_contents( $file, $text );
    }

    // Job Struct 
    // {
    //     "<job-slug>":{
    //         "status": "<waiting|pending|finished|failed|killed|notfound|lost>",
    //         "taskid": "<Taskid>",
    //         "progress": <0-100>,
    //         "message": "<task created>",
    //         "method" : "<task|taskwait>",
    //         "data":"<json string>",
    //         "created_at": "<Unix Timestamp>",
    //         "updated_at": "<Unix Timestamp>"
    //     }
    //     "__server": {
    //          "started_at": "<Unix Timestamp>",
    //          "worker_num": <number>,
    //          "host": "<hosting ip>",
    //          "port": "<hosting port>",
    //          "timeout": 0.5,
    //          "pids":{}
    //     }
    //     ...
    // }
    function __construct( $options  = [] ) {

        $name_bak = $options["name"];
        $this->options = $options;
        $this->options["name"] = trim(!empty($this->options["name"]) ? $this->options["name"] : Utils::genStr(6));
        $this->cache = new Cache( [
            "prefix" => "_system:jobs:{$this->options['name']}:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

        $server = $this->cache->getJSON('__server');
        if ( is_array($server) ) {
            $this->server = $server;
        }

        $logpath = Conf::G('log/server/file');
        if ( !empty($logpath) ) {
            $logpath = dirname($logpath);
        } else {
            $logpath = "/data/stor/run";
        }

        $name = "Job.{$this->options['name']}";
        $logfile = "{$logpath}/{$name}.log";
        if ( !empty($name_bak) && !file_exists($logfile) ) {
            (new Log("Exception"))->info("创建日志文件($name_bak)", ["file"=>$logfile]);
            @touch($logfile);
        }

        if ( !file_exists($logfile) ) {
            if ( !empty($name_bak) ) {
                (new Log("Exception"))->error("无法创建日志文件({$name_bak})", ["file"=>$logfile]);
            }
            $logfile = null;
        }
        
        $this->log = new Log($name, $logfile);
    }

    /**
     * 返回日志文件路径
     * @return 成功返回文件路径， 失败返回 false
     */
    function getLogPath() {
        $conf = $this->log->getConfig();
        if ( \file_exists($conf["file"]) ){
            return $conf["file"];
        }
        return false;
    }

    /**
     * 读取日志最后行数
     * @param int $max 最多返回行数
     * @return string 日志内容
     */
    function tailLog( $max = 500 ) {
        $file = $this->getLogPath();
        if ( $file === false ){
            return null;
        }
        exec("tail -n {$max} {$file}", $response );
        return implode("\n",$response);
    }


    /**
     * 发起请求前，更新数据
     * @param array $args 参数表
     * @param array $options 配置选项
     *                  int after $options["after"]毫秒后触发
     *                  int tick  每间隔$options["tick"]毫秒触发 
     * 
     * @return
     */
    function callPrepare( $args, $options=[] ) {

        $slug = $args[0];
        if ( $this->isRunning($slug) ) {
            throw new Excp("Job正在执行($slug)", 500, ["args"=>$args]);
        }

        $options = array_merge([
            "status"=> "waiting",
            "taskid"=>null,
            "progress"=>0,
            "message"=> "task created",
            "method" => "task",
            "data"=>$args,
            "created_at"=>time(),
            "updated_at"=>time()
        ],$options);

        $this->put($slug, $options);
    }


    /**
     * 后台调用数据模型
     * 
     * @param string $slug 任务唯一别名
     * @param string $model 调用 Model 名称
     * @param string $method 调用 Model 方法
     * @param mix ...$args  模型参数表
     * 
     * @return string $task_id 任务ID
     */
    function call(string $slug, string $model, string $method ) {
        $args = func_get_args();
        $this->callPrepare( $args );
        return $this->send($slug);
    }
    
   

    /**
     * ( 在 $ms 毫秒后 ) 后台调用数据模型
     * 
     * @param string $ms 延迟运行时间, 单位毫秒
     * @param string $slug 任务唯一别名
     * @param string $model 调用 Model 名称
     * @param string $method 调用 Model 方法
     * @param mix ...$args  模型参数表
     * 
     * @return string $task_id 任务ID
     */
    function callAfter( int $ms, string $slug, string $model, string $method ) {

        if ( $ms < 0 ) {
            throw new Excp("延迟运行时间应该大于0(ms={$ms}毫秒)", 500, ["ms"=>$ms]);
        }

        
        $args = func_get_args();
        $args = array_slice( $args, 1 , func_num_args() );
        $this->callPrepare( $args, ["after"=>$ms] );
        return $this->send($slug, ["after"=>$ms] );
    }


    /**
     * ( 每间隔 $ms 毫秒 ) 后台调用数据模型
     * 
     * @param string $ms   间隔时间, 单位毫秒
     * @param string $slug 任务唯一别名
     * @param string $model 调用 Model 名称
     * @param string $method 调用 Model 方法
     * @param mix ...$args  模型参数表
     * 
     * @return string $task_id 任务ID
     */
    function callTick( int $ms, string $slug, string $model, string $method ) {

        if ( $ms > 30000 ) {
            throw new Excp("间隔运行时间应该大于30000(ms={$ms}毫秒)", 500, ["ms"=>$ms]);
        }

        $args = func_get_args();
        $args = array_slice( $args, 1 , func_num_args() );
        $this->callPrepare( $args, ["tick"=>$ms] );
        return $this->send($slug, ["tick"=>$ms] );
    }


    /**
     *  ( 到达指定时刻运行 ) 后台调用数据模型
     * 
     * @param string $time 到达时刻, 时间描述字符串 @see strtotime()
     * @param string $slug 任务唯一别名
     * @param string $model 调用 Model 名称
     * @param string $method 调用 Model 方法
     * @param mix ...$args  模型参数表
     * 
     * @return string $task_id 任务ID
     */
    function callAt(string $time, string $slug, string $model, string $method ) {

        $now = time();
        $sec = strtotime($time);
        $ms = ($sec - $now) * 1000;
        if ( $ms < 0 ) {
            throw new Excp("延迟运行时间应该大于0(ms={$ms}毫秒 time={$time} now={".date("Y-m-d H:i:s")."})", 500, ["time"=>$time, "ms"=>$ms]);
        }

        $args = func_get_args();
        $this->callAfter( ...$args );
    }



    /**
     * 向队列服务器发送调用请求
     * @param string $slug 服务别名
     * @param array $options 配置选项
     *                  int after $options["after"]毫秒后触发
     *                  int tick  每间隔$options["tick"]毫秒触发
     * 
     * @return string $response 服务端返回数据
     */
    function send( string $slug, $options=[] ) {

        if (empty($this->server)) {
            throw new Excp("{$this->options['name']} JOB Server 未启动", 501, ["slug"=>$slug, "options"=>$this->options]);
        }

        $task_id = null;
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if (!@$client->connect($this->server["host"], $this->server["port"], $this->server["timeout"]) ){
            if ( $slug !== "__inspect"){
                $this->update($slug, ["status"=>"failed", "message"=>"cant't connect job server"]);
            }
            throw new Excp("向Job Server发送请求失败($slug)", 501, $this->server);
        }

        $options = array_merge( $this->options, $options );
        $client->send(json_encode(["slug"=>$slug, "options"=>$options]));
        $response = $client->recv();
        $client->close();

        return $response;
    }

  
    /**
     * 更新任务数据(合并已存在数据)
     * @param string $slug 任务唯一别名
     * @param array $data 任务数据
     * @return bool 成功返回 true, 失败抛出异常
     */
    function update( string $slug, array $data ) {
        $job = $this->get($slug);
        $job = array_merge($job, $data);
        $job["updated_at"] = time();
        // echo "===== update $slug ===========\n";
        // Utils::out( $job );
        return $this->put( $slug,  $job );
    }


    /**
     * 设置任务数据 (覆盖已存在数据)
     * @param string $slug 任务唯一别名
     * @param array $data 任务数据
     * @return bool 成功返回 true, 失败抛出异常
     */
    function put( string $slug, array $data ) {
        $err = $this->cache->setJSON($slug, $data);
        if ( $err === false ) {
            throw new Excp("设置任务失败", 500, ["slug"=>$slug, "data"=>$data]);
        }
        return true;
    }


    /**
     * 读取任务数据
     * @param string $slug 任务唯一别名
     * @return array $data 任务数据 ( 失败抛出异常 )
     */
    function get( string $slug ) {
        $job = $this->cache->getJSON($slug);
        if ( $job === false ) {
            throw new Excp("读取任务失败", 500, ["slug"=>$slug]);
        }

        return $job;
    }

    /**
     * 检查任务是否正在运行
     * @param string $slug 任务唯一别名
     * @return bool 正在运行返回 true, 没有运行返回 false
     */
    function isRunning( string $slug ) {
        $status = $this->status( $slug );
        $runningStatus = ["waiting","pending"];
        if ( in_array($status, $runningStatus) ) {
            return true;
        }
        return false;
    }

    /**
     * 检查任务状态
     * @param string $slug 任务唯一别名
     * @return string $status       任务状态字符串
     *                “notfound”    任务不存在
     *                “lost”        程序意外终止, 任务退出
     *                “waiting”     任务排队等待运行
     *                “pending”     任务正在运行
     *                ”failed“      任务执行失败
     *                "killed"      因超时等原因，任务被强制结束
     *                "finished"    任务运行完毕
     */
    function status( string $slug ){
        
        if (empty($this->server)) {
            return "notfound";
        }
        
        $job = $this->cache->getJSON($slug);
        if ( !is_array($job) ) {
            return "notfound";
        }

        $started = $this->server["started_at"];
        $updated = $job["updated_at"];
        // 程序意外终止或重启进程或未作修复处理
        if ( $started > $updated ) {
            return "lost";
        }

        return $job["status"];
    }


    /**
     * 清除所有任务
     */
    function clean() {
        
        $keys = $this->cache->keys();
        foreach( $keys as $slug ) {
            if ( strpos($slug, "__server") !== false) {
                continue;
            }
            $status = $this->status($slug);
            // 清理数据
            if ( $status == "lost" ) {
                $this->cache->delete( $slug );
            }
        }
    }
    

    /**
     * 关闭服务器
     * @return bool 成功返回true, 失败返回false
     */
    function shutdown() {
        $c = $this->get( "__server" );
        $master_pid = intval($c["master_pid"]);
        if ( $master_pid > 0 ) {

            // 检查PID是否存在


            // @see http://php.net/manual/en/pcntl.constants.php
            return posix_kill($master_pid, SIGTERM) ;
            if ( $response === true ) {
                $this->update("__server", ["master_pid"=>0, "manager_pid"=>0, "stopped_at" => time()]);
            }

            return $response;
        }
        return false;
    }


    /**
     * 平滑重启服务器
     * @param bool $worker_only  是否仅重启Task进程
     * @return bool 成功返回true, 失败返回false
     */
    function reload( $worker_only = false ) {

        $c = $this->get( "__server" );
        $master_pid = intval($c["master_pid"]);
        $response = false;
        if ( $master_pid > 0 ) {
            // @see http://php.net/manual/en/pcntl.constants.php
            if ( $worker_only ) {
                $response = posix_kill($master_pid, SIGUSR2) ;
            } else {
                $response =  posix_kill($master_pid, SIGUSR1) ;
            }
        }

        $workers = !$worker_only ? 'all workers': 'task workers';

        if ( $response ) {
            $this->log->info( "{$this->options['name']} Server Reload Success ({$workers})", ["master_pid"=>$master_pid] );
        } else {
            $this->log->info( "{$this->options['name']} Server Reload Failure ({$workers})", ["master_pid"=>$master_pid] );
        }
        
        return $response;
    }



    /**
     * 重启服务器
     */
    function restart() {

        $inspect = $this->inspect();
        $c = $inspect["setting"];
        if ( !$c["daemonize"] ) {
            throw new Excp("服务不是以Daemon方式启动", 402,["setting"=>$setting]);
        }

        $this->shutdown();
        usleep(200000); // 等待200毫秒
        return $this->start( $c );
    }


    /**
     * 检查服务详情
     */
    function inspect() {

        try {
            $c = $this->get( "__server" );
            $master_pid = intval($c["master_pid"]);
        }  catch( Excp $e ) { 
            $master_pid = 0;
        }


        if( $master_pid == 0){
            return [
                "status"=>"shutdown",
                "setting" => $c,
            ];
        }

        // 查询服务器信息
        try {
            $response = $this->send("__inspect");
        } catch( Excp $e ) {
            
            // 服务无法连接 
            if ( $e->getCode() == 501 ) {
                return [
                    "status"=>"hangup",
                    "setting" => $c,
                ];
            }
        }
        $data = json_decode( $response, true );
        if ($data === false ) {
            Utils::json_decode( $response );
            return;
        }

        $data["status"] = "running";
        return $data;
    }


    /**
     * 启动服务器
     */
    function start( $c ) {
    
        $setting = json_encode( $c );
        $options =  json_encode( $this->options );
        $xpm = AROOT . "/bin/xpm.phar server -t queue  -c '''{$setting}''' -o '''{$options}''' " ;
        exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $xpm, "/dev/null", "/dev/stdout"), $response);
        usleep(500000); // 等待500毫秒
        return $this->get( "__server" );
    }


    /**
     * 输出信息到日志
     */
    function info( $message, $extra=[] ) {
        if ( !is_array($extra) ) {
            $extra = [$extra];
        }
        $this->log->info( $message, $extra );
    }

    /**
     * 输出错误信息到日志
     */
    function error( $message, $extra=[] ) {
        if ( !is_array($extra) ) {
            $extra = [$extra];
        }

        $this->log->info($message, $extra );
    }
   
    /**
     * 启动队列服务器
     *  $c = [
     *      "host" => <主机, 默认 127.0.0.1>
     *      "port" => <端口, 默认 9527>
     *      "home" => <模拟请求来源 例如: https://www.jianmo.ink>,
     *      "user" => <模拟管理用户, 默认第一个管理员>,
     *      "name" => <服务名称>,
     *      "worker_num" => <Worker 数量默认1个>,
     *      "timeout" => <连接超时, 默认0.5s>
     *  ];
     */
    function server( $c ) {

        // 检查服务器是否运行
        $inspect = $this->inspect();
        if ( $inspect["status"] === "running" ) {
            throw new Excp("服务器已经启用(master_pid={$inspect['setting']['master_pid']}, manager_pid={$inspect['setting']['manager_pid']})", 402, ["stats"=>$inspect["stats"]] );
        }

        // 强行关闭
        if ( $inspect["status"] === "hangup" ) { 
            $this->shutdown();
        }

        // Ctrl + c 调用 shutdown
        $options = $this->options;
        if ( !$c["daemonize"] ) {
            \Swoole\Process::signal(SIGINT, function ($signal) use($options) { 
                $job = new \Xpmse\Job($options);
                $job->shutdown();
            }); 
        }
        
        $this->put( "__server", [
            "started_at" => time(),
            "worker_num" => $c["worker_num"],
            "host" => $c["host"],
            "port" => $c["port"],
            "home" => $c["home"],
            "daemonize" => $c["daemonize"],
            "autoport" => isset($c["autoport"]) ? $c["autoport"] : ( $c["port"] == 0 ),
            "timeout" => !empty($c["timeout"]) ? $c["timeout"] : 0.5,
        ]);

        $this->clean();

        // SET ENV
        if ( !empty($c["home"]) ) {
            $uri = parse_url($c["home"]);
            $_SERVER['HTTP_HOST'] = $uri["host"];
            if ($uri["port"] != 80 && $uri["port"] != 443 && !empty($uri["port"]) ) {
                $_SERVER['HTTP_HOST'] =  $_SERVER['HTTP_HOST'] . ":" . $uri["port"];
            }
            $https = "off";
            if ( $uri["scheme"] == "https") {
                $https = true;
            }

            $_SERVER['HTTPS'] = $https;
            $_SERVER["REQUEST_URI"] = "/__daemon/job";
        }

        $server = new \swoole_server($c["host"], $c["port"]);

        // 服务启动时回调
        $server->on('start', function ($server){
            $runtime = [
                "master_pid" =>  $server->master_pid,
                "manager_pid" => $server->manager_pid,
                "port" => $server->ports[0]->port
            ];
            $this->update("__server", $runtime);
            $setting = $server->setting;
            $setting["home"] = Utils::getHome();
            $this->log->info( "{$this->options['name']} Server Started ", array_merge($setting, $runtime) );
        });

        // 服务关闭时回调
        $server->on('shutdown', function($server){
            $runtime = [
                "master_pid" =>  $server->master_pid,
                "manager_pid" => $server->manager_pid,
            ];
            $this->update("__server", ["master_pid"=>0, "manager_pid"=>0, "stopped_at" => time()]);
            $this->log->info( "{$this->options['name']} Server Shutdown", $runtime );
        });

        // 接收到数据时回调
        $server->on('receive', function($server, $fd, $reactor_id, $data_string) {

            $data = json_decode($data_string, true);
            if ( $data === false ) {
                throw Excp("读取任务信息失败 @line: " . __LINE__, 500, ["data"=>$data]);
            }
            

            // 检查服务详情
            if ( $data["slug"] == "__inspect" ) {

                $response = [
                    "stats" => $server->stats(),
                    "setting" => $server->setting,
                    "port" => $server->ports[0]->port,
                ];

                $response["setting"]["master_pid"] = $server->master_pid;
                $response["setting"]["manager_pid"] = $server->manager_pid;

                $server->send($fd, json_encode($response) ); // 返回服务详情
                return;
            }

            $task_id = 0;

            // after 
            $options = is_array($data["options"]) ? $data["options"] : [];
            if ( intval($options["after"]) > 0 ) {
                swoole_timer_after(  intval($options["after"]), function() use( $server, $data ) {
                    $server->task($data);
                });
            } else if ( intval($options["tick"]) > 0 ) {
                swoole_timer_tick(  intval($options["tick"]), function( $timer_id ) use( $server, $data ) {
                    $data["timer_id"] = $timer_id;
                    $server->task($data);
                });
            } else {
                $task_id = $server->task($data);
            }

            $server->send($fd, $task_id); // 返回任务ID
        });


        // 运行投投递任务
        $server->on('task', function ($server, $task_id, $reactor_id, $data ) {
            
            $slug = $data["slug"];
            $options = $data["options"];
            $timer_id = $data["timer_id"];
            $self = new Job( $options );
            $this->log->info( "Start #{$slug}");

            try { 
                $job = $self->get($slug);
            }catch( Excp $e) {
                $e->log();
                $self->update($slug, ["status"=>"failed", "message"=>$e->getMessage()]);
                $server->finish($slug);
                return;
            }

            // pendding & 记录 Timmer id
            $self->update($slug, ["status"=>"pending", "timer_id"=>$timer_id]);

            $class = $job["data"][1];
            $method = $job["data"][2];
            if ( empty($class) || empty($method) ) {
                $self->update($slug, ["status"=>"failed", "message"=>"class or method is requied"]);
                $server->finish($slug);
                return;
            }

            $args = [];
            if ( count($job["data"]) > 3 ) {
                $args = array_slice( $job["data"], 3, count($job["data"]) );
            }

            $this->log->info("Exec {$class}::{$method} #{$slug}");

            try {
                $inst = new $class;
            } catch( Excp $e) {
                $e->log();
                $self->update($slug, ["status"=>"failed", "message"=>$e->getMessage()]);
                $server->finish($slug);
                return;
            } catch( Exception $e) {
                $self->update($slug, ["status"=>"failed", "message"=>$e->getMessage()]);
                $server->finish($slug);
                return;
            }
            try {
                $resp = $inst->$method(...$args);
            } catch( Excp $e) {
                $e->log();
                $self->update($slug, ["status"=>"failed", "message"=>$e->getMessage()]);
                $server->finish($slug);
                return;
            }
            $self->update($slug, ["status"=>"finished"]);
            $server->finish($slug);
        });

        // 投递任务完成
        $server->on('finish', function ($server, $task_id, $slug) {
            $this->log->info("Finished #{$slug}");
            $data = json_decode($data_string, true);
            if ( $data === false ) {
                throw Excp("读取任务信息失败 @line: " . __LINE__, 500, ["data"=>$data]);
            }
            $slug = $data["slug"];
            $options = $data["options"];
            $self = new Job( $options );
            if ( $slug ) {
                if ( $self->isRunning($slug) ) {
                    $self->update($slug, ["status"=>"killed", "message"=>"worker timeout"]);
                }
            }
        });

        // 设定参数
        $setting = $c; 
        unset( $setting["user"] );
        $setting["task_worker_num"] = $c["worker_num"];
        $server->set($setting);

        // 启动服务
        $server->start();

    }
}

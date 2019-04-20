<?php

namespace Xpmse;

require_once( __DIR__ . '/Mem.php');
require_once( __DIR__ . '/Log.php');

use \Exception as Exception;
use \Xpmse\Conf;
use \Xpmse\Log;
use \Xpmse\Utils;
use \Mina\Cache\Redis as Cache;
use co;

/**
 * WEB Socket 服务器
 * 
 * @server : 
 * 
 *   $config =[
 *      "host" => "127.0.0.1",
 *      "port" => 7749,
 *      "home" => "https://www.jianmo.ink",
 *      "ssl_cert_file" => '/config/ssl.crt',
 *      "ssl_key_file"  => '/config/key.crt',
 *      "user" => 0
 *   ];
 * 
 *   $ws = new Websocket(["name"=>"message"]);
 *   $ws->server($config);
 * 
 * @client :
 * 
 * 
 */
class Websocket  {

    public  $options =[];
    private $cache = null;
    private $server = [];

    // 日志处理器
    private $log = null;

    // websocket Struct 
    // {
    //     ":client_id":{
    //          "client_id": "<connection id>",
    //          "header": "<request header>",
    //          "cookie": "<request cookie>",
    //          "get" : "<query params>",
    //          "request_time": "<request_time>"
    //     }
    //     "__server": {
    //          "started_at": "<Unix Timestamp>",
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
            "prefix" => "_system:websocket:{$this->options['name']}:",
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

        $name = "Websocket.{$this->options['name']}";
        $logfile = "{$logpath}/{$name}.log";
        if ( !empty($name_bak) && !file_exists($logfile) ) {
            (new Log("Exception"))->info("创建日志文件($name_bak)", ["file"=>$logfile]);
            @touch($logfile);
        }

        if ( !file_exists($logfile) ) {
            (new Log("Exception"))->error("无法创建单独的日志文件($name)", ["file"=>$logfile]);
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
     * 更新WebSocket服务器(合并已存在数据)
     * @param string $slug 连接唯一别名
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
     * 设置WebSocket服务器数据 (覆盖已存在数据)
     * @param string $slug 连接唯一别名
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
     * 读取WebSocket服务器数据
     * @param string $slug 连接唯一别名
     * @return array $data 任务数据 ( 失败抛出异常 )
     */
    function get( string $slug ) {
        $job = $this->cache->getJSON($slug);
        if ( $job === false ) {
            throw new Excp("读取数据失败", 500, ["slug"=>$slug]);
        }

        return $job;
    }



    /**
     * 删除WebSocket服务器数据
     * @param string $slug 连接唯一别名
     * @return bool 成功返回 true, 失败抛出异常
     */
    function del( string $slug ) {
        return $this->cache->delete( $slug );
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
            // @see http://php.net/manual/en/pcntl.constants.php
            $response =  posix_kill($master_pid, SIGTERM) ;
            $this->update("__server", ["master_pid"=>0, "manager_pid"=>0, "stopped_at" => time()]);
            sleep(2); // 等待500毫秒
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
            $response =  posix_kill($master_pid, SIGUSR1) ;
        }

        $this->log->info( "{$this->options['name']} Server Reload Success", ["master_pid"=>$master_pid] );
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
        // try {
        //     $response = $this->send("__inspect");
        // } catch( Excp $e ) {
            
        //     // 服务无法连接 
        //     if ( $e->getCode() == 501 ) {
        //         return [
        //             "status"=>"hangup",
        //             "setting" => $c,
        //         ];
        //     }
        // }
        // $data = json_decode( $response, true );
        // if ($data === false ) {
        //     Utils::json_decode( $response );
        //     return;
        // }

        $data["port"] = $c["port"];
        $data["status"] = "running";
        $data["setting"] = $c;
        return $data;
    }



    /**
     * 启动服务器
     */
    function start( $c ) {
    
        $setting = json_encode( $c );
        $options =  json_encode( $this->options );
        $xpm = AROOT . "/bin/xpm.phar server -t websocket  -c '''{$setting}''' -o '''{$options}''' " ;
        exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $xpm, "/dev/null", "/dev/stdout"), $response);
        sleep(2); // 等待500毫秒

        $server =  $this->get( "__server" );
        if ( intval($server["master_pid"]) == 0 ) {
            $text = \implode("", $response);
            throw new Excp("启动失败 (response={$text})", 500, ["server"=>$server]);
        }
        
        return $server;
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

        // // 检查服务器是否运行
        // $inspect = $this->inspect();
        // if ( $inspect["status"] === "running" ) {
        //     // $this->shutdown();
        //     // sleep(1);
        //     // throw new Excp("服务器已经启用(master_pid={$inspect['setting']['master_pid']}, manager_pid={$inspect['setting']['manager_pid']})", 402, ["stats"=>$inspect["stats"]] );
        // }

        // // 强行关闭
        // if ( $inspect["status"] === "hangup" ) { 
        //     $this->shutdown();
        //     sleep(1);
        // }

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
            $_SERVER["REQUEST_URI"] = "/__daemon/websocket";
        }

        try {
            $server  = new \Swoole\WebSocket\Server($c["host"], $c["port"]);
        } catch ( \Swoole\Exception  $e ){
            throw new Excp( $e->getMessage(), $e->getCode(), ["setting"=>$c]);
        }
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

       


        // 客户端连接
        $server->on('open', function (\Swoole\WebSocket\Server $server, $request) {

            $connection = [
                "client_id" => $request->fd,
                "header" => $request->header,
                "cookie" => $request->cookie,
                "get" => $request->get,
                "request_time" => empty($request->server["request_time"]) ? time() :  $request->server["request_time"],
            ];

            // 保存客户端连接
            $this->put($request->fd, $connection);
            $this->info("OPEN. handshake success. client_id #{$request->fd}\n");
        });


        // 响应客户端请求
        $server->on('message', function (\Swoole\WebSocket\Server $server, $frame) {

            $json_text = $frame->data;
            $client_id = $frame->fd;
            $data = json_decode( $json_text, true);
            if ( $data === fasle ) {
                $server->push($client_id, json_encode(["code"=>401, "message"=>"非法请求数据", "extra"=>["json_text"=>$json_text] ]));
                return;
            }

            if ( empty($data["api"]) ) {
                $server->push($client_id, json_encode(["code"=>401, "message"=>"未指定API接口信息", "extra"=>["json_text"=>$json_text] ]));
                return;
            }

            if ( empty($data["method"]) ) {
                $server->push($client_id, json_encode(["code"=>401, "message"=>"未指定METHOD方法信息", "extra"=>["json_text"=>$json_text] ]));
                return;
            }

            $api = explode( "/", $data["api"]);
            $class = "\\{$api[1]}\\{$api[2]}\\api\\{$api[3]}";
            if  ( !class_exists($class) ) {
                $server->push($client_id, json_encode(["code"=>402, "message"=>"API接口不存在", "extra"=>["class"=>$class, "data"=>$data] ]));
                return;
            }

            $method = $data["method"];
            if( !method_exists($class, $method) ){
                $server->push($client_id, json_encode(["code"=>402, "message"=>"METHOD方法不存在", "extra"=>["class"=>$class, "method"=>$method,"data"=>$data] ]));
                return;
            }

            $request_id = md5($client_id .time());
            $server->push($frame->fd, json_encode(["code"=>0, "status"=>"pending", "process"=>0, "message"=>"提交成功", "request_id"=>$request_id]));

            // 运行API 
            // ====================================================================================================
            $ws = new Websocket($this->options);
            $args = !is_array($data["args"]) ? [] : $data["args"];
            $args_text = json_encode( $args );
            
            $connection = $this->get($client_id);
            if ( $connection !== false ) {
                $cookie = json_encode($connection["cookie"]);
                $header = json_encode($connection["header"]);
                $get = json_encode($connection["get"]);
            }

            $cmd = AROOT . "/bin/xpm.phar api -a '''{$class}''' -m '''{$method}'''  -q '''{$get}'''  -c '''{$cookie}'''  -h '''{$header}'''  -d '''{$args_text}''' ";
            go(function() use( $ws, $server, $client_id, $request_id, $class, $method, $cmd ) {
                $ws->info("PENDING. client_id #{$client_id} request_id={$request_id} ({$class}::{$method}) \n");
                $ws->info("EXEC: {$cmd}");
                $ret = Co::exec($cmd);
                $output = $ret["output"];
                $response = json_decode( $output, true );
                if ( $response === false ) {
                    $response = $output;
                }

                $server->push($client_id, json_encode(["code"=>0, "status"=>"done", "process"=>100, "data"=>$response, "message"=>"运行完毕", "request_id"=>$request_id]));
                $ws->info("DONE. client_id #{$client_id} request_id={$request_id}\n");
            });

        });
        
        // 客户端断开连接
        $server->on('close', function ($ser, $client_id) {
            $this->del($client_id);
            $this->info("CLOSE. client #{$client_id} closed\n");
        });
        
        // 设定参数
        $setting = $c; 
        unset( $setting["user"] );
        $server->set($setting);

        // 启动服务
        $server->start();

    }
}

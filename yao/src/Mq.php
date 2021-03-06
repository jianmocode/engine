<?php
/**
 * Class MQ
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use Swoole\Process;

/**
 * 轻量级消息队列
 */
class MQ {

    /**
     * 队列名称
     * 
     * @var string
     */
    private $name = "";


    /**
     * 配置参数
     * 
     * @var array
     */
    private $option = [];


    /**
     * 主进程PID
     * 
     * @var int
     */
    private $pid = 0;


    /**
     * Worker PID
     * 
     * @var array<int>
     */
    private $workers = [];


    /**
     * 创建一个消息队列
     */
    public function __construct(string $name, array $option=[]) {

        // 从缓存中读取配置
        if ( empty($option) ) {

            // 读取配置
            $option_cache = Redis::hget("mq:{$name}", "option");
            if( $option_cache != false) {
                $option = json_decode($option_cache, true);
            }
            if ( !is_array($option) ) {
                $option = [];
            }
        }

        // 设置默认值
        Arr::defaults($option, [
            "blocking" => false, // 是否等待 callback 方法运行完毕
            "log" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-mq-{$name}.log", 'debug']],
            "backup" => "/backup/yao-mq-{$name}.mq"
        ]);

        // 方法赋值
        $this->name = $name;
        $this->option = $option;

        // 添加到列表 & 设定配置
        Redis::hset("mq:list", $this->name, 1);
        Redis::hset("mq:{$this->name}", "option", json_encode($option));

        // 读取主进程信息
        $pid = Redis::hget("mq:{$this->name}", "pid");
        if ( $pid ) {
            $this->pid = $pid;
        }

        // 读取Worker进程信息
        $workers = Redis::hget("mq:{$this->name}", "workers");
        if ( $workers !== false ) {
            $workers = json_decode($workers, true);
            if ( $workers !== false) {
                $this->workers = $workers;
            }
        }

    }

    /**
     * 消费任务
     */
    public function pop( callable $callback, int $timeout=0 ) {

        $blocking = Arr::get($this->option, "blocking", false);

        // 阻塞模式下，锁定
        if ( $blocking ) {
            $this->lock( $timeout );
        }
        
        $priority_names = [
            "mq:{$this->name}:1",
            "mq:{$this->name}:2",
            "mq:{$this->name}:3",
            "mq:{$this->name}:4",
            "mq:{$this->name}:5",
            "mq:{$this->name}:6",
            "mq:{$this->name}:7",
            "mq:{$this->name}:8",
            "mq:{$this->name}:9"
        ];
        $data_res = Redis::brpop($priority_names, $timeout);
        if (!$data_res){
            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }
            throw Excp::create("消费任务失败(REDIS返回结果异常)", 500, [
                "prioritys" => $priority_names,
                "data_res" => $data_res,
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        $data = json_decode(Arr::get($data_res, 1), true);
        if ( $data === false ) {
            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }
            throw Excp::create("消费任务失败(JSON解析错误)", 500, [
                "data_res" => $data_res,
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        // 错误处理
        set_error_handler(function($errno, $errstr, $errfile, $errline ) use($blocking){
            
            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }

            // 队列错误
            $e = Excp::create("消费任务执行失败({$errstr})", 500, [
                "errno" => $errno,
                "errline" => $errline,
                "errfile" => $errfile,
                "name" => $this->name,
                "option" => $this->option
            ]);
            $e->log();
            throw $e;
        });

        $response = null;

        // 捕获异常
        try {
            $response = $callback( $data );

        } catch( Excp $e ) {
            
            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }
            throw $e;

        } catch( \PDOException $e ) {

            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }

            $message = $e->getMessage();
            throw Excp::create("消费任务执行失败({$message})", 500, [
                "name" => $this->name,
                "option" => $this->option
            ]);

        } catch( Excption $e ) {
            
            // 阻塞模式下，解锁
            if ( $blocking ) {
                $this->unlock();
            }

            $message = $e->getMessage();
            throw Excp::create("消费任务执行失败({$message})", 500, [
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        if ( $blocking ) {
            $this->unlock();
        }

        return $response;
    }


    /**
     * 添加任务
     * 
     * @param array $data      任务数据
     * @param int   $priority  任务优先级[1-9]
     * 
     * @return int $job_id 任务ID
     */
    public function push( array $data, int $priority=9 ) {
        
        // 最多9个优先级
        if ( $priority < 0 || $priority > 9 ) {
            $priority = 9;
        }

        $priority_name = "{$this->name}:{$priority}";
        $job_id = Redis::lpush("mq:{$priority_name}", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($job_id === false){
            throw Excp::create("添加任务失败", 500, [
                "data" => $data,
                "priority" => $priority,
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        return $job_id;
    }

    /**
     * 阻塞锁-解锁
     */
    private function unlock() {
        $unlock = "mq:{$this->name}:unlock";
        if ( !Redis::exists($unlock) || Redis::llen($unlock) == 0 ) {
            Redis::lpush($unlock, 1);
        }
    }

    /**
     * 阻塞锁-锁定
     * @param int $timeout 锁定时长
     */
    private function lock( int $timeout = 0 ){
        $unlock = "mq:{$this->name}:unlock";
        if ( Redis::exists($unlock) ) {
            Redis::brpop($unlock, $timeout);
        }
    }

    /**
     * 启动队列
     * 
     * @param int $workerNums Worker数量
     * @param callable $callback 任务程序
     * @param callable $onError 错误回调函数
     * @param int $timeout 任务运行超时时长，单位秒
     * 
     * @return int 主进程PID
     */
    public function start(int $workerNums = 1, callable $callback, callable $onError=null,  int $timeout=0 ) {
        
        if ( $onError == null ) {
            $onError = function($excp){};
        }

        // 检查守护进程是否已经启动
        if ( $this->pid != 0 && Process::kill($this->pid, 0) ) {
            throw Excp::create("{$this->name}守护进程已启动 PID={$this->pid}", 403);
        }

        $this->pid = posix_getpid();
        swoole_set_process_name("YAO-MQ-{$this->name}-Master");
        Redis::hset("mq:{$this->name}", "pid", $this->pid);
        set_time_limit(0);
        for( $i=0; $i<$workerNums; $i++ ) {
            $this->startWorker( $i, $callback, $onError, $timeout );
        }
        $this->monitor($callback, $onError, $timeout);
        return $this->pid;
    }

    /**
     * 关闭队列
     * @return bool true 关闭成功, false 关闭失败
     */
    public function stop() {
        Process::kill($this->pid, 9);
        if(!Process::kill($this->pid, 0)) {
            Redis::hset("mq:{$this->name}", "pid",0);
            return true;
        }
        return false;
    }

    /**
     * 创建Worker进程
     * 
     * @param int       $index      Worker序号
     * @param callable  $callback   任务程序
     * @param callable  $onError    错误处理函数
     * @param int       $timeout    任务运行超时时长，单位秒
     * @return void
     */
    private function startWorker( int $index=0, callable $callback, callable $onError, int $timeout=0 ) {
        $worker_process = new Process(function (Process $worker) use($index, $callback, $onError, $timeout) {
            swoole_set_process_name("YAO-MQ-{$this->name}-Worker-{$index}");
            // 回调任务数据
            try {
                $this->pop($callback, $timeout);
            }catch( Excp $e ){
                $onError($e);
            }

        }, 1, true);
        $worker_pid = $worker_process->start();
        $this->workers[$index] = $worker_pid;
        Redis::hset("mq:{$this->name}", "workers", json_encode($this->workers));
    }

    /**
     * 重启Worker进程
     * 
     * @param int       $pid        Worker进程号
     * @param callable  $callback   任务程序
     * @param callable  $onError    错误处理函数
     * @param int       $timeout    任务运行超时时长，单位秒
     * @return void
     */
    private function restartWorker( int $pid, callable $callback, callable $onError, int $timeout=0 ) {
        
        $index = array_search($pid, $this->workers);
        if ($index !== false) {
            $this->startWorker($index, $callback, $onError, $timeout );
            return;
        }
        throw Excp::create("子进程不存在({$pid})", 404);
    }

    /**
     * 监控Worker进程(如果退出，自动重启)
     * 
     * @param callable  $callback   任务程序
     * @param callable  $onError    错误回调函数
     * @param int       $timeout    任务运行超时时长，单位秒
     * @return void
     */
    private function monitor( callable $callback, callable $onError, int $timeout=0 ) {
        while(1){
            if ( count($this->workers) ) {
                // 检查退出子进程
                $worker_process = Process::wait(); 
                if ($worker_process) {
                    $this->restartWorker(Arr::get($worker_process, "pid"), $callback,$onError, $timeout);
                }
            } else {
                break;
            }
        }
    }
    

    /**
     * 终止指定任务
     */
    public function kill( string $job_id ) {
    }

    /**
     * 清除所有任务
     */
    public function clean( bool $except_pending = true ) {
    }

    /**
     * 查询队列清单
     */
    public static function search() {
    }

    /**
     * 删除队列
     */
    public static function remove( string $name ) {
    }
    
}
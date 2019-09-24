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

use function GuzzleHttp\json_decode;

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
     * Worker 进程信息
     * 
     * @var array<Process>
     */
    private $processes = [];


    /**
     * Worker 信息
     * 
     * @var array<Worker>
     */
    private $workers = [];


    /**
     * 等待关闭标记
     */
    private $flagStop = false;

    /**
     * 等待清空标记
     */
    private $flagClean = false;


    /**
     * 创建一个消息队列
     */
    public function __construct(string $name, array $option=[]) {

        // 从缓存中读取配置
        if ( empty($option) ) {
            $option_cache = Redis::get("mq:{$name}:option");
            if( $option_cache != false) {
                $option = json_decode($option_cache, true);
            }
            if ( $option === false ) {
                $option = [];
            }
        }

        // 设置默认值
        Arr::defaults($option, [
            "blocking" => false, // 是否为阻塞队列, 默认为非阻塞
            "log" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-mq-{$name}.log", 'debug']],
            "backup" => "/backup/yao-mq-{$name}.mq"
        ]);

        // 方法赋值
        $this->name = $name;
        $this->option = $option;
        Redis::set("mq:{$this->name}:option", json_encode($option));
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
        
        $priorities = $this->priorities();
        $priority_names = preg_filter('/^/', "mq:{$this->name}:", $priorities);
        array_push($priority_names, $timeout);
        $data_res = Redis::brpop(...$priority_names);
        if ($data_res === false ){
            throw Excp::create("消费任务失败(REDIS返回结果异常)", 500, [
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        $data = json_decode(Arr::get($data_res, 1, ""), true);
        if ( $data === false ) {
            throw Excp::create("消费任务失败(JSON解析错误)", 500, [
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

            throw Excp::create("消费任务执行失败({$errstr})", 500, [
                "errno" => $errno,
                "errline" => $errline,
                "errfile" => $errfile,
                "name" => $this->name,
                "option" => $this->option
            ]);
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
     * @return $this
     */
    public function push( array $data, int $priority=9 ) {
        
        if ( $this->flagStop || $this->flagClean ) {
            return $this;
        }

        // 最多9个优先级
        if ( $priority < 0 || $priority > 9 ) {
            $priority = 9;
        }

        $this->addPriority($priority);
        $priority_name = "{$this->name}:{$priority}";
        if (Redis::lpush("mq:{$priority_name}", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false){
            throw Excp::create("添加任务失败", 500, [
                "data" => $data,
                "priority" => $priority,
                "name" => $this->name,
                "option" => $this->option
            ]);
        }

        return $this;
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
     * 读取所有优先级数据
     * 
     * @return array<int> 优先级列表
     */
    private function priorities() {

        $text = Redis::hGet("mq:list", $this->name);
        if ( !$text  ) {
            $text = "[]";
        }

        $priorities = json_decode($text, true);
        if ( $priorities === false ) {
            $priorities = [];
        }
        return $priorities;
    }

    /**
     * 添加优先级
     * 
     * @param int $priority 优先级
     * @return $this
     */
    private function addPriority( int $priority ){
        $priorities = $this->priorities();
        array_push( $priorities, $priority );
        $priorities = array_unique( $priorities );
        sort($priorities);
        if (Redis::hSet("mq:list", $this->name, json_encode($priorities)) === false) {
            throw Excp::create("添加优先级失败", 500, [
                "priority" => $priority
            ]);
        }

        return $this;
    }


    /**
     * 启动Worker进程
     */
    public function start( callable $callback, int $workerNum = 1, int $timeout=0, callable $onSuccess=null, callable $onError=null) {
    }

    /**
     * 关闭Worker进程
     */
    public function stop( bool $wait_pending = true) {
    }

    /**
     * 队列监控程序
     */
    public function monitor() {
    }

    /**
     * Worker 进程清单
     */
    public function processes() {
    }

    /**
     * 队列中任务清单
     */
    public function jobs() {
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
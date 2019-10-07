<?php
/**
 * Class 
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use PDO;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Events\StatementPrepared;


/**
 * 数据库
 * see https://laravel.com/docs/5.8/database
 */
class DB extends Capsule {

    /**
     * 设定标记
     */
    public static $isInit = false;

    /**
     * 数据库对象
     */
    public function __construct( Container $container = null ) {
        parent::__construct( $container );
    }


    /**
     * 连接数据库
     *
     * @param  bool  $force 强制连接选项
     * @return void
     */
    public static function setting(bool $force=false ) {

        if ( !self::$isInit || $force===true ) {
            static::$isInit = true;
            $config = self::config();   
            $capsule = new self();
            $event = new Dispatcher();
            $event->listen(StatementPrepared::class, function ($event) {
                $event->statement->setFetchMode(PDO::FETCH_ASSOC);
            });
            $capsule->setEventDispatcher( $event );
            $capsule->addConnection( $config );
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        }
    }


    /**
     * [异步]连接数据库 (暂勿使用)
     * @param string $type write = 写连接, read = 读连接
     * @return \Swoole\Coroutine\MySQL  Instance
     */
    public static function connectAsync( $type="write" ) {

        $config = DB::config();
        $conn = Arr::get($config, "{$type}.0");
        if ( empty($conn) ) {
            throw Excp::create("读取数据库配置失败", 500);
        }

        $hosts = explode(":", Arr::get($conn, "host"));
        $conn["host"] = Arr::get($hosts, "0");
        $conn["port"] = Arr::get($conn, "port") ?  Arr::get($conn, "port") :  Arr::get($hosts, "1", 3306);
        $conn["user"] = Arr::get($conn, "username");
        Arr::forget($config, ["read", "write", "sticky", "driver","username"]);
        $config = array_merge($config, $conn );
        $mysql = new \Swoole\Coroutine\MySQL();
        $mysql->connect( $config );
        return $mysql;
    }

    /**
     * 读取数据库配置
     */
    public static function config() {
        return Arr::get($GLOBALS, "YAO.mysql", []);
    }
    
}
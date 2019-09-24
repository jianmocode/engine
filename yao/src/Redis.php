<?php
/**
 * Class Redis
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Exception;
use \Yao\Arr;
use \Predis\Client;

/**
 * Redis
 * see https://laravel.com/docs/5.8/redis
 */
class Redis {

    /**
     * predis 实例
     */
    public static $predis = null;

    /**
     * 创建 Redis 协议
     */
    public function __construct( Container $container = null ) {
        parent::__construct( $container );
    }


    /**
     * 连接 Redis Server
     */
    public static function connect() {

        if ( !Redis::$predis instanceof Client ) {
            $config = Arr::get($GLOBALS, "YAO.redis");
            Arr::defaults($config, [
                "read_write_timeout" => -1,
                "connection_timeout" => 5
            ]);
            try {
                Redis::$predis = new Client( $config );
            } catch( Exception $e ) {
                Log::write("error")->error("创建 Predis 实例失败", $config );
                return false;
            } catch( \Predis\Connection\ConnectionException $e ){
                Log::write("error")->error("创建 Predis 实例失败", $config );
                return false;
            }
        }
    }


    /**
     * 设定缓存数据
     * 
     * @return bool 成功设定返回 true, 失败返回  false
     */
    public static function set( $key, $value, $ttl=0 ) {
        
        if ( !Redis::$predis instanceof Client ) {
            return false;
        }

        try {
            $response = self::$predis->set("{$key}", $value);
        } catch( Exception $e ) {
            $config = Arr::get($GLOBALS, "YAO.redis");
            Log::write("error")->error("调用 Predis Set 命令失败", $config );
            return false;
        }

        if ( $response === false ) {
            return $response;
        }

        $response = true;
        if ( $ttl  > 0 ) {
            try {
                $response = self::$predis->expire("{$key}", $ttl);
            } catch( Exception $e ) {
                $config = Arr::get($GLOBALS, "YAO.redis");
                Log::write("error")->error("调用 Predis Set 命令失败", $config );
                return false;
            }
        }

        return $response;
    }


    
    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {

        if ( !Redis::$predis instanceof Client ) {
            return false;
        }
        try {
            return self::$predis->{$method}(...$parameters);
        } catch( Exception $e ) {
            $config = Arr::get($GLOBALS, "YAO.redis");
            Log::write("error")->error($e->getMessage(), $config );
            return false;
        }
        
    }
}

// 创建Redis 连接
Redis::connect();
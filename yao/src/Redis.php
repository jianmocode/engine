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
            try {
                Redis::$predis = new Client( $config );
            } catch( Exception $e ) {
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

        $response = self::$predis->set("{$key}", $value);
        if ( $response === false ) {
            return $response;
        }

        $response = true;
        if ( $ttl  > 0 ) {
            $response = self::$predis->expire("{$key}", $ttl);
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
        return self::$predis->{$method}(...$parameters);
    }
}

// 创建Redis 连接
Redis::connect();
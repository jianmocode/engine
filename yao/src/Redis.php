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
            $config = $GLOBALS["YAO"]["redis"];
            Redis::$predis = new Client( $config );
        }
    }

    
    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        return self::$predis->{$method}(...$parameters);
    }
}

// 创建Redis 连接
Redis::connect();
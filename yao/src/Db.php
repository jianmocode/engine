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
     * 连接标记
     */
    public static $isconnected = false;

    /**
     * 数据库对象
     */
    public function __construct( Container $container = null ) {
        parent::__construct( $container );
    }


    /**
     * 连接数据库
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function connect() {
        if ( !self::$isconnected  ) {
            static::$isconnected = true;

            $config = $GLOBALS["YAO"]["mysql"];
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
    
}

// 连接数据库
DB::connect();
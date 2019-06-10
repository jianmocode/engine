<?php 
/**
 * Yao Backend 配置文件
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */


return [

    /**
     * MySQL 数据库配置
     */
    "mysql" => [

        "read" => [
            [
                'host'      => '172.17.0.1:3307',
                'username'  => 'root',
                'password'  => '123456',
            ]
        ],

        "write" => [
            [
                'host'      => '172.17.0.1:3307',
                'username'  => 'root',
                'password'  => '123456',
            ]
        ],
        
        'sticky'    => true,
        'driver'    => 'mysql',
        'database'  => 'vpin',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ],



    /**
     * 日志配置
     */
    "logger" =>[
        "access" =>["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-access.log", 'debug']],
        "error" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-error.log", 'debug']],
        "debug" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-debug.log", 'debug']],
    ],

    /**
     * Redis 缓存配置
     */
    "redis" => [],

    /**
     * 数据存储配置
     */
    "storage" => [],
    
];
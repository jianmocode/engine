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
    "mysql" => [],

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
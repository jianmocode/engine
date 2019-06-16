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
     * 调试标记
     */
    "debug" => true,

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
    "redis" => [
        'scheme' => 'tcp',
        'host'   => '172.17.0.1',
        'port'   => 6379,
    ],

    /**
     * 数据存储配置
     */
    "storage" => [
        
        "public" => "https://cdn.vpin.biz", // 公共文件访问地址
        "private" => "https://private-cdn.vpin.biz", // 私密文件访问地址

        "options" => [
            "sync" => true, // 是否同步到 Remote, 默认为 true
            "backup" => true, // 是否同时保存备份, 默认为 false
        ],

        "local" => [
            "adapter" => "\\League\\Flysystem\\Adapter\\Local",
            "setting" => [
                "/data/stor/upload",
                LOCK_EX,
                0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
                [
                    'file' => [
                        'public' => 0744,
                        'private' => 0700,
                    ],
                    'dir' => [
                        'public' => 0755,
                        'private' => 0700,
                    ]
                ]
            ]
        ],

        "remote" => [
            "adapter" => "\\League\\Flysystem\\Adapter\\Local",
            "setting" => [
                "/data/stor/remote",
                LOCK_EX,
                0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
                [
                    'file' => [
                        'public' => 0744,
                        'private' => 0700,
                    ],
                    'dir' => [
                        'public' => 0755,
                        'private' => 0700,
                    ]
                ]
            ]
        ],

        "backup" => [
            "adapter" => "\\League\\Flysystem\\Adapter\\Local",
            "setting" => [
                "/data/stor/backup",
                LOCK_EX,
                0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
                [
                    'file' => [
                        'public' => 0700,
                        'private' => 0700,
                    ],
                    'dir' => [
                        'public' => 0700,
                        'private' => 0700,
                    ]
                ]
            ]
        ],
    ],
    
];
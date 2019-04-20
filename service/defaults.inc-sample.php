<?php
// 本文件有安装程序自动生成
// require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once('/data/composer/vendor/autoload.php');

//环境信息
define('_XPMAPP_ROOT', '/apps');

// 版本信息
define('_XPMSE_REVISION', '6ec30db');

// REDIS 服务器默认配置
define('_XPMSE_REDIS_HOST', 'local.xpmapp.com');
define('_XPMSE_REDIS_PORT', '6379' );
define('_XPMSE_REDIS_SOCKET', NULL );
define('_XPMSE_REDIS_PASSWD', NULL );

// 配置文件路径
define('_XPMSE_CONFIG_FILE', dirname(__FILE__) . '/config.json' );

// PHP 错误报告级别
error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE &  ~E_WARNING );
ini_set( 'display_errors' , true );
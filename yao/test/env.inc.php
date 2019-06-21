<?php
$ROOT =  realpath(dirname( __FILE__ ) . '/../../');
define( 'AROOT',  $ROOT);
define( 'SEROOT', "{$ROOT}/service");
define( 'LPROOT', "{$ROOT}/_lp/" );
define( 'CROOT', "{$ROOT}/_lp/core/");
define( 'DS' , DIRECTORY_SEPARATOR );
define( 'IN' , true );
include_once(LPROOT . '/autoload.php' );
include_once ( "{$ROOT}/lib/app.function.php" );
include_once ( "{$ROOT}/yao/vendor/autoload.php" );


/**
 * 从环境变量 "config" 数值，读取配置文件信息
 * 
 * 命令行:
 *  config=/config.php phpunit testModel.php
 * 
 * @param $default 默认配置文件路径
 * @return array 配置信息
 */
function loadConfig( $default) {

    $config_file = getenv("config");
    if (empty($config_file)) {
        $config_file = $default;   
    }
    if ( !file_exists($config_file) ) {
        echo "\n配置文件: {$config_file} 不存在\n";
        exit;
    }

    $config = require_once($config_file);
    if ( !is_array($config) ) {
        echo "\n配置文件: {$config_file} 格式不正确\n";
        exit;
    }
    return $config;
}
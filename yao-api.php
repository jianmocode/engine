<?php
/**
 * YAO API加载器
 */

use \Yao\Route;
use \Yao\Excp;
defined("YAO_APP_ROOT") ?: define("YAO_APP_ROOT", "/apps");
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
error_reporting(E_ALL);
ini_set('display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');

// 载入YaoJS Backend 配置
$GLOBALS["YAO"] = require_once(__DIR__ . "/yao/config.inc.php");

// 载入路由设定文件寻址 (正式上线时从配置文件中读取)
$domain_groups = [
    "vpin.biz" => [
        "default" => "/apps/vpin/backend/api/public",
        "user" => "/apps/vpin/backend/api/user",
        "kol" => "/apps/vpin/backend/api/kol",
        "vpin" => "/apps/vpin/backend/api/vpin",
    ]
];
$domain_groups["vpin.ink"] = $domain_groups["vpin.biz"];

// 读取域名信息
$host = $_SERVER["HTTP_HOST"];
$host_names = explode(".", $host);
$host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];

// 绑定的独立域名解析
if ( !in_array($host_name, ["vpin.biz", "vpin.ink"])){
    $cname = dns_get_record($host, DNS_CNAME);
    $host_names = explode(".", $host);
    $host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
}

// 读取 group_map
$GLOBALS["YAO"]["group_map"] = $domain_groups["$host_name"];


// 注册自动载入
function handler_autoload($class_name ) {

    
	$class_arr = explode( '\\', $class_name );
    $namespace  = current($class_arr);
    
    if ( strtolower($namespace) == 'yao') { 
        $YAO_ROOT = __DIR__ . "/yao";
            
        // Vendor autoload
        $autoload = realpath("{$YAO_ROOT}/vendor/autoload.php");
        include_once($autoload);
        
        // Class Name
        $class = array_pop($class_arr);
        array_shift( $class_arr);
        
        // Source Path
        $path = strtolower(implode("/", $class_arr));
        $src_path = !empty($path) ? "src/{$path}" : "src";
        $class_file = ucfirst(strtolower($class)) . '.php';
        $class_path_file = "{$YAO_ROOT}/{$src_path}/{$class_file}";
        include_once($class_path_file);

    // 载入APP
    } else if ( count($class_arr) >= 2 ) {

		$APP_ROOT = YAO_APP_ROOT;
        $class_arr = array_map( "strtolower", $class_arr );

        // 兼容旧版 Model (简墨引擎)
        if ( in_array("model", $class_arr) ) {

            $class = array_pop( $class_arr );
            $class_file = ucfirst($class);
            $class_path = strtolower(implode("/", $class_arr));
            $class_path_file = "{$APP_ROOT}/{$class_path}/{$class_file}.php";


        // YAO Backend 模型 
        } else {

            $class = array_pop( $class_arr );
            array_splice( $class_arr, 2, 0, ["model"] ); // 添加 model 目录
            $class_file = ucfirst($class);
            $class_path = strtolower(implode("/", $class_arr));
            $class_path_file = "{$APP_ROOT}/{$class_path}/{$class_file}.php";
        }

        if ( file_exists($class_path_file) ) {
            include_once($class_path_file);
        }
    }
};


/**
 * 异常通报
 */
function handler_excp($e) {

    $type = get_class($e);

    if ( $e instanceof Excp ) {

        $code = $e->getCode();
        if ( $code > 600 || $code < 400 ) {
            $code = 500;
        }
        http_response_code( $code );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo json_encode($e->toArray());

        // 服务端错误计入日志
        if ( $code >= 500 ) {
            $e->log();
        }
        exit;

    } else if ( $e instanceof Exception || $e instanceof Error ) {
        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jinamo.ink");
        echo '{"code":'.$e->getCode().', "message":"发生未定义错误"}';
        $exp = Excp::create("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误. Exception Type:{$type} Message:".$e->getMessage().".", 500 );
        $exp->log();
        exit;

    } else {
        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo '{"code":500, "message":"发生未定义错误"}';
        $message = $e->getMessage();
        $exp = Excp::create("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误 Exception Type:{$type} Message:{$message}.", 500 );
        $exp->log();
        exit;
    }
}

/**
 * 错误通报
 */
function handler_error($severity, $message, $file, $line) {
    // if (!(error_reporting() & $severity)) {
    //     // This error code is not included in error_reporting
    //     return;
    // }
    header("Content-Type: application/json");
    header("server: jianmo/server:1.9.3");
    header("x-powered-by: jianmo.ink");
    echo '{"code":500, "message":"程序运行错误"}';
    $e = Excp::create("{$message}( 第{$line}行 {$file} )",500);
    $e->log();
    exit;
}


set_error_handler("handler_error");
set_exception_handler('handler_excp');
spl_autoload_register("handler_autoload");



// 设定路由分组
Route::setGroups($GLOBALS["YAO"]["group_map"]);
$response = Route::run();


// 输出数据
header("Content-Type: application/json");
header("server: jianmo/server:1.9.3");
header("x-powered-by: jianmo.ink");

// 返回数据
if ( !is_null($response) ) {
    echo json_encode( $response, JSON_UNESCAPED_UNICODE || JSON_UNESCAPED_SLASHES );
}
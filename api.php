<?php
if ( isset($_GET['__timing']) && $_GET['__timing'] == 1 ) {
    $stime=microtime(true);
}

if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );
if( !defined('AROOT') ) define( 'AROOT' , realpath(__DIR__) );
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
error_reporting(E_ALL);
ini_set('display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');

require_once(__DIR__ . DS . "_lp" . DS ."autoload.php" );

use \Xpmse\Excp;
use \Xpmse\OpenApi;
use \Excption;

/**
 * 标准异常通报
 */
function handler_excp($e) {

    $type = get_class($e);
    if ( $type == "Error" || $type == "Exception" ) {
        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.1");
        header("x-powered-by: jinamo.ink");
        echo '{"code":'.$e->getCode().', "message":"发生未定义错误"}';
        $exp = new Excp("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误. Exception Type:{$type} Message:".$e->getMessage().".", 500 );
        $exp->log();

    } else if ( $type == "Xpmse\\Excp" ) {

        $code = $e->getCode();
        if ( $code > 600 || $code < 400 ) {
            $code = 500;
        }
        http_response_code( $code );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.1");
        header("x-powered-by: jianmo.ink");
        echo $e->toJSON();

        // 用户输入错误, 不计入日志
        if ( $code >= 500 ) {
            $e->log();
        }
    
    } else {

        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.1");
        header("x-powered-by: jianmo.ink");
        echo '{"code":500, "message":"发生未定义错误"}';
        $message = $e->getMessage();
        $exp = new Excp("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误 Exception Type:{$type} Message:{$message}.", 500 );
        $exp->log();
    }

}


/**
 * 安全过滤
 */
function securityFilter( & $input  ) {
    
    if ( is_array($input) ) {
        foreach( $input as & $v ) {
            securityFilter( $v );
        }
    } else if ( is_string($input) ) {
        $input = filter_var($input, FILTER_SANITIZE_STRING);
    }
}


set_exception_handler('handler_excp');

// 安全过滤
securityFilter( $_GET );
securityFilter( $_POST );


// API地址
$path = current(explode("?",$_SERVER["REQUEST_URI"]));

// 校验请求
OpenAPI::AuthorizeRequest( $path );

// 创建API实例
$class = OpenAPI::GetClass($path);
$instance = new $class["class"];
$response = $instance->run( $class["method"] );

http_response_code(200);
header("Content-Type: application/json");
header("server: jianmo/server:1.9.1");
header("x-powered-by: jianmo.ink");
echo json_encode( $response, JSON_UNESCAPED_UNICODE || JSON_UNESCAPED_SLASHES );


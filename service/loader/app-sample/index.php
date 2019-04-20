<?php
/**
 * XpmSE应用路由 
 * 
 * 所有请求将会通过本程序转发
 * 
 * @see XpmSE应用开发手册
 */

if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );

define('APP_ROOT', __DIR__);

$headers = [];
if ( function_exists('apache_request_headers') ) {
	$headers = apache_request_headers();
} else {

	foreach( $_SERVER as $key => $value ) {
        if ( substr($key,0,5)=="HTTP_" ) {
            $key = str_replace( " " , "-" , ucwords( strtolower( str_replace( "_" , " " , substr( $key , 5 ) ))));
            $headers[$key]=$value;
        }
        else {
            $headers[$key]=$value;
        }
    }
}

// 服务库根目录
$seroot = $headers['Xpmse-Service'];
if ( empty($seroot) ) {
	echo json_encode([
		'result'=>false, 
		'content'=>'服务库目录', 
		'data'=>$headers ]
	);
	exit;
}

// 自动载入脚本
require_once( $seroot . DS . 'loader' . DS  . "Autoload.php" );
\Xpmse\Loader\Auto::run( $headers );


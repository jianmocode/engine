<?php
/**
 * 载入XpmSE配置文件  
 *
 * 常量
 * __SE_VERSION:		XpmSE服务库版本
 * __SE_REVISION: 		XpmSE服务库修订版本
 * 
 * __CONFIG_ROOT: 		配置文件路径。 
 * 				  		对应环境变量: _XPMSE_CONFIG_ROOT
 *
 * __VHOST_NAME: 		VHOST名称
 *  				  		
 * __VHOST_CONFIG_ROOT: VHOST配置文件路径 ( __MULTIPLE = 1 时有效 )。
 * 					    对应环境变量: _VHOST_CONFIG_ROOT
 *
 * __CLUSTER:  			是否为集群模式。 1:集群模式; 0:单节点模式。  
 * 			   			对应环境变量: _XPMSE_CLUSTER
 * 			   			
 * __MULTIPLE: 			是否为多实例模式。 1:一个 HTTP Server 部署多套实例;  0:一个 HTTP Server 部署一套实例。 
 * 						对应环境变量: _XPMSE_MULTIPLE
 *
 * 
 */
define('__SE_VERSION', '1.8.2'); // 简墨
define('__SE_REVISION', '$Id: e057bd2604a9e66593a9a7991512527e3858675b $');

$_default_config_root = dirname(dirname(__DIR__)) . DS . 'config';
$_default_vhost_config_root = '/config/vhost';

// API地址HOST
$_xpmsns_api = 'https://service.jinamocloud.com/_api/xpmsns/homepage';
// $_xpmsns_api = 'https://dev.xpmsns.com/_api/xpmsns/homepage';
define('_XPMSE_API_LICENSE', $_xpmsns_api . '/license');
define('_XPMSE_API_APPSTORE', $_xpmsns_api . '/appstore');


// 当前为集群模式   
if ( getenv('_XPMSE_CLUSTER') == 1 ) {
	define('__CLUSTER', 1);  // 集群模式
} else {
	define('__CLUSTER', 0);  
}
	
// 一个 HTTP Server 部署多套实例
if ( getenv('_XPMSE_MULTIPLE') == 1 ) {  
	define('__MULTIPLE', 1);   // 多实例部署
	define('__VHOST_NAME', basename(dirname(dirname(dirname(__DIR__)))));

	// Vhost 配置目录
	$vhost_cfg = getenv('_VHOST_CONFIG_ROOT');
	if ( empty($vhost_cfg) ) {
		$vhost_cfg = $_default_vhost_config_root;
	}
	$GLOBALS['_XPMSE_CONFIG_ROOT'] = $vhost_cfg . DS . __VHOST_NAME;

	define('__VHOST_CONFIG_ROOT', $vhost_cfg );

	define('__CONFIG_ROOT', $GLOBALS['_XPMSE_CONFIG_ROOT']);

// 一个 HTTP Server 部署一套实例
} else {
	define('__MULTIPLE', 0);
	$GLOBALS['_XPMSE_CONFIG_ROOT'] = getenv('_XPMSE_CONFIG_ROOT');
	if ( empty($GLOBALS['_XPMSE_CONFIG_ROOT']) ) {
		$GLOBALS['_XPMSE_CONFIG_ROOT'] = $_default_config_root;
	}
	define('__VHOST_CONFIG_ROOT', '');
	define('__VHOST_NAME','');
	define('__CONFIG_ROOT', $GLOBALS['_XPMSE_CONFIG_ROOT']);
}

/**
 * 解析版本号
 * @return [type] [description]
 */
function __GET_SE_VISION() {
    $id = str_replace('$Id: ', '', __SE_REVISION);
    $id = str_replace(' $', '', $id);
    return ["version"=>__SE_VERSION, 'revision'=>$id];
}


// 加载默认配置
// default.inc.php 载入优先级:（ 单节点 ）
// 		1. 搜索 环境变量 _XPMSE_CONFIG_ROOT 定义的路径
// 		2. 搜索 服务器环境  DOCUMENT_ROOT 定义的路径
// 		3. 搜索 PHP include_path 定义的路径
// 		
$defaults_inc = $GLOBALS['_XPMSE_CONFIG_ROOT'] . '/defaults.inc.php';
// echo "\n\n";
// echo $defaults_inc . "\n" ;
// echo __VHOST_NAME. "\n" ;
// echo __MULTIPLE. "\n" ;
// echo __VHOST_CONFIG_ROOT. "\n" ;

// echo "\n\n";

@include_once($defaults_inc);  // 载入 _XPMSE_CONFIG_ROOT 或 DOCUMENT_ROOT 定义路径中的配置文件

if ( !defined('_XPMSE_REVISION') ) {
	include_once('defaults.inc.php');  // 载入 include_path 定义路径的配置文件 
}

if ( !defined("_XPMSE_REDIS_DB") ) {
    define('_XPMSE_REDIS_DB', 1);
}


if ( !defined('_XPMSE_REVISION') ) {  // 转向程序安装路径（如果存在）

	if ( file_exists($_SERVER['DOCUMENT_ROOT']. '/setup.php') ) {

		$proto ='http://';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https://';
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
         	$proto = 'https://';
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
            $proto = 'https://';
        }

        $home_root =  ( strlen(dirname($_SERVER['DOCUMENT_URI'])) > 1 ) ? dirname($_SERVER['DOCUMENT_URI']): '';
        $domain = $_SERVER["SERVER_NAME"];
        $home =  $proto . $_SERVER["HTTP_HOST"]. $home_root;

		header("Location: {$home}/setup.php");

	} else {
		echo json_encode([
			'code'=>404, 
			'message'=>'Not Found Configure File', 
			'extra'=>[
				'root' => dirname(__FILE__),
				'defaults_inc'=>$defaults_inc,
				'_SERVER'=>$_SERVER
			]
		]);
	}
	exit;
	die();
}


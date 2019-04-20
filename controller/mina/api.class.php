<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Xpmse\Api;

class minaApiController extends minaBaseController {

	function __construct() {
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		parent::__construct();
	}

    /**
     * 调用API 
     */
	function call() {
		
		$_api = $_REQUEST['_api'];
		if ( empty($_api) ) {
			throw new Excp('未提供API信息', 402 , ['REQUEST'=>$_REQUEST]);
        }

        // 解析API
        unset( $_GET['_api'], $_GET['n'], $_GET['c'], $_GET['a']);        
        $api_arr = explode('/', $_api );
		$method = end($api_arr);
		array_pop($api_arr);
		$api_name = ucwords(strtolower(end($api_arr)));
		array_pop( $api_arr );
		foreach ($api_arr as $idx => $name ) {
			$api_arr[$idx] = ucwords(strtolower($name));
		}
        $class_name =  trim(implode("\\", $api_arr) . "\\Api\\" . $api_name);
        if ( !class_exists($class_name) ) {
			throw new Excp("{$_api} 接口不存在", 404, ['api'=>$_api, "class"=>$class_name, "method"=>$method]);	
        }
        
        // 调用API
		$inst = new $class_name(['query'=>$_GET,'data'=>$_POST,'files'=>$_FILES]);
        $resp = $inst->call($method, $_GET, $_POST, $_FILES);

        // 输出数据
        $responseContentType = $inst->__getDataType();
        header('Content-type: ' . $responseContentType);

        if ( $responseContentType == 'application/json') {
			echo json_encode( $resp , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		} else {
			echo $resp;
		}
    }
}
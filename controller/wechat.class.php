<?php
// 废弃
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );





use \Xpmse\Wechat as TWechat;
use \Xpmse\Excp as Excp;
use \Xpmse\Log as Log;

class wechatController extends coreController {
	
	function __construct() {
		// 载入默认的
		parent::__construct();
	}

	/**
	 * 微信OAuth授权回调
	 * @return [type] [description]
	 */
	function authback() {

		$wechat = new Wechat();

		$state = t(v('state'));
		$code = t(v('code'));
		$goto = html_entity_decode(t(v('goto')));
		$wechat->getAuthToken( $code, $state );
		
		// echo "$goto"; echo "<a href='$goto'>GOOOO</a>"; die();

		header("Location: $goto");
	}


	/**
	 * 异步队列回调信息
	 * @return [type] [description]
	 */
	function queuecall() {

		$class = t(v('class'));
		$method = t(v('method'));

		$input = file_get_contents('php://input');
		$options = json_decode($input, true );

		$obj = new $class();
		$args = array();
		for($i=0; $i<count($options); $i++) {
			array_push($args, '$options[' . $i . ']');
		}

		$exp = '$ret = $obj->$method( '. implode(',', $args) . ');';
		eval($exp);


		// DEBUG 
		// $mmc = memcache_init();  // 创建MC实例
		// memcache_set($mmc,"exp", $exp);
		// memcache_set($mmc,"ret", var_export($ret, true));

	}

	function listen() {
		
		$wechat = new TWechat;
		$log = new Log('wechat::listen');
		
		$signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        if (  $wechat->checkSignature( $signature, $timestamp, $nonce ) ) {
        	
        	$input = file_get_contents('php://input');
        	$log->info('微信通知被触发', ['input'=>$input, 'get'=>$_GET] );
        	echo $_GET['echostr'];

        } else {
        	$e = new Excp('微信通知签名验证失败', '504',['get'=>$_GET]);
        	$e->log();
        	echo "FAILURE";

        }

	}


	// ---- 一下函数暂时未用到 
	function callbackurl() {
		if ( $this->checkSignature() ) {
			echo $_GET['echostr'];
		} else {
			echo "SOMTHIN ERROR";
		}
	}

	private function checkSignature() {
      	$signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = 'HELLODAOMEN';
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
	
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

}
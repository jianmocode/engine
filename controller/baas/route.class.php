<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;

class baasRouteController extends baasBaseController {

	function __construct() {
		parent::__construct();
	}

	/**
	 * 请求应用地址
	 * @return [type] [description]
	 */
	function app() {

		require_once( AROOT . 'controller' . DS . 'core/app/route.class.php' );
		
		$_GET['n'] = 'core-app';
		$_GET['c'] = 'route';
		$_GET['a'] = 'portal';

		$_GET['app_name'] = $_GET['_app'];
		$_GET['app_org']  = $_GET['_org'];
		$_GET['app_c'] = $_GET['_c'];
		$_GET['app_a'] = $_GET['_a'];

		unset( $_GET['_app'], $_GET['_c'], $_GET['_a']);

		$postData = is_array($this->data) ? $this->data : [];
		$_POST = array_merge($postData, $_POST);
		$_POST['wxapp.appid'] = $this->wxconf['wxapp.appid'];
		$_POST['wxapp.secret'] = $this->wxconf['wxapp.secret'];

		$_REQUEST = array_merge(  $_GET, $_POST );

		try {

			$route = new \coreAppRouteController();
			ob_start();
			call_user_func([$route, 'portal']);
			$content = ob_get_contents();
	        ob_clean();

	        if ( $content != null  ) {
	        	$resp = json_decode( $content, true );

	        	// 异常输出
	        	if ( isset($resp['result']) && 
	        		 isset( $resp['content']) && 
	        		$resp['result'] === false ) {
	        		
	        		Utils::out($resp['content']  );
	        		exit;
	        	}
	        }

	        Utils::out( $content );

		} catch( Excp $e ) {
			// Utils::out( );
			echo $e->toJSON();
		}

	}



	/**
	 * 请求外部网页
	 * @return [type] [description]
	 */
	function url() {	

		$method = $this->data['method'];
		$option = $this->data['option'];
		$url = $this->data['url'];
	
		$resp = Utils::Request( $method, $url, $option );
		Utils::out( $resp );
	}


}
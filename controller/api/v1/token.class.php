<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'api.class.php' );





use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;


class apiv1TokenController extends apiController {

	function __construct() {
		parent::__construct(['get']);
	}


	/**
	 * 读取API Token 
	 * POST /token/get
	 * @param  $appid 应用ID
	 * @param  $appsecret 应用Secret
 	 * @return 成功返回'access_token' 
	 */
	function get() {

		if ( !isset($this->data['appid']) ) {
			throw new Excp("缺少appid信息", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			return null;
		}

		if ( !isset($this->data['appsecret']) ) {
			throw new Excp("缺少appsecret信息", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			return null;
		}

		$resp = $this->api->genToken( $this->data['appid'], $this->data['appsecret'] );
		
		if ( Err::isError($resp) ) {
			throw new Excp($resp->message, $resp->code, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			return null;
		}

		echo json_encode(['access_token'=>$resp['token'], 'expires_at'=>$resp['expires_at']]);
	}
}
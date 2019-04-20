<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Xpmse\Mem;

class minaBaseController extends coreController {
	
	function __construct() {
		parent::__construct();
	}

	/**
	 * appid & secret 鉴权
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function auth( $appid=null, $secret=null ) {
		$appid =  empty($appid) ? $_REQUEST['appid'] : $appid;
		$secret = empty($secret) ? $_REQUEST['secret'] : $secret;
		$resp = M('Secret')->isSecretEffect($appid, $secret);
		if ( $resp === false ) {
			throw new Excp('非法请求 secret 已失效', 403, ['appid'=>$appid, 'secret'=>$secret]);
		}
	}


}
<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;

class baasHiController  extends baasBaseController {
	
	function __construct() {
		// 载入默认的
		parent::__construct();
	}

	function index() {

		echo json_encode([
				"server" => "Xpm Server V2",
				"status" => "ok"
			]);
	}


	function qrcode() {
		$path  = empty($_GET['page'])? '/pages/store/user/user/user' : urldecode(trim($_GET['page']));
		$path = str_replace('-', '/', $path);
		$resp = $this->wxapp->getQrcode($path);
		Header("Content-Type: {$resp['type']}");
		echo $resp['body'];
	}

}
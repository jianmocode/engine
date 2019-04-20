<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;

class baasDefaultController  extends baasBaseController {
	
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

}
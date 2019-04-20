<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;


class minaOtherController extends minaBaseController {

	function __construct() {
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		parent::__construct();
	}

	function call() {
		$_other = $_REQUEST['_other'];
		unset( $_GET['_other'] );
		if ( empty($_other) ) {
			throw new Excp('未提供查询接口', 400 , ['REQUEST'=>$_REQUEST]);
		}
		$data = M('Data');
		$resp = $data->query( $_other, $_GET, $_POST, $_FILES);

		if (  $GLOBALS['_RESPONSE-CONTENT-TYPE'] == 'application/json' ) {
			echo json_encode( $resp , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		} else {
			header('Content-type: ' . $GLOBALS['_RESPONSE-CONTENT-TYPE']);
			echo $resp;
		}
	}
}
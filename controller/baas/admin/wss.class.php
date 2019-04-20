<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;
use \Xpmse\Conf as Conf;

class baasAdminWssController extends privateController {
	
	function __construct() {
		parent::__construct([],['icon'=>'fa-code', 'icontype'=>'fa', 'cname'=>'开发工具']);
	}

	
	function index() {

		$this->_crumb('信道管理', R('baas-admin','wss','index', ['table'=>$table]) );
	    $this->_crumb('信道调试器');


		@session_start();
		$_SESSION['_user'] = 1;
		$_SESSION['_group'] = 'member';
		$_SESSION['_isadmin'] = 0;


		$data= ['_page'=>'admin/wss/index', 'domain'=>Conf::G('general/domain')];
		$data = $this->_data( $data , '信道管理');
		render($data, 'baas', 'main');
	}

}
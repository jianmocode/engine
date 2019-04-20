<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;

/**
 * MINA API调试工具
 */
class minaApidebuggerController extends privateController {

	function __construct() {
		parent::__construct([],['icon'=>'fa-code', 'icontype'=>'fa', 'cname'=>'开发工具']);
	}

	/**
	 * 代码生成器
	 * @return [type] [description]
	 */
	function index() {
		
		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$this->_crumb('开发工具', R('mina','apidebugger','index') );
	    $this->_crumb('API调试器');

		$data = $this->_data(["_TITLE"=>"开发工具/代码生成器"]);
		render( $data, 'mina/apidebugger', 'index');
	}
	
}
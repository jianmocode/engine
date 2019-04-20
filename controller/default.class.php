<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );

use \Xpmse\Utils as Utils;

class defaultController extends coreController
{
	function __construct() {
		// 载入默认的
		parent::__construct();
	}
	
	
	function index() {
		header("Location: /admin.php?n=core-dashboard&c=default&a=index");
	}


	function file() {

		$gw = new \Mina\Gateway\Http([
			"seroot" => Utils::seroot()
		]);

		$gw->load("xpmse/developer", function( $app ) {

			// 读取应用逻辑
			$tab = Utils::getTab('app', 'core_');
			$slug = implode('/', $app);
			$rows = $tab->query()->where('slug', '=', $slug)->limit(1)->get()->toArray();
			if ( empty($rows)) {
				throw new Excp('应用不存在或未安装', 404);
			}
			return current($rows);
		});

		$gw->file('/test/assets/test.jpg');
	}


	function fetch(){

		$gw = new \Mina\Gateway\Http([
			"seroot" => Utils::seroot()
		]);

		$gw->load("xpmse/developer", function( $app ) {

			// 读取应用逻辑
			$tab = Utils::getTab('app', 'core_');
			$slug = implode('/', $app);
			$rows = $tab->query()->where('slug', '=', $slug)->limit(1)->get()->toArray();
			if ( empty($rows)) {
				throw new Excp('应用不存在或未安装', 404);
			}
			return current($rows);

		})

		->init()

		->fetch('gateway', 'fetch');

		$resp = $gw->get();
		print_r( $resp );
	}


	function transparent(){
		@session_start();
		$user = empty($_SESSION["user/login/info"]) ? [] : json_decode($_SESSION["user/login/info"], true);

		$gw = new \Mina\Gateway\Http([
			"seroot" => Utils::seroot(),
			"user" => $user
		]);

		$gw->load("xpmse/developer", function( $app ) {

			// 读取应用逻辑
			$tab = Utils::getTab('app', 'core_');
			$slug = implode('/', $app);
			$rows = $tab->query()->where('slug', '=', $slug)->limit(1)->get()->toArray();
			if ( empty($rows)) {
				throw new Excp('应用不存在或未安装', 404);
			}
			return current($rows);

		})

		->init()

		->transparent('gateway', 'transparent');
	}



	// /**
	//  * CLI INDEX
	//  * @return [type] [description]
	//  */
	// function cli() {
	// 	Utils::out("CLI CALLED:\n", $_GET, "\n", $_POST,  "\n", $_REQUEST );
	// }

	// function api(){
		
	// 	echo file_get_contents("php://input");
	// }

}

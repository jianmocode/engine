<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );



use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;

class CoreSystemApiController extends privateController {

	function __construct() {
		// 载入默认的
		parent::__construct([],'system','appmanager');
	}


	/**
	 * 安装应用
	 * @return [type] [description]
	 */
	function install() {
		$app_id = $_POST['appid'];
		$app = M('App');	
		$resp = $app->setup($app_id, 'install');
		echo json_encode($resp);
	}

	/**
	 * 卸载应用
	 * @return [type] [description]
	 */
	function uninstall(){
		$app_id = $_POST['appid'];
		$app = M('App');	
		$resp = $app->setup($app_id, 'uninstall');
		echo json_encode($resp);
	}


	/**
	 * 修复应用
	 * @return [type] [description]
	 */
	function repair() {

		$app_id = $_POST['appid'];
		$app = M('App');	
		$resp = $app->setup($app_id, 'repair');
		echo json_encode($resp);
	}


	function upgrade() {
	}


	function scan() {
		$app = M('App');
		$resp = $app->scan();
		if (count($resp['error']) > 0) {
			throw new Excp( '有' . count($resp['error']). '个应用信息无效 ', 503, $resp['error']);
		}
		echo json_encode($resp);
	}
}
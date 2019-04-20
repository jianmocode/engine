<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Mina\Pages\Api\Article;

use \Exception as Exception;




class minaImageController extends minaBaseController {


	public $fonts = [];

	function __construct() {
		parent::__construct();
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
	}

	/**
	 * 读取图片信息
	 * @return [type] [description]
	 */
	function media() {
		
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		$size = $_GET['size'];
		$id = $_GET['media_id'];
		M('Media')->displayImage($id, $size);
	}


	// 有效字体清单
	function fonts(){
		$resp = Utils::fonts(1,20);
		echo json_encode([
			"items"=>$resp['data'],
			"total"=>count($resp['data'])
		]);
	}


	function wechat() {

		$conf = Utils::getConf();
		$grops = is_array($conf['_groups']) ? $conf['_groups'] : []; 

		$items = [];
		foreach ($grops as $group => $cfg) {
			if ( $cfg['type'] <> 4 && $cfg['appid'] <> '' ){
				array_push($items, [
					'text'=>$group, "id"=>$group
				]);
			}
		}

		echo json_encode([
			"items"=>$items,
			"total"=>count($items)
		]);
	}


	function error() {
		
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		$message = !empty($_GET['message'])? $_GET['message'] : "图片出错了" ;
		throw new Excp($message, 500);
	}


	function text() {
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		M('Media')->text($_GET, $image);
		header('Content-type: image/png');
		echo $image;
	}


	function qrcode(){
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		M('Media')->qrcode($_GET, $image );
		header('Content-type: image/png');
		if (isset($_GET['name'])) {
			header("Content-Disposition: attachment; filename=\"{$_GET['name']}.png\"");
		}
		echo $image;
	}


	

}
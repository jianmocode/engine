<?php
if( !defined('IN') ) die('bad request');

include_once( AROOT . 'controller' . DS . 'private.class.php' );


class coreDashBoardDefaultController extends privateController
{
	function __construct() {

		// 载入默认的
		parent::__construct([], ['icon'=>'si-speedometer', 'icontype'=>'si', 'cname'=>'控制台']);
	}

	function index() {

		$opt = new \Xpmse\Option;
		$page = $opt->get("dashboard");
		if ( !empty($page)  && !$this->user['isAdmin'] ) {
			header('Location: ' . $page );
			return;
		}
		
		$this->my();
	}

	

	/**
	 * 默认的控制器
	 * @return [type] [description]
	 */
	function my() {
				// 导航
		$app = M('App');
		$apps = $app->getInstalled();
		foreach ($apps['data'] as  $idx => $a ) {
			if ( is_array($a['index']) ) {
				$apps['data'][$idx]['index'] = ( isset($a['index']['link']) ) ? $a['index']['link'] : null;
			}
		}

		// $apps['data'] = array_merge([
		// 	[
		// 		"cname"=>"小程序后端管理器",
		// 		"intro"=>"数据管理、信道管理、微信证书配置等",
		// 		"icontype"=>"fa",
		// 		"icon"=>"fa-wechat",
		// 		"index"=>R('baas-admin', 'data', 'index' )
		// 	]

		// ], $apps['data']);


		$data = $this->_data(['apps'=>$apps['data']]);
		render( $data, 'core/dashboard/web', 'index');
	}

	function first() {	
		$this->index();
	}

} 
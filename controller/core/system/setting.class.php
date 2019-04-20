<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );




use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;



class coresystemsettingController extends privateController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct([],'system','system');
	}

	function index() {

		
		// 菜单
		$this->_active('default/settings');


		// // 导航
		$data = $this->_data([],'系统配置','系统设置');

		// SETTING
		if ( $this->browser['iswechat'] ) { // 微信UI
			render( $data, 'core/system/wechat', 'index');

		} else if ($this->browser['isdingtalk'] ) {  // 钉钉UI
			render( $data, 'core/system/dingtalk', 'index');

		} else if ( $this->browser['ismobiledevice'] ) {  // 手机浏览器 UI
			render( $data, 'core/system/mobile', 'index');

		} else if ( $this->browser['istablet'] ) {  // 平板浏览器 UI
			render( $data, 'core/system/tablet', 'index');

		} else { // PC UI
			render( $data, 'core/system/web/setting', 'index');
		}
	}

	/**
	 * 系统选项标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_general() {

	    $data = OM('Core::Option');

	    // 查询表里所有数据

	    $getone = $data->select();

	    $data = ['data'=>$getone['data']['0']];



		render( $data, 'core/system/web/setting', 'general');
	}


	/**
	 * 应用标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_app() {


		$data = $this->_data();

		render( $data, 'core/system/web/setting', 'app');
	}




	/**
	 * 接入钉钉标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_dingtalk() {

		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'dingtalk');

		//header('HTTP/1.1 500 Internal Server Error'); 
	}

	/**
	 * 接入微信标签表单 (网页版) 
	 * @return [type] [description]
	 */
	function settings_form_wechat() {
		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'wechat');
	}


	/**
	 * 通知标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_notify() {

		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'notify');

	}

	/**
	 * 日志标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_log() {
		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'log');		
	}


	/**
	 * Redis标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_Redis() {

		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'Redis');		
	}

	/**
	 * 开发者工具标签表单 (网页版本)
	 * @return [type] [description]
	 */
	function settings_form_Storage() {
		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'Storage');	
	}

	/**
	 * 系统信息标签信息
	 * @return [type] [description]
	 */
	function settings_form_SuperTable() {
		$data = $this->_data();
		render( $data, 'core/system/web/setting', 'SuperTable');	
	}


} 
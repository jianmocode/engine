<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );




use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;



class coreSystemDefaultController extends privateController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct([],'system','system');
	}

	function settings() {

		// 菜单
		$this->_active('default/settings');

		// 导航
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
			render( $data, 'core/system/web', 'settings');
		}
	}

	/**
	 * 系统选项标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_general() {

		$data = $this->_data();

		render( $data, 'core/system/web/form', 'general');
	}


	/**
	 * 接入钉钉标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_dingtalk() {

		$data = $this->_data();
		render( $data, 'core/system/web/form', 'dingtalk');

		//header('HTTP/1.1 500 Internal Server Error'); 
	}

	/**
	 * 接入微信标签表单 (网页版) 
	 * @return [type] [description]
	 */
	function settings_form_wechat() {
		$data = $this->_data();
		render( $data, 'core/system/web/form', 'wechat');
	}


	/**
	 * 服务参数标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_service() {
		$data = $this->_data();
		render( $data, 'core/system/web/form', 'service');

	}

	/**
	 * 系统运维标签表单 (网页版)
	 * @return [type] [description]
	 */
	function settings_form_operation() {
		$data = $this->_data();
		render( $data, 'core/system/web/form', 'operation');		
	}

	/**
	 * 开发者工具标签表单 (网页版本)
	 * @return [type] [description]
	 */
	function settings_form_develop() {
		$data = $this->_data();
		render( $data, 'core/system/web/form', 'develop');	
	}


	/**
	 * 系统信息标签信息
	 * @return [type] [description]
	 */
	function settings_form_about() {
		$data = $this->_data();
		render( $data, 'core/system/web/form', 'about');	
	}

	function first() {
		$this->index();
	}


} 
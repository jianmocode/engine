<?php
// XpmSE控制器 (不需要后台登录)
// 
//
//
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Stor as Stor;
use \Xpmse\Mem as Mem;
use \Xpmse\Err as Err;
use \Xpmse\Excp as Excp;


class publicController extends coreController {	

	/**
	 * 浏览器信息
	 * @var array
	 */
	public $browser = [];


	/**
	 * 用户登录配置项
	 * @var array
	 */
	public $login = [];


	/**
	 * 基本配置信息
	 */
	public $general = [];



	/**
	 * 当前路由信息
	 * @var array
	 */
	public $route = [];


	/**
	 * 是否为Ajax请求
	 */
	public $isAjax = false;


	/**
	 * 请求返回数据类型 ( json | html |null)
	 */
	public $datatype = null;


	function __construct() {

		// 载入默认的
		parent::__construct();
		$this->route = [
			'namespace' => (isset($_GET['n'])) ? trim($_GET['n']) : "",
			'controller' => (isset($_GET['c'])) ? trim($_GET['c']) : "default",
			'action' => (isset($_GET['a'])) ? trim($_GET['a']) : "index"
		];

		$ut = new Utils;
		$this->browser = $ut->getBrowser(); 
		$this->isajax = $ut->isAjax();
		$this->datatype = $ut->responseType();
		$this->general = Conf::G('general');

		isLicenseEffect();
	}


	/**
	 * 生成基本信息数据
	 * @param  [type]  $title  [description]
	 * @param  boolean $isfull [description]
	 * @return [type]          [description]
	 */
	function _data( $_data=[], $title=null, $isfull=false ) {

		// 后台不再做适配
		// if( $this->login['wechat'] !== false &&  $this->browser['iswechat'] ) { // 载入微信SDK
		// 	$wechat = new Wechat('public');
		// 	try {
		// 		$data['_WECHAT_SDK'] = $wechat->getSignature();
		// 	} catch( Excp $e ) {
		// 		$data['_WECHAT_SDK'] = ['code'=>500, 'message'=>'getSignature ERROR'];
		// 	}
		// }

		if ( $this->browser['ismobiledevice'] ) { 
			$isfull = true;
		}	


		// 载入钉钉SDK

		$data['_TITLE'] = (empty($title)) ? $this->general['short'] : "{$title}-{$this->general['short']}";
		if ( $isfull && !empty($title) )  {
			$data['_TITLE'] = $title;
		}

		

		$data['_NAME'] =  empty($this->general['name']) ? "简墨" :$this->general['name'];
		$data['_SHORT'] =  empty($this->general['short']) ? "简墨" : $this->general['short'];
		$data['_COMPANY'] =  $this->general['company'];
		$data['_STATIC'] = (empty($this->general['static']))? "" : $this->general['static'];
		$data['_DOMAIN'] = (empty($this->general['domain']))? "" : $this->general['domain'];
        $data['_HOMEPAGE'] = (empty($this->general['homepage']))? "" : $this->general['homepage'];
        
        
        $data['_LOGO'] = [
            "default" =>"/static/defaults/images/logo/color.svg",
            "color" =>"/static/defaults/images/logo/color.svg",
            "dark" => "/static/defaults/images/logo/dark.svg",
            "light" =>"/static/defaults/images/logo/dark.svg",
            "fav" => "/static/defaults/images/logo/fav.png",
        ];

        $logo_path = is_string($this->general['logo']) ?  $this->general['logo'] : null;
        if ( !empty($logo_path) ) {
            $media = new \Xpmse\Media(["home"=>Utils::getHome()]);
            $uri =  $media->get($logo_path);
            $data["_LOGO"]["custom"] = $uri["url"];
        }

        $fav_path = is_string($this->general['fav']) ?  $this->general['fav'] : null;
        if ( !empty($fav_path) ) {
            $media = new \Xpmse\Media(["home"=>Utils::getHome()]);
            $uri =  $media->get($fav_path);
            $data["_LOGO"]["custom_fav"] = $uri["url"];
        }

		$ut = new Utils;
		$go = ['URL'=>R('core-dashboard', 'default', 'index')];

		$data['_NEXT'] = $go;
		return array_merge( $data, $_data );
	}


	/**
	 * 读取微信配置 ( Copy From BaaS )
	 * @return
	 */
	protected function _conf( $nocache = false ) {
		return Utils::getConf();
	}
}



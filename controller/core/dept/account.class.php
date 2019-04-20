<?php

// XpmSE账号管理控制器
include_once( AROOT . 'controller' . DS . 'public.class.php' );




use \Xpmse\Mem as Mem;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;



class coreDeptAccountController extends publicController {

	function __construct() {
		parent::__construct();
	}


	function index() {
		echo "index";
	}

	/**
	 * 验证码函数
	 */
	function vcode() {

		@session_start();
		$id = session_id();
		$name = ( $_GET['name'])? $_GET['name'] : 'account';
		$expires = ( $_GET['expires'])? intval($_GET['expires']) : 60;
		$height = ($_GET['height']) ? intval($_GET['height']) : 42;
		$cache = "vcode:$id:$name";
		$_vc = Utils::vcode(); //实例化一个对象
		
		$mem = new Mem;
		$code = $_vc->getCode();
		$mem->set($cache, $code, $expires); // 60秒后过期
		$_vc->doimg( 120, $height, 18);

	}


	/**
	 * 退出登录
	 * @return [type] [description]
	 */
	function logout() {

		$user = M('User');
		$user->logout();
			
		$url = R('core-dept', 'account', 'login');
	 	header("Location: $url");
	}



	/**
	 * 用户登录表单
	 */
	function login() {
		$this->login_password();
		return;
	}

		/**
		 * 用户名密码方式登录
		 * @Platform Desktop
		 */
		function login_password() {
			$user = M('User');
			$loginTimes = $user->getLoginTimes();
			$data = $this->_data(['loginTimes'=>$loginTimes], '登录');
			render( $data, 'core/dept/web', 'login.password');
		}

}
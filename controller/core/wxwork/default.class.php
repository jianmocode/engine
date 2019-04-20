<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Endroid\QrCode\QrCode;
use \Xpmse\Wxwork;

class coreWxworkDefaultController extends privateController  {

	private $ww = null;


	function __construct() {

		// 载入默认的
		parent::__construct(['password','login', 'authback']);

		$this->ww = new Wxwork([
			'corpid' => 'WWe146299c731e6301',
			'secret' => 'VPFu2NpuNuQJx0MEOsC0pQolb-PtSmqnjqsptchjYbk'
		]);
	}

	/**
	 * 转向登录页面
	 * @return [type] [description]
	 */
	function login() {

		@session_start();
		$go = [ 
			'gapp' => empty($_GET['gapp']) ? null :  $_GET['gapp'],
			'gc' =>  empty($_GET['gc']) ? 'default' :  $_GET['gc'],
			'ga' => empty($_GET['ga']) ? 'index' :  $_GET['ga']
		];

		$_SESSION['_AFTER_LOGIN'] = $go;

		$url = $this->ww->getAuthUrl( R('core-wxwork', 'default', 'authback') );
		header("Location: $url");
	}


	/**
	 * 登录表单数据处理
	 */
	function authback() {


		$u = M('User');
		$desp = $this->ww->getUserByCode($_REQUEST['code'], $_REQUEST['state'] );
		$mobile = $desp['mobile'];

		$user = $u->getLine("where mobile=? LIMIT 1", [], ["$mobile"] );

		// 同步注册用户
		if ( empty($user) ) { 

			// 同步部门信息
			$dp = M('Department');
			foreach ($desp['department'] as $dept_id ) {
				$depts = $this->ww->getDepartment( $dept_id );

				foreach ($depts as $dept ) {
					$dp->save( $dept );
					
				}
			}

			// 注册用户
			$u->create( $u->wxworkData( $desp ) );

			// // 设置密码
			// $this->password();
			// return;
		}
		

		// 更新用户信息，自动登录
		unset($desp['avatar']);
		$userData = $u->updateBy( "userid", $u->wxworkData( $desp ) );

		if ( $userData == false ) {
			throw new Excp( '系统错误,请联系管理员。', '500',['resp'=>$desp] );
		}

		// 登录系统
		if ( $u->setSession($userData['_id']) === false ) {
			$e = new Excp( '系统错误,请联系管理员。', '500',
				['_FIELD'=>'error', '_POST'=>$_POST, 'session'=>session_id(), 'user'=>$userData] );
			$e->log();
			echo $e->error->toJSON();
			return;
		}


		// 清空菜单缓存
		M('Menu')->cleanCache();


		// 转向应用主页
		@session_start();
		$go = $_SESSION['_AFTER_LOGIN'];
		unset($_SESSION['_AFTER_LOGIN']);
		$next = [ 
			'gapp' => empty($go['gapp']) ? null :  $go['gapp'],
			'gc' =>  empty($go['gc']) ? 'default' :  $go['gc'],
			'ga' => empty($go['ga']) ? 'index' :  $go['ga']
		];

		if ( $next['gapp'] == null ) {
			header('Location: '. R(null, $next['gc'], $next['ga']) );
		} else {
			header('Location: '. AR($next['gapp'], 'n', $next['gc'], $next['ga']) );
		}
	}


	/**
	 * 密码设定表单
	 * @return [type] [description]
	 */
	function password() {

	}


	/**
	 * 从企业微信拉取员工 ( Run As Daemon )
	 * @return [type] [description]
	 */
	function pull() {

	}


	/**
	 * 向企业微信推送员工 ( Run As Daemon )
	 * @return [type] [description]
	 */
	function push() {

	}


	/**
	 * 从企业微信拉取部门 ( Run As Daemon )
	 * @return [type] [description]
	 */
	function deptpull() {

	}


	/**
	 * 向企业微信推送部门 ( Run As Daemon )
	 * @return [type] [description]
	 */
	function deptpush() {

	}




}
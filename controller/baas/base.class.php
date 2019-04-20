<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Xpmse\Mem;
use \Xpmse\Wxpay;



class baasBaseController extends coreController {
		
	protected $wxapp = null;
	protected $wxpay = null;
	protected $wxconf = null;
	protected $data = [];
	protected $cid = null;


	function __construct() {
		
		parent::__construct();
		

		$input  = file_get_contents("php://input");
		if ( $input != null ) {
			$this->data = json_decode($input, true);

			if ( json_last_error()  != JSON_ERROR_NONE ) {
				throw new Excp("input data error (". json_last_error_msg().") ", 400, ['input'=>$input]);
			}

			if ( empty($this->data['_sid']) && empty($_GET['_sid'])  ) {
				throw new Excp("no session id", 403, ['input'=>$input, '_GET'=>$_GET]);
			}

			if ( !empty($this->data['_sid']) ) {
				$_REQUEST['_sid'] = $this->data['_sid'];
			}

			if ( !empty($this->data['_cid']) ) {
				$_REQUEST['_cid'] = $this->data['_cid'];
			}

			if ( !empty($this->data['_appid']) ) {
				$_REQUEST['_appid'] = $this->data['_appid'];
			}

			if ( !empty($this->data['_secret']) ) {
				$_REQUEST['_secret'] = $this->data['_secret'];
			}

		}


		// 校验请求
		$this->authSecret($_REQUEST['_secret']);


		// 通过 $_GET 传递 SESSION
		// $session_id = !empty($_REQUEST['_sid']) ?  'BaaS-' . $_REQUEST['_sid'] : null;
		$session_id = !empty($_REQUEST['_sid']) ? $_REQUEST['_sid'] : null;
		@session_id( $session_id );
		// @session_start();


		$this->wxconf = $c = $this->loadconf();
		if ( !empty( $_REQUEST['_appid'] )  ) {
			$cfg = $c["_map"][$_REQUEST['_appid']];
			$this->wxapp = new Wxapp([
				'appid'=> $cfg['appid'],
				'secret'=>$cfg['secret']
			]);

			$this->wxpay = new Wxpay([
				'appid'=> $cfg['appid'],
				'secret'=>$cfg['secret'],
				'mch_id'=> $cfg['mch_id'],  // 商户号
				'key' =>$cfg['key'],
				'cert' =>$c['pay.cert'],
				'cert.key' => $c['pay.cert.key'],
				'notify_url' => Utils::getHomeLink() . R('baas','pay','notify')
			]);
			
		} else {

			if ( intval($_REQUEST['_cid']) <= 1 ) {
				$_REQUEST['_cid'] = '';
			}
			$this->cid = $cid = !empty($_REQUEST['_cid']) ? '_' . $_REQUEST['_cid'] : '';
			$this->wxapp = new Wxapp([
				'appid'=> $c['wxapp.appid' . $cid],
				'secret'=>$c['wxapp.secret' . $cid],
			]);

			$this->wxpay = new Wxpay([
				'appid'=> $c['wxapp.appid' . $cid],
				'secret'=>$c['wxapp.secret' . $cid],
				'mch_id'=> $c['wxpay.mch_id' . $cid],  // 商户号
				'key' => $c['wxpay.key' . $cid],
				'cert' => $c['pay.cert' . $cid],
				'cert.key' => $c['pay.cert.key' . $cid],
				'notify_url' => Utils::getHomeLink() . R('baas','pay','notify')
			]);
		}
	}

	// function qrcode() {
	// 	$path  = $_GET['path'];
	// 	$resp = $this->wxapp->createwxaqrcode('/pages/store/user/user/user');
	// 	Header("Content-Type: {$resp['type']}");
	// 	echo $resp['body'];

	// }


	/**
     * Secret 鉴权 ( Https Only )
     * @param  [type] $appid  [description]
     * @param  [type] $secret [description]
     * @return [type]         [description]
     */
    protected function authSecret( $appid, $secret = null ) {

    	if ( empty($secret) ) {
    		$arr = explode("|", $appid );
    		$appid = $arr[0];
    		$secret = $arr[1];
    	}

		if ( empty($secret) ) {
			throw new Excp("Secret 错误", 403, ['secret'=>$secret, 'appid'=>$appid]);
		}

    	$sc = new \Xpmse\Secret;
		$secretReal = $sc->getSecret($appid);

		if ( $secretReal !== $secret ) {
			throw new Excp("Secret 错误", 403, ['secret'=>$secret, 'appid'=>$appid]);
		}
    }


	protected function loadconf() {

		return Utils::getConf();

	}


	/**
	 * 读取当前用户信息
	 * @return [type] [description]
	 */
	protected function getUserInfo() {
		return !empty($_SESSION['_loginInfo']) ? $_SESSION['_loginInfo'] : null;
	}


	/**
	 * 读取当前会话客户
	 * @return [type] [description]
	 */
	protected function currUser() {

		$user = (isset($_SESSION['_user'])) ? $_SESSION['_user'] : session_id();
		$group = (isset($_SESSION['_group'])) ? $_SESSION['_group'] : 'guest';
		$isadmin= (isset($_SESSION['_isadmin'])) ? $_SESSION['_isadmin'] : 0;
		
		$data = [];
		$data['_user'] = $user;
		$data['_group'] = $group;
		$data['_isadmin'] = $isadmin;
		return $data;
	}


}
<?php
/**
 *  废弃
 */

if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'api.class.php' );


use \Exception as Exception;
use \Xpmse\Conf as Conf;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Utils as Utils;


class apiv1AppController extends apiController {

	private  $appinfo = null;

	private  $approute = [];

	private  $appuri =  [];

	private  $user =[];


	function __construct() {

		@session_start();

		$appname = (isset($_GET['app_name'])) ? $_GET['app_name'] : null;
		$controller = (isset($_GET['app_c'])) ? $_GET['app_c'] : null;
		$action = (isset($_GET['app_a'])) ? $_GET['app_a'] : null;
		$key = "/{$controller}/{$action}";


		if ( $appname == null || $controller == null || $action == null ) {
			throw new Excp("缺少应用信息", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;
		}

		// 读取应用清单
		$appinfo = M('App')->getRegisterAPI( $appname );
		if ( $appinfo  === null ) {
			throw new Excp("{$appname}无注册API", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;
		}

		if ( !isset($appinfo['api'][$key]) ) {
			throw new Excp("{$key} API未注册", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;	
		}

		$api = $appinfo['api'][$key];
		
		// 更新应用HOST 信息
		$path = str_replace(_XPMAPP_ROOT, '', $appinfo['path']);
		$host = (!empty(Conf::G('general/apphost')))? Conf::G('general/apphost') : "http://apps.JianMoApp.com" ;


		$this->appinfo =  $appinfo;
		
		$this->approute = [
			"host" => "{$host}{$path}",
			'id' => $appinfo['appid'],
			'name' => $appinfo['name'],
			'controller'=>$api['controller'],
			'action' =>$api['action']
		];


		$this->appuri = [
			'home'   =>R('core-app','route','index', ['app_name'=>$this->approute['name'],'app_id'=>$this->approute['id']] ),
			'noframe'=>R('core-app','route','noframe', ['app_name'=>$this->approute['name'],'app_id'=>$this->approute['id']] ),
			'static' =>R('core-app','route','staticurl',['app_name'=>$this->approute['name'],'app_id'=>$this->approute['id'], 'path'=>'']),
			'portal' =>R('core-app','route','portal',['app_name'=>$this->approute['name'],'app_id'=>$this->approute['id']])
		];


		$ut = new Utils;
		$this->user['clientIP'] = $ut->getClientIP();
		$this->user['sessionID'] = session_id();

		// HTTP请求转发头部信息规划
		$this->appheaders = [
			"Content-Type: application/api",
			"CLIENT-IP: {$this->user['clientIP']}",
			"X-FORWARDED-FOR: {$this->user['clientIP']}",
			"Xpmse-Useragent: {$_SERVER['HTTP_USER_AGENT']}",
			"Xpmse-Appid: {$this->approute['id']}",
			"Xpmse-Appname: {$this->approute['name']}",
			"Xpmse-Controller: {$this->approute['controller']}",
			"Xpmse-Action: {$this->approute['action']}",
			"Xpmse-Home: {$this->appuri['home']}",
			"Xpmse-Noframe: {$this->appuri['noframe']}",
			"Xpmse-Static: {$this->appuri['static']}",
			"Xpmse-Portal: {$this->appuri['portal']}",
			"Xpmse-Service: " . Utils::seroot(),
		];

		if ( isset($api['public']) && $api['public'] == true ) {
			parent::__construct(['index']);
		} else{
			parent::__construct([]);
		}

		unset($this->query['app_name'],$this->query['app_c'],$this->query['app_a']);
		
	}



	/**
	 * 应用API转发
	 * @return [type] [description]
	 */
	function index() {

		$data = [
			'_REQUEST' => ['input'=>$this->body, 'get'=>$this->query],
			'_USER'=>$this->user,
			'_INJECTION'=>[]		
		];

		// 调用应用程序
		$ut = new Utils;

		$resp = $ut->Request( 'POST',  "{$this->approute['host']}/index.php", [
			'header'=>$this->appheaders,
			'data'=>$data,
			'type'=>'json',
			'datatype'=>'html'
		]);

		echo $resp;
	}

}

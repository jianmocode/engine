<?php
// XpmSEAPI控制器基类 （ 请不要直接使用 ）
// 
// 
// 
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );





use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;


class apiController extends coreController {

	/**
	 * 无需验证AccessToken的地址
	 * @var array
	 */
	protected $extra = [];

	/**
	 * 请求路由表信息
	 * @var array
	 */
	protected $route = [];


	/**
	 * GET 数据参数表
	 * @var array
	 */
	protected $query = [];


	/**
	 * POST 数据参数表
	 * @var array
	 */
	protected $data = [];


	/**
	 * 应用信息
	 */
	protected $api = null;


	/**
	 * Token 
	 */
	protected $token = null;


	/**
	 * 应用ID
	 * @var null
	 */
	protected $appid = null;


	/**
	 * Body
	 */
	protected $body = null;
	

	function __construct( $extra ) {

		$ut = new Utils;
		$ut->setRespType('application/json');

		// 载入默认的
		parent::__construct();
		
		$this->route = [
			'namespace' => (isset($_GET['n'])) ? t(v('n')) : "",
			'controller' => (isset($_GET['c'])) ? t(v('c')) : "default",
			'action' => (isset($_GET['a'])) ? t(v('a')) : "index"
		];

		$this->extra = $extra;


		$query = array_merge($_GET, []);
			unset( $query['n'] );unset( $query['a'] );unset( $query['c'] );
			$this->query = $query;


		$body = file_get_contents('php://input');
			if ( $body != null ) {
				$this->body = $body;
				$this->data = json_decode($body, true);
				if( json_last_error() !== JSON_ERROR_NONE) {
					throw new Excp('请求数据异常 ('.json_last_error_msg().')',  "503", ['query'=>$query, 'body'=>$body]);					
				}
			}


		$this->api = M('Secret')->apiInit();

		// $this->appid
		(isset($this->query['appid'])) && $this->appid = $this->query['appid'];
		(isset($this->data['appid'])) && $this->appid = $this->data['appid'];

		if ( in_array($this->route['action'], $this->extra) ) {  // 无需验证 token
			return null;
		}

		// $this->token
		(isset($this->query['access_token'])) && $this->token = $this->query['access_token'];
		(isset($this->data['access_token'])) && $this->token = $this->data['access_token'];


		// 校验Token
		if ( $this->token === null ) {
			// echo json_encode(['errcode'=>403, 'errmsg'=>'缺少access_token信息']);
			throw new Excp("缺少access_token信息", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;
		}

		// 校验Token 是否有效
		if ( $this->api->isTokenEffect($this->appid, $this->token) === false ){
			// echo json_encode(['errcode'=>403, 'errmsg'=>'access_token不正确或已过期']);
			throw new Excp("access_token不正确或已过期", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;
		}



	}
}
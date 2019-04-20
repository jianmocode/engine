<?php
/**
 * MINA Gateway 基类
 * 
 * @package      \Mina\Gateway
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Gateway;
use Mina\Gateway\Obj as MinaObject;
use \Exception;


class Base  {

	/**
	 * 配置选项
	 * @var array
	 */
	protected $options = [];

	/**
	 * 信息缓存
	 * @var null
	 */
	protected $cache = null;

	/**
	 * 应用详情
	 * @var array
	 */
	protected $app = [];

	/**
	 * 网关转发的请求参数
	 * @var array
	 */
	protected $params = [];


	/**
	 * 当前访问的控制器
	 * @var null
	 */
	protected $ctr = null;


	/**
	 * 当前访问的action
	 */
	protected $act = null;


	/**
	 * 应用控制器返回结果
	 * @var array
	 */
	protected $response = [];


	/**
	 * 共享用户信息
	 */
	protected $user = [];

	/**
	 * 禁止访问控制器
	 * @var array
	 */
	protected $block = [];

	
	/**
	 * 应用网关
	 * @param array $options 配置选项
	 *               bool  ['sign'] 是否启用签名鉴权，启用签名更安全, 默认为flase 不启用。
	 *               array ['notallowed'] 不向应用层传递的系统参数 默认值为 ["a", "c", "n", "app_name", "app_c", "app_a"]
	 *               array ['notaccessable'] 不可访问的静态文件后缀，默认为 ['php','exe','sh', '/package.json', '/controller', '/model', '/view', '/api']
	 *               bool  ["nocache"] 缓存开关 默认为 false, 开启缓存
	 *      		 array   ["cache"]   缓存配置选项
	 *      		     string  ["cache"]["engine"] 引擎名称 有效值 Redis/Apcu, 默认为 null, 不启用缓存。
	 *      		     string  ["cache"]["prefix"] 缓存前缀，默认为空
	 *      		     string  ["cache"]["host"] Redis 服务器地址  默认 "127.0.0.1"
	 *      		        int  ["cache"]["port"] Redis 端口 默认 6379
	 *      		     string	 ["cache"]["passwd"] Redis 鉴权密码 默认为 null
	 *      		        int  ["cache"]["db"] Redis 数据库 默认为 1
	 *      		        int  ["cache"]["timeout"] Redis 超时时间, 单位秒默认 10
	 *      		        int	 ["cache"]["retry"] Redis 链接重试次数, 默认 3
	 */
	function __construct( $options = [] ) {

		$this->options = $options;

		// Service Root 目录
		$this->options['seroot'] = !empty($this->options['seroot']) ? $this->options['seroot'] : '/code/service';

		// 是否启用签名鉴权 
		$this->options['sign'] =  array_key_exists('sign', $this->options) ? $this->options['sign'] : false;

		// 屏蔽的 Query 参数
		$this->options['notallowed'] = !empty($this->options['notallowed']) ? $this->options['notallowed'] : [
			"a", "c", "n", "app_org", "app_name", "app_c", "app_a"
		];

		// 屏蔽的静态文件后缀
		$this->options['notaccessable'] = !empty($this->options['notaccessable']) ? $this->options['notaccessable'] : [
			'php','exe','sh', '/package.json', '/controller', '/model', '/view', '/api'
		];

		// 是否关闭缓存
		$this->options['nocache'] = array_key_exists('nocache', $this->options) ? $this->options['nocache'] : false;

		// 指定访问用户
		if ( !empty($this->options['user']) ) {
			$this->user = $this->options['user'];
		}

		$cacheOptions = !empty($this->options['cache']) ? $this->options['cache'] : [];
		if (!empty($cacheOptions['engine'])) {
			
			// 默认缓存前缀
			$cacheOptions['engine']['prefix'] = empty($cacheOptions['engine']['prefix']) ? 'GATEWAY:' :  $cacheOptions['engine']['prefix'];

			$cacheClassName = "\\Mina\\Cache\\{$cacheOptions['engine']}";
			if ( class_exists($cacheClassName) ) {
				$this->cache = new $cacheClassName( $cacheOptions );
			}
		}

	}


	/**
	 * 检查文件是否可访问
	 * @param  [type]  $path [description]
	 * @return boolean       [description]
	 */
	function isAccessable( $path ) {
		$pi = pathinfo($path);

		$resp = array_filter( $this->options['notaccessable'], function( $name ) use($pi, $path ) {

			if ( strpos($name, $pi['dirname']) === 0 ) {
				return $name;
			}

			if ( $pi['extension'] == $name ) {
				return $name;
			}

			if ( $path == $name ) {
				return $name;
			}

		});

		return empty($resp);
	}



	/**
	 * 清除缓存
	 * @return [type] [description]
	 */
	function clean() {
		if ( method_exists($this->cache, 'delete') ) {
			$this->cache->delete('');
		}

		return $this;
	}


	/**
	 * 解析应用名称
	 * @param  mix $org_app 
	 *         		org_name/app_name
	 *         		["app"=>'app_name', "org"=>"org_name"]
	 *         		["org_name",'app_name']
	 * @return array ["org_name",'app_name']
	 */
	function parseName( $org_app ) {
		if ( is_string($org_app) ) {
			$arr = explode('/', $org_app);
			$app = $org = $arr[0];
			if ( count($arr) >= 2 ) {
				$org = $arr[0];
				$app = $arr[1];
			}

			return [$org, $app];
		} else if ( is_array($org_app) ) {
			$org = array_key_exists('org', $org_app) ? $org_app['org'] : $org_app[0];
			$app = array_key_exists('app', $org_app) ? $org_app['app'] : $org_app[1];
			return [$org, $app];
		}

		throw new Exception("非法应用该名称", 402 );
	}


	/**
	 * 调取应用
	 * @param  mix $org_app 应用名称 格式为 org_name/app_name  或 ["app"=>'app_name', "org"=>"org_name"] 或 ["org_name",'app_name']
	 * @param  function  $getApp  读取应用详情函数
	 * @param  boolean $nocache 缓存开关，默认false 启用缓存
	 * @return $this
	 */
	function load( $org_app, $getApp, $nocache = false ) {

		if ( empty($org_app) ) {
			throw new Exception("未提供应用信息", 402 );
		}

		$a = $this->parseName($org_app);
		$cname = "info:" . implode('_',$a);

		// 从缓存中读取应用信息
		if ( $this->options['nocache'] !== true && $this->cache != null && $nocache !== true ) {
			$app = $this->cache->getJSON($cname);
			if ( $app !== false ) {
				$this->app == $app;
				return $this;
			}
		}

		if ( !is_callable($getApp)  ) {
			throw new Exception("请指定应用读取方式", 402);
		}

		$this->app = $getApp( $a );
		if ( empty($this->app) ) {
			throw new Exception("未找到任何应用信息", 404);
		}

		// 更新缓存信息
		if (  $this->cache != null ) {
			$this->cache->setJSON($cname, $this->app);
		}

		return $this;
	}



	/**
	 * 过滤掉不允许传递的字符串
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	protected function filter( $data ) {

		$notallowed = $this->options['notallowed'];
		return array_filter($data, function($var) use ( & $data , $notallowed ){
			 $ret = !in_array( key($data), $notallowed );
			 next($data);
			 return $ret;
		});
	}

	/**
	 * 普通初始化
	 * @return [type] [description]
	 */
	function init() {

		$this->setRouter($_GET['app_c']);
		$this->setRouter($_GET['app_a']);
		$this->setParams( $this->filter($_GET), $_POST, $_FILES, file_get_contents("php://input") );

		return $this;
	}


	/**
	 * 命令行初始化
	 * @return [type] [description]
	 */
	function cli() {
		return $this;
	}


	function setRouter( $ctr=null, $act=null) {
		if (!empty($ctr)) {
			$this->ctr = $ctr;
			$this->params['header'][]= "Xpmse-Controller: {$ctr}";
		}
		if ( !empty($act)) {
			$this->act = $act;
			$this->params['header'][]= "Xpmse-Action: {$act}";
		}


		if ( $this->isBlocked($ctr, $act) ) {
			throw new Exception("控制器禁止访问 ( {$ctr}::{$act} )", 403);
		}

		return $this;
	}


	function run( $ctr=null, $act=null ) {
		$this->setRouter($ctr, $act);
		return $this;
	}



	/**
	 * 读取用户IP 地址
	 * @return [type] [description]
	 */
	public function getClientIP() {
		$client_ip = null;
		if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$client_ip = $_SERVER["HTTP_CLIENT_IP"];
		}  else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) { 
			$client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else if(!empty($_SERVER["REMOTE_ADDR"])) {
			$client_ip = $_SERVER["REMOTE_ADDR"];
		}
		return $client_ip;
	}

	public function getHost() {
		return !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];
	}


	public function getProto() {

		if ( PHP_SAPI === 'cli' ) {
			return '';
		}

		$proto ='http:';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
			$proto = 'https:';
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
			$proto = 'https:';
		}
		elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
			$proto = 'https:';
		}
		return $proto;
	}


	public function getHome() {

		if ( PHP_SAPI === 'cli' ) {
			return $_SERVER['HOME'];
		}

		return $this->getProto() . "//" . $this->getHost();
	}

	public function getLocation() {
		
		if ( PHP_SAPI === 'cli' ) {
			return $_SERVER['PWD'];
		}

		return $this->getHome() .  $_SERVER['REQUEST_URI'];
	}


	public function getAppPath( $ctrl='', $act=''  ) {
		$path =  "/{$this->app['org']}/{$this->app['name']}";
		if ( !empty($ctrl) && !empty($act) ) {
			$path .= "/$ctrl/$act";
		}

		return $path;
	}



	/**
	 * 设定参数
	 * @param array  $query [description]
	 * @param array  $data  [description]
	 * @param array  $files [description]
	 * @param [type] $raw   [description]
	 */
	function setParams( $query=[], $data=[], $files=[], $raw=null ) {

		@session_start();

		if ( empty($this->app) ) {
			throw new Exception("应用尚未初始化", 500);
		}

		// 处理上传文件
		if ( isset($files) && count($files) > 0 ) {
			foreach ($files as $idx => $fn ) {
				if ( !$files[$idx]['error'] ||  $files[$idx]['tmp_name'] != "" ) {
					$files[$idx]['content'] =  base64_encode(file_get_contents($fn['tmp_name']));
				}
			}
		}


		// 原 appInput
		$this->params['data'] = [
			"get" => $query,
			"post" => $data,
			"files" => $files,
			"input" => $raw,
			"user" => $this->user,  // 管理员资料
			"argv"  => $_SERVER['argv'], 
			"argc" => $_SERVER['argc']
		];


		$this->params['header'] = [
			"Xpmse-phpsapi: " . PHP_SAPI,
			"xpmse-Cookie: " . $_SERVER['HTTP_COOKIE'],
			"CLIENT-IP: " . $this->getClientIP(),
			"X-FORWARDED-FOR: ". $this->getClientIP(),
			"Xpmse-Host: " . $this->getHost(),
			"Xpmse-Useragent: {$_SERVER['HTTP_USER_AGENT']}",
			"Xpmse-Appid: {$this->app['appid']}",
			"Xpmse-Apporg: {$this->app['org']}",
			"Xpmse-Appname: {$this->app['name']}",
			"Xpmse-Appalias: {$this->app['alias']}",
			"Xpmse-Appalign: {$this->app['align']}",
			"Xpmse-Url". $this->getHome(),
			"Xpmse-Home: ". $this->getHome(). '/_a/i'. $this->getAppPath(),
			"Xpmse-Noframe: ". $this->getHome() . '/_a/n'. $this->getAppPath(),
			"Xpmse-Static: ". $this->getHome() . '/s'. $this->getAppPath(),
			"Xpmse-Location: ". $this->getLocation(),
			"Xpmse-Sid: " . session_id(),
			"Xpmse-Service: " . $this->options['seroot'],
			"Xpmse-Vhost: " . __VHOST_NAME,
			"Xpmse-Cluster: ".__CLUSTER,
			"Xpmse-Multiple: ".__MULTIPLE,
			"Cli-User: {$_SERVER['USER']}",
			"Cli-Group: {$_SERVER['GROUP']}",
			"Cli-Pwd: {$_SERVER['PWD']}"
		];

		// 计算签名
		if ( $this->options['sign'] == true ) {
			$this->sign();
		}

		// print_r( $this->params['header'] );
		return $this;
	}


	/**
	 * 加入阻止名单
	 * @param  [type] $ctr [description]
	 * @param  [type] $act [description]
	 * @return [type]      [description]
	 */
	public function isBlocked( $ctr, $act ) {
		if ( $this->app['block'][$ctr][$act] == true ){
			return true;
		} 
		return false;
	}


	/**
	 * 计算签名
	 * @return 
	 */
	public function sign() {

		if ( empty($this->app['apikey']) ) {
			throw new Exception("未设定 API Key ", 404 );
		}

		if ( empty( $this->app['secret']) ) {
			throw new Exception("未设定 API Secret", 404 );
		}

		$t =  time();
		$n =  $this->genString( 8 );

		$query = array_merge($this->params['data']['get'], $this->params['data']['post']);
		foreach($query as &$datum) if($datum===null) $datum='';
		ksort($query);

		$query_string = http_build_query($query);
		$origin = $t . $n . $query_string.  $this->app['secret'];
		
		// echo $origin . "\n";

		$sign = sha1($origin);

		$this->params['header'][] = 'Xpmse-Signature: ' . $sign;
		$this->params['header'][] = 'Xpmse-Time: ' . $t;
		$this->params['header'][] = 'Xpmse-Nonce: ' . $n;
		$this->params['header'][] = 'Xpmse-Apikey: ' . $this->app['apikey'];

	}


	/**
	 * 生成随机数
	 * @param  integer $length [description]
	 * @return [type]          [description]
	 */
	public function genString( $length=16 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
		  $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

}
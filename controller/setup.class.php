<?php
/**
 * XpmSE安装器
 */
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );


// XpmSE配置文件存放位置
$GLOBALS['_XPMSE_CONFIG_ROOT'] = getenv('_XPMSE_CONFIG_ROOT');
$GLOBALS['_XPMSE_CONFIG_ROOT'] = ( !empty($GLOBALS['_XPMSE_CONFIG_ROOT']) ) ?  $GLOBALS['_XPMSE_CONFIG_ROOT'] :  dirname(__DIR__) . DS . 'config';


class setupController extends coreController
{

	private $require_modules = [
		'Core','date','libxml','openssl','pcre','sqlite3','zlib','bcmath','bz2','calendar','ctype','curl',
		'dba','dom','hash','fileinfo','filter','ftp','gd','gettext','SPL','iconv','json','mbstring','mcrypt',
		'session','standard','pcntl','mysqlnd','PDO','pdo_mysql','pdo_sqlite','Phar','posix','readline',
		'Reflection','mysqli','shmop','SimpleXML','soap','sockets','exif','sysvmsg',
		'sysvsem','sysvshm','tokenizer','wddx','xml','xmlreader','xmlrpc','xmlwriter',
		'zip','cgi-fcgi','redis','Zend OPcache'
	];


	function __construct() {
		// 载入默认的
		parent::__construct();
		$this->systemCheck();
		@session_start();
	}


	function getCurl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}

	

	// 安装界面
	function install() {

		// 保存会话状态信息
		$steps = (isset($_SESSION['_steps'])) ? $_SESSION['_steps'] : $this->initCheck();
		if ( isset($_GET['force']) ) {
			$steps = $this->initCheck();
		}
		

		$name = (isset($_GET['s']))? trim($_GET['s']) : current($steps);

		if ( count( $steps) <= 0 ) {
			$this->errorpage('XpmSE已安装', 'XpmSE已安装，如需重新安装请删除 service.lock、config.json 和 default.inc.php');
			exit; die();
		}

		if ( !in_array($name, array_merge($steps, ['install']) ) ) {
			$this->errorpage('非法请求', '非法请求来源');
			exit; die();
		}

		$_SESSION['_steps'] = $steps;

		// 需要生成配置文件
		if ( in_array($name, ['sys']) ){
			$this->genConfigFile();
		}


		$index = array_flip($steps);
		$idx = $index[$name];

		$next = null;
		if ( isset($steps[$idx+1]) ) {
			$next = $steps[$idx+1];
		}

		$prev = null;
		if ( isset($steps[$idx-1]) ) {
			$prev = $steps[$idx-1];
		}

		$total = count($steps);
		$curr  = $idx + 1;


		$proto ='http://';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https://';
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
         	$proto = 'https://';
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
            $proto = 'https://';
        }

		$home_root =  ( strlen(dirname($_SERVER['DOCUMENT_URI'])) > 1 ) ? dirname($_SERVER['DOCUMENT_URI']): '';
        $domain = $_SERVER["SERVER_NAME"];
        $home =  $proto . $_SERVER["HTTP_HOST"]. $home_root;


		$data = [
			'next'=>$next,
			'prev'=>$prev,
			'curr'=>$name,
			'total'=>$total,
			'currIdx' => $curr,
			'home_root'=>$home_root,
			'domain' => $domain,
			'proto' => $proto,
			'home'=> $home,
			'homev2' => '//'. $_SERVER["HTTP_HOST"]. $home_root
		];

		render( $data, 'setup/install', $name);

		// echo "<pre>";
		// print_r($_SESSION);
		// echo "</pre>";
	}




	/**
	 * 各种服务配置测试
	 * @return [type] [description]
	 */
	function tryit() {

		$se = (isset($_GET['se']) )? trim($_GET['se']) : 'redis';

		$method = null;

		if ( method_exists($this, "{$se}Check")) {
			$method = "{$se}Check";
		}


		if ($method == null ) {
			echo json_encode(['code'=>500, 'message'=>'非法请求', 'extra'=>$_POST]);
			exit;	
		}	


		$resp = $this->$method( $_POST );
		if ( $resp !== true ) {
			echo json_encode($resp);
			exit;
		}

		echo json_encode(['code'=>1, 'message'=>'success']);
	}


	/**
	 * 各种服务配置临时存盘
	 * @return [type] [description]
	 */
	function saveit() {
		$se = (isset($_GET['se']) )? trim($_GET['se']) : 'redis';

		$method = null; $checkMethod = null;
		if ( method_exists($this, "{$se}Save")) {
			$method = "{$se}Save";
		}


		if ( method_exists($this, "{$se}Check")) {
			$checkMethod = "{$se}Check";
		}


		if ($method == null || $checkMethod == null ) {
			echo json_encode(['code'=>500, 'message'=>'非法请求', 'extra'=>$_POST]);
			exit;	
		}

		// 校验服务器配置
		$warning = null;
		$resp = $this->$checkMethod( $_POST );
		if ( $resp !== true ) {
			if  ( isset($resp['code']) && $resp['code'] == 201 ) {
				$warning = $resp;
			} else {
				echo json_encode($resp);
				exit;
			}
		}

		// 保存配置
		$resp = $this->$method( $_POST );
		if ( $resp !== true ) {
			echo json_encode($resp);
			exit;
		}

		if  ($warning == null ) {
			echo json_encode(['code'=>1, 'message'=>'success']);
		} else {
			echo json_encode($warning);
		}
	}


	/**
	 * 读取数据
	 * @param  [type] $service_name [description]
	 * @param  [type] $key          [description]
	 * @param  [type] $default      [description]
	 * @return [type]               [description]
	 */
	static function V( $service_name, $key, $default=null ) {
		if ( !isset($_SESSION["_setup:$service_name"]) ) {
			return $default;
		}

		if ( isset($_SESSION["_setup:$service_name"][$key]) ) {
			return $_SESSION["_setup:$service_name"][$key];
		}
		return $default;
	}

	function test() {
		sleep(4);
		echo json_encode(['code'=>1, 'status'=>'done','message'=>'安装完毕']);
	}


	/**
	 * 保存 Redis服务器信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function redisSave( $option ) {
		$_SESSION['_setup:redis'] = $option;
		return true;
	}

	/**
	 * 保存 Storage 信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function storSave( $option ) {
		$_SESSION['_setup:stor'] = $option;
		return true;
	}


	/**
	 * 保存 SuperTable 信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function tabSave( $option ) {

		// 是否清空已有数据
		$option['format'] = ( !isset($option['format']) ) ? '0' : $option['format'];
		$_SESSION['_setup:tab'] = $option;

		return true;
	}


	/**
	 * 保存应用引擎信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function appSave( $option ) {
		$option['auto'] = ( isset($option['auto']) ) ? $option['auto'] : 'off';
		$_SESSION['_setup:app'] = $option;
		return true;
	}


	/**
	 * 保存应用配置信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function sysSave( $option ) {

		// 兼容老版本
		unset($option['logo']);
		$_SESSION['_setup:sys'] = $option;
			
		// 重新生成配置文件
		$this->genConfigFile();
		return true;
	}



	/**
	 * 保存管理员账号信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function userSave( $option ) {

		$_SESSION['_setup:user'] = $option;
		// 重新生成配置文件
		$this->genConfigFile();

		return true;
	}

		


	// 检查 default.inc.php 配置
	protected function constCheck( $option ) {
	}


	/**
	 * 校验 Redis 服务器是否可用
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function redisCheck( $option ) {
		
		$ip = $host = (isset($option['host']))? trim($option['host']) : null;
		$port = (isset($option['port']))? $option['port'] : null;
		$passwd = (isset($option['password']))? $server['password'] : null;
		// $user = (isset($option['user']))? $server['user'] : null;

		if ( $host == null || $port == null ){
			return ['code'=>500, 'message'=>'非法请求 ( Host/Port不能为空 )', 'extra'=>$option];
		}

		// 域名解析
		if ( !preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip) ) {
			$ip = @gethostbyname($host);
			if ( !preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip ) ) {
				$option['ip'] = $ip;
				return ['code'=>500, 'message'=>'连接失败 ( '.$host.' 无法解析 )', 'extra'=>$option];
			}
		}



		// 连接校验
		$option['ip'] = $ip;
		$redis = new Redis();
		try {
			$resp = $redis->connect( $ip, $port, 1.0, NULL, 500);
		} catch ( RedisException  $e ){
			$message = $e->getMessage();
			return ['code'=>500, 'message'=>'连接失败 ( '. $message . ' )'  , 'extra'=>$option];
		}

		if ( $resp === false ){  // 连接失败
			try {
				$err = $redis->getLastError();
			} catch ( RedisException  $e ){
				$message = $e->getMessage();
				return ['code'=>500, 'message'=>'连接失败 ( '. $message . ' )'  , 'extra'=>$option];
			}

			return ['code'=>500, 'message'=>'连接失败 ( '. $err  . ') ', 'extra'=>$option];
		}


		// 权限验证
		if ( $password != null ) {
			try {
				if ( $redis->auth($password) === false ) {
					return ['code'=>503, 'message'=>'密码错误' , 'extra'=>$option];
				} 
		    } catch ( RedisException  $e ){
				$message = $e->getMessage();
				return ['code'=>500, 'message'=>'密码校验失败 ( '. $message . ' )' , 'extra'=>$option];
			}
		}

		return true;
	}


	/**
	 * 校验存储配置
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function storCheck( $option ) {

		return true;

		$public_home = (isset($option['public_home']))? trim($option['public_home']) : null;
		$public_root = (isset($option['public_root']))? trim($option['public_root']) : null;
		$private_root = (isset($option['private_root']))? $option['private_root'] : null;
		$composer = (isset($option['composer']))? $option['composer'] : null;
		$engine = (isset($option['engine']))? $server['engine'] : null;


		$proto ='http://';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https://';
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
         	$proto = 'https://';
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
            $proto = 'https://';
        }


        $home_root =  ( strlen(dirname($_SERVER['DOCUMENT_URI'])) > 1 ) ? dirname($_SERVER['DOCUMENT_URI']): '';
        $domain = $_SERVER["SERVER_NAME"];
        $home =  $proto . $_SERVER["HTTP_HOST"]. $home_root;
        // $home = $home_root;

        $public_home = $home .  '/static-file';

		if ( $public_home == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 访问地址不能为空 )', 'extra'=>$option];
		}

		if ( $public_root == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 公开目录不能为空 )', 'extra'=>$option];
		}

		if ( $private_root == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 私密目录不能为空 )', 'extra'=>$option];
		}


		if ( !is_dir($public_root) ) {
			return ['code'=>404, 'message'=>'公开目录不存在 ( ' . $public_root . ' )', 'extra'=>$option];
		}

		if ( !is_dir($private_root) ) {
			return ['code'=>404, 'message'=>'私密目录不存在 ( ' . $private_root . ' )', 'extra'=>$option];
		}

		if ( !is_dir($composer) ) {
			return ['code'=>404, 'message'=>'Composer 目录不存在 ( ' . $composer . ' )', 'extra'=>$option];
		}


		if ( !is_writable($public_root) ) {
			return ['code'=>403, 'message'=>'公开目录不可写入 ( ' . $public_root . ' )', 'extra'=>$option];
		}

		if ( !is_writable($private_root) ) {
			return ['code'=>403, 'message'=>'私密目录不可写入 ( ' . $private_root . ' )', 'extra'=>$option];
		}

		// if ( !is_writable($composer) ) {
		// 	return ['code'=>403, 'message'=>'Composer 目录不可写入 ( ' . $composer . ' )', 'extra'=>$option];
		// }

		// 校验访问地址
		$now = time();
		$code = $now . rand(10000,99999);
		$name = "storcheck_{$now}.txt";
		@file_put_contents( "$public_root/$name", $code);
		
		// $check_code = @file_get_contents("$public_home/$name", false,stream_context_create([
  //  			"http"=>['method'=>"GET",'timeout'=>1],
  //  			"https"=>['method'=>"GET",'timeout'=>1]
  //  		]));
   		$check_code = $this->getCurl("$public_home/$name" );

		// @unlink( "$public_root/$name" );
		if ($check_code != $code ) {
			$option['check_code'] = $check_code;
			$option['code'] = $code;
			$option['public_home'] = $public_home;
			$option['name'] = $name;
			return ['code'=>403, 'message'=>'访问地址不正确 ( ' . $public_home . ' )', 'extra'=>$option];
		}


		return true;
	}



	/**
	 * 校验 SuperTable 配置
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function tabCheck( $option ) {

		$es_engine = (isset($option['es_engine']))? trim($option['es_engine']) : null;
		$es_host = (isset($option['es_host']))? trim($option['es_host']) : null;
		$es_port = (isset($option['es_port']))? trim($option['es_port']) : null;
		
		// if ( $es_host == null ) {
		// 	return ['code'=>500, 'message'=>'非法请求 ( ElasticSearch 服务 Host 不能为空 )', 'extra'=>$option];
		// }

		// if ( $es_port == null ) {
		// 	return ['code'=>500, 'message'=>'非法请求 ( ElasticSearch 服务 Port 不能为空 )', 'extra'=>$option];
		// }

		// // 校验服务器
		// $es_text = @file_get_contents("http://{$es_host}:{$es_port}", false,  stream_context_create(['method'=>"GET",'timeout'=>1]) );
		// $es_data = json_decode($es_text, true);

		// if ( !is_array($es_data) || !is_array($es_data['version'])   ) {
		// 	$option['es_text'] = $es_text;
		// 	$option['es_data'] = $es_data;
		// 	return ['code'=>403, 'message'=>'无法访问 ElasticSearch 服务 ( http://' . $es_host . ':'. $es_port . ' )', 'extra'=>$option];
		// }

		// if ( $es_data['version']['number'] != '1.7.3' ){
		// 	$option['es_text'] = $es_text;
		// 	$option['es_data'] = $es_data;
		// 	return ['code'=>403, 'message'=>'不支持的 ElasticSearch 版本 ( ' . $es_data['version']['number'] . ' )', 'extra'=>$option];
		// }


		// 校验 MySQL 
		$st_engine = (isset($option['st_engine']))? trim($option['st_engine']) : null;
		$st_host = (isset($option['st_host']))? trim($option['st_host']) : null;
		$st_port = (isset($option['st_port']))? trim($option['st_port']) : 3306;
		$st_user = (isset($option['st_user']))? trim($option['st_user']) : null;
		$st_pass = (isset($option['st_pass']))? trim($option['st_pass']) : null;
		$st_dbname = (isset($option['st_dbname']))? trim($option['st_dbname']) : null;
		$st_prefix= (isset($option['st_prefix']))? trim($option['st_prefix']) : null;

		if ( $st_host == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL Host 不能为空 )', 'extra'=>$option];
		}

		if ( $st_port == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL Port 不能为空 )', 'extra'=>$option];
		}

		if ( $st_user == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL User 不能为空 )', 'extra'=>$option];
		}

		if ( $st_dbname == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL 数据库名称不能为空 )', 'extra'=>$option];
		}

		// 校验 MySQL 连接
		$mysqli = @new mysqli($st_host, $st_user, $st_pass, $st_dbname, $st_port);
		if (mysqli_connect_error()) {
		    $message = 'Connect Error ' . mysqli_connect_errno() . ': ' . mysqli_connect_error();
		    return ['code'=>500, 'message'=>'MySQL 服务连接失败 ( '. $message . ' )'  , 'extra'=>$option];
		}

		if ($result = $mysqli->query("SHOW TABLES")) {
		    $tabs = []; $warning  = null;
		    while ($rs = $result->fetch_array()){
		        $tab = $rs[0];
		        if ( strpos( $tab, $st_prefix) === 0 ) {
		        	$warning = ['code'=>201, 'message'=>"但已存在前缀为 {$st_prefix} 的数据表 {$tab}, 建议更换数据表前缀。", 'extra'=>$option];
		        }
		    }

		    $result->close();
		    if ( $warning  !== null ) {
		    	return $warning ;
		    }
		}

		return true;
	}

	/**
	 * 校验应用引擎配置
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function appCheck( $option ) {

		$home = (isset($option['home']))? trim($option['home']) : null;
		$root = (isset($option['root']))? trim($option['root']) : null;

		$warning = null;
		
		if ( $home == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 访问地址不能为空 )', 'extra'=>$option];
		}

		if ( $root == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 应用目录不能为空 )', 'extra'=>$option];
		}

		if ( !is_dir($root) ) {
			return ['code'=>404, 'message'=>'应用目录不存在', 'extra'=>$option];
		}


		// 校验 Host 
		$effectHome = null;
		$host = parse_url($home, PHP_URL_HOST);
		$ip = @gethostbyname($host);
		if ( !preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip ) ) {
			$option['ip'] = $ip;
			$option['host'] = $host;
			return ['code'=>500, 'message'=>'访问地址无法解析 ( '.$host.' 无法解析 )', 'extra'=>$option];
		}

		if ( !is_readable($root) ) {
			return  ['code'=>500, 'message'=>'无法访问应用目录 ( '. $root . ' )', 'extra'=>$option];
		}


		if ( !is_writable($root) ) {
			$warning = ['code'=>201, 'message'=>'应用目录不可写，将无法自动下载应用', 'extra'=>$option];
			
			$apptest = null;
			// 校验访问地址
			$hd = opendir($root);
			while (($dir = readdir($hd)) !== false)  {
				if (!preg_match('/^([0-9a-zA-Z_]+)$/', $dir, $match) ) {
					continue;
				}

				if ( file_exists("$root/$dir/package.json") ) {
					$apptest = "$home/$dir/package.json";
					break;
				}
			}

			if ( $apptest != null ) {
				// $json_text = @file_get_contents("$apptest", false, stream_context_create([
			 //   		"http"=>['method'=>"GET",'timeout'=>1],
			 //   		"https"=>['method'=>"GET",'timeout'=>1]
			 //   	]));

			   	$json_text = $this->getCurl("$apptest");

				$json_data = json_decode($json_text, true);
				if ( !isset($json_data['name']) ) {
					$option['json_text'] = $json_text;
					$option['json_data'] = $json_data;
					return ['code'=>403, 'message'=>'访问地址与应用目录不匹配 ( ' . $root . ' )', 'extra'=>$option];
				}
			} else {
				$warning = ['code'=>201, 'message'=>'应用目录不可写，无法验证访问地址；这将导无法自动下载应用', 'extra'=>$option];
			}

		} else {
			// 校验访问地址
			$now = time();
			$code = $now . rand(10000,99999);
			$name = "HomeCheck_{$now}.php";
			@file_put_contents( "$root/$name", '<?php echo json_encode($_SERVER); ?>');
			// $json_text = @file_get_contents("$home/$name", false,  stream_context_create(['method'=>"GET",'timeout'=>1]) );
			$json_text = $this->getCurl("$home/$name");


			@unlink( "$root/$name" );
			$json_data = json_decode($json_text, true);

			if ( !isset($json_data['DOCUMENT_ROOT']) || $json_data['DOCUMENT_ROOT'] != $root ) {
				$option['json_text'] = $json_text;
				$option['json_data'] = $json_data;
				return ['code'=>403, 'message'=>'访问地址与应用目录不匹配 ( ' . $root . ' )', 'extra'=>$option];
			}

		}

		// 返回结果
		if ( $warning != null ) {
			return $warning;
		}

		return true;
	}


	// 系统配置检查
	protected function sysCheck( $option ) {

		$name = (isset($option['name']))? trim($option['name']) : null;
		$short = (isset($option['short']))? trim($option['short']) : null;
		$company = (isset($option['company']))? trim($option['company']) : null;
		$logo_path = (isset($option['logo_path']))? trim($option['logo_path']) : null;


		

		if ( empty($name) ) {
			return ['code'=>404, 'message'=>'未填写系统名称', 'extra'=>$option];
		}

		if ( empty($short) ) {
			return ['code'=>404, 'message'=>'未填写系统简称', 'extra'=>$option];
		}

		if ( empty($company) ) {
			return ['code'=>404, 'message'=>'未填写公司名称', 'extra'=>$option];
		}

		if ( empty($logo_path) ) {
			return ['code'=>404, 'message'=>'未上传系统图标', 'extra'=>$option];
		}

		if ( $logo_path != 'local://public::/media/defaults/favicon.tmp.png_fit' ) {
			return ['code'=>500, 'message'=>"非法请求(图标地址: logo_path={$logo_path})", 'extra'=>$option];	
		}

		return true;
	}


	// 管理员配置检查
	protected function userCheck( $option ) {

		$mobile = (isset($option['mobile']))? trim($option['mobile']) : null;
		$name = (isset($option['name']))? trim($option['name']) : null;
		$password = (isset($option['password']))? trim($option['password']) : null;
		$repassword = (isset($option['repassword']))? trim($option['repassword']) : null;
		

		if ( empty($mobile) ) {
			return ['code'=>404, 'message'=>'未填写手机号码', 'extra'=>$option];
		}

		if ( empty($name) ) {
			return ['code'=>404, 'message'=>'未填写真实姓名', 'extra'=>$option];
		}

		if ( empty($password) ) {
			return ['code'=>404, 'message'=>'未填写登录密码', 'extra'=>$option];
		}

		if ( $repassword != $password ) {
			return ['code'=>404, 'message'=>'两次输入的登录密码不一致', 'extra'=>$option];
		}

		return true;
	}

	

	protected function installCheck( $option ) {
		return true;
	}



	// 安装失败界面
	protected function errorpage( $title = 'XpmSE安装失败' , $message = "", $extra = [] ) {
		$message = ( empty($message) ) ? $_GET['message'] : $message;
		$title = ( empty($title) ) ? $_GET['title'] : $title;
		$data = [
			'title' =>  $title,
			'message'=> $message,
			'extra' => $extra
		];

		render( $data, 'setup/install', 'error');
		exit;
	}


	// 服务器系统环境检查
	protected function systemCheck() { // 这里有BUG 会导致死循环

		return true;

		$errors = [];
		$conf_root = $GLOBALS['_XPMSE_CONFIG_ROOT'];

		if ( !defined(_XPMSE_REVISION) || !file_exists(_XPMSE_CONFIG_FILE) ) {  
			if ( !is_writable($conf_root) ) {
				array_push($errors, "配置目录: {$conf_root}, 无写入权限。");
			}
		}

		$info = GetSysinfo();

		// 检查操作系统
		if ( $info['os'] != 'Linux' ) {
			array_push($errors, "操作系统: {$info['os']}, 请使用 Linux 操作系统。");
		}

		// 检查内存
		if ( $info['memory'] < 512000 ) {
			array_push($errors, "系统内存: {$info['memory']},  至少需要 524288。");
		}

		// 检查PHP版本
		if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		    array_push($errors, "PHP版本: {$info['phpversion']},  请使用 5.0 及以上版本。");
		}

		// 检查PHP扩展
		$needExts = [];
		foreach ($this->require_modules as $ext ) {
			if ( !in_array($ext, $info['exts']) ) {
				array_push($needExts, $ext );
			}
		}
		if (count($needExts) > 0 ) {
		    array_push($errors, "缺少PHP扩展: <b>" . implode(', ', $needExts) . '</b>' );
		}

		if ( count($errors) > 0 ) {
			$this->errorpage(
				"系统环境不符合要求",
				"以上问题导致XpmSE无法安装，请修正后重试",
				$errors
			);
		}

	}



	// XpmSE系统初始化情况检查
	protected function initCheck() {
		
		$steps = [];
		$conf_root = $GLOBALS['_XPMSE_CONFIG_ROOT'];
		
		@include_once("$conf_root/defaults.inc.php");

		if ( !defined('_XPMSE_REVISION') ) {  // 无 defaults.inc.php
			return ['law', 'redis', 'stor', 'tab', 'app', 'sys', 'user'];
		}

		if ( !file_exists(_XPMSE_CONFIG_FILE) )  {  // 无 config.json
			return ['law', 'stor', 'tab', 'app', 'sys', 'user'];
		}

		// XpmSE已安装
		if ( file_exists("$conf_root/service.lock") ) {
			return [];
		}


		// 读取配置信息
		$json_text = file_get_contents(_XPMSE_CONFIG_FILE);
		$json_data = json_decode($json_text, true);

		$needComplete = [];
		if ( isset($json_data['general']) ) {

			// Load Redis
			if ( isset($json_data['mem']['redis']) && is_array( $json_data['mem']['redis'] ) ) {
				$this->redisSave( $json_data['mem']['redis'] );
			} else {
				array_push($needComplete, 'redis' );
			}

			// Load Storage
			if ( 
					isset($json_data['storage']['local']['bucket']['public']) && is_array( $json_data['storage']['local']['bucket']['public'] )  && 
					isset($json_data['storage']['local']['bucket']['private']) && is_array( $json_data['storage']['local']['bucket']['private'] ) 
				) {
				$stor = $json_data['storage']['local']['bucket'];

				$this->storSave([
						'public_root' => $stor['public']['root'],
						'public_home' => $stor['public']['home'],
						'private_root' => $stor['private']['root']
					]);

			} else {
				array_push($needComplete, 'stor' );
			}
			
			// Load SuperTable
			if ( 
					isset($json_data['supertable']['storage']['option']) && is_array( $json_data['supertable']['storage']['option'] )  && 
					isset($json_data['supertable']['search']['option']['hosts']) && is_array($json_data['supertable']['search']['option']['hosts'] ) 
				) {

				$st = $json_data['supertable']['storage'];
				$es = $json_data['supertable']['search'];
				$es_hosts = end( $es['option']['hosts'] );
				$es_hostinfo = explode(':', $es_hosts);
				$es_host = $es_hostinfo[0];
				$es_port = $es_hostinfo[1];
				$st_host = end($st['option']['master']);

				$this->tabSave([
						'st_dbname' => $st['option']['db_name'],
						'st_host' => $st_host['host'],
						'st_port' => $st_host['port'],
						'st_user' => $st_host['user'],
						'st_pass' => $st_host['pass'],
						'st_prefix' => $st['prefix'],
						'es_index' => $es['index'],
						'es_host' => $es_host,
						'es_port' => $es_port,
					]);

			} else {
				array_push($needComplete, 'tab' );
			}

			// Load App
			if ( !empty($json_data['general']['apphost']) && defined('_XPMAPP_ROOT') ) {
				$this->appSave([
					'home' => $json_data['general']['apphost'],
					'root' => _XPMAPP_ROOT,
					'auto' => 'on'
				]);
			} else {
				array_push($needComplete, 'app' );
			}


			// Load Sys
			if ( !empty($json_data['general']['company']) && !empty($json_data['general']['name'])   && !empty($json_data['general']['short']) ) {
				
				$url = '';
				$path = 'local://public::/defaults/favicon.tmp.png_fit';
				if ( isset( $json_data['storage']['local']['bucket']['public']['home'])) {
					$home = $json_data['storage']['local']['bucket']['public']['home'];
					$url = $home . '/defaults/favicon.tmp.png_fit';
				} else {
					$path = '';
				}

				$this->sysSave([
					'name' => $json_data['general']['name'],
					'company' => $json_data['general']['company'],
					'short' => $json_data['general']['short'],
					'logo_path' => $path,
					'logo_url' =>  $url
				]);

			}

			return ['law', 'sys', 'user'];
		}

		return ['law', 'sys', 'user'];
	}



	// 生成系统配置文件
	protected function genConfigFile() {

		$conf_root = $GLOBALS['_XPMSE_CONFIG_ROOT'];
		
		// if ( !defined(_XPMSE_REVISION) ) {  // 无 defaults.inc.php
		if ( !file_exists("$conf_root/defaults.inc.php") ) {
			$defaultsinc = $this->defaultsInc( $conf_root );
			file_put_contents("$conf_root/defaults.inc.php", $defaultsinc );
			require_once("$conf_root/defaults.inc.php");
		}
		
		if ( !defined("_XPMSE_CONFIG_FILE") || !file_exists(_XPMSE_CONFIG_FILE) )  {  // 无 config.json
			$configjson  = $this->configJSON( $conf_root );
			file_put_contents("$conf_root/config.json", $configjson );
		}

	}



	// 生成 config.json 文件内容
	protected function configJSON( $configFileRoot = null, $logo = [] ) {

		if( !isset($_SESSION['_setup:redis']) ) {
			header('Location: /setup.php?s=redis');
			exit;
		}

		if( !isset($_SESSION['_setup:stor']) ) {
			header('Location: /setup.php?s=stor');
			exit;
		}

		if( !isset($_SESSION['_setup:app']) ) {
			header('Location: /setup.php?s=app');
			exit;
		}

		if( !isset($_SESSION['_setup:tab']) ) {
			header('Location: /setup.php?s=tab');
			exit;
		}


		$proto ='http://';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https://';
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
         	$proto = 'https://';
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
            $proto = 'https://';
        }


        $home_root =  ( strlen(dirname($_SERVER['DOCUMENT_URI'])) > 1 ) ? dirname($_SERVER['DOCUMENT_URI']): '';
        $domain = $_SERVER["SERVER_NAME"];
        $apihost = $home =  $proto . $_SERVER["HTTP_HOST"]. $home_root;
        $home = $home_root;


		// $data = [
		// 	'domain'=> $domain,
		// 	'homepage' => empty($home) ? '/' : $home,
		// 	'static' => $home . '/static',
		// 	'api' => $home . '/api/v1',
		// 	'appid' => gen_string(16),
		// 	'appsecret' => gen_string(32),
		// 	'redis' => $_SESSION['_setup:redis'],
		// 	'stor' => $_SESSION['_setup:stor'],
		// 	'app' => $_SESSION['_setup:app'],
		// 	'tab' => $_SESSION['_setup:tab'],
		// 	'sys' =>  isset($_SESSION['_setup:sys']) ? $_SESSION['_setup:sys'] : []
		// ];

		$data = [
			'domain'=> '',
			'homepage' => '',
			'static' => '/static',
			'api' => $apihost . '/api/v1',
			'appid' => gen_string(16),
			'appsecret' => gen_string(32),
			'redis' => $_SESSION['_setup:redis'],
			'stor' => $_SESSION['_setup:stor'],
			'app' => $_SESSION['_setup:app'],
			'tab' => $_SESSION['_setup:tab'],
			'sys' =>  isset($_SESSION['_setup:sys']) ? $_SESSION['_setup:sys'] : []
		];


		return render( $data, 'setup', 'config.json', true);
	}

	// 生成 defaults.inc.php 文件内容
	protected function defaultsInc( $configFileRoot = null ) {

		if( !isset($_SESSION['_setup:redis']) ) {
			header('Location: /setup.php?s=redis');
			exit;
		}

		if( !isset($_SESSION['_setup:stor']) ) {
			header('Location: /setup.php?s=stor');
			exit;
		}

		if( !isset($_SESSION['_setup:app']) ) {
			header('Location: /setup.php?s=app');
			exit;
		}

		$redis = $_SESSION['_setup:redis'];
		$stor = $_SESSION['_setup:stor'];
		$app = $_SESSION['_setup:app'];
		$data = [
			'XPMSE_DOMAIN'=> $_SERVER["SERVER_NAME"],
			'COMPOSER_ROOT' => $stor['composer'],
			'XPMSE_REDIS_HOST' => $redis['host'],
			'XPMSE_REDIS_PORT' => $redis['port'],
			'XPMSE_REDIS_PASSWD' => $redis['password'],
			'XPMAPP_ROOT' => $app['root'],
			'XPMSE_CONFIG_FILE' => ($configFileRoot == null) ? "dirname(__FILE__) . '/config.json'" : "'$configFileRoot/config.json'"
		];

		return render( $data, 'setup', 'defaults.inc.php', true);
	}

}

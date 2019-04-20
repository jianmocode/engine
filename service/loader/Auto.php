<?php
namespace Xpmse\Loader;
use \Xpmse\Loader\App as APP;
use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Secret as Secret;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
ini_set( 'display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');

/**
 * 框架载入
 */
class Auto {
	
	function __construct() {
	}

	public static function checkHeaders( & $headers, & $errmsg ) {
		$errmsg = "";
		$fixList = ['Controller','Action','Home','Noframe','Static', 'Appalign', 'Appname','Apporg','Appid','Useragent', 'Host', 'Phpsapi','Service','Sid','Location', 'Vhost', 'Cluster', 'Multiple']; 
		
		$checkList = ['Controller','Action','Home','Noframe','Static'];
		$ret = true;
		foreach ($checkList as $check) {
			if ( empty($headers["Xpmse-$check"]) ) {
				$errmsg .= "$check not set;";
				$ret = false;
			}
		}

		return $ret;
	}


	public static function run( $headers, $request=null ) {

		$headers_error = "";
		if ( self::checkHeaders($headers, $headers_error ) === false ) {

			$e = new Excp("Header Error ({$headers_error})", '500', ['headers'=>$headers, 'headers_error'=>$headers_error] );
			$e->log();
			
			echo json_encode([
				'code'=>500, 
				'message'=>$e->getMessage(), 
				'data'=>$e->getTrace()]
			);
			exit;
        }
        
		if ( empty($request) ) {

			$json = file_get_contents('php://input');
			// 请求信息
			$request = json_decode($json, true);
			if( json_last_error() !== JSON_ERROR_NONE) {
				$e = new Excp("Parse Error: " . json_last_error_msg(), '500', ['json'=>$json, 'headers_error'=>json_last_error_msg()] );
				$e->log();
				echo json_encode([
					'code'=>500,
					'message'=>$e->getMessage(), 
					'data'=>$e->getTrace()]
				);
				exit;
			}
		}

		if ( $request === null ) {
			$e =  new Excp("Parse Error: Unknown", '500');
			$e->log();
			echo json_encode([
				'result'=>false, 
				'message'=>$e->getMessage(), 
				'data'=>$e->getTrace()]
			);
			exit;
		}

		return self::load( $headers, $request );

	}

	


	/**
	 * 载入 Cookie
	 * @param  [type] $string [description]
	 * @return [type]         [description]
	 */
	public static function loadCookie( $string ) {
		$cookies = explode(';', $string);
		foreach ($cookies as $ck ) {
			$c = explode('=', $ck);
			if ( count($c) == 2 ) {
				$name = trim($c[0]);
				$GLOBALS['_COOKIE'][$name] = trim($c[1]);
			}
		}
	}

	public static function load( $headers, $data ) {

		if ( !defined('APP_ROOT') ) {
			throw new Excp('APP_ROOT未定义', 500, ['headers'=>$headers, 'data'=>$data]);
		}

		define('HOME', $headers['Xpmse-Url']);
		define('APP_HOME', $headers['Xpmse-Home']);
		define('APP_HOME_NOFRAME', $headers['Xpmse-Noframe']);
		define('APP_HOME_STATIC', $headers['Xpmse-Static']);
		define('APP_HOME_PORTAL', $headers['Xpmse-Portal']);
		define('APP_HOME_LOCATION', $headers['Xpmse-Location']);
		define('APP_SID', 'a'. $headers['Xpmse-Sid'] );
		define('FORWARDED_PHP_SAPI', $headers['Xpmse-Phpsapi']);

		// + 集群 Support
		if ( !defined('__VHOST_NAME') ) { define('__VHOST_NAME', $headers['Xpmse-Vhost']); }
		if ( !defined('__CLUSTER') ) { define('__CLUSTER', $headers['Xpmse-Cluster']); }
		if ( !defined('__MULTIPLE') ) { define('__MULTIPLE', $headers['Xpmse-Multiple']); }


		// 处理上传文件
		if ( isset($data['files']) && count($data['files']) > 0 ) {
			foreach ($data['files'] as $idx => $fn ) {
				if (isset($data['files'][$idx]['content']))  {
					$data['files'][$idx]['content'] = base64_decode($data['files'][$idx]['content']);
				}
			}
		}

		APP::$user = $data['user'];
		APP::$injections = []; // $data['_INJECTION'];  // 废弃
		APP::$headers = $headers;
		
		App::$HOME = HOME;
		APP::$APP_ROOT = APP_ROOT;
		APP::$APP_HOME = APP_HOME;
		APP::$APP_HOME_NOFRAME = APP_HOME_NOFRAME;
		APP::$APP_HOME_STATIC = APP_HOME_STATIC;
		APP::$APP_HOME_PORTAL = APP_HOME_PORTAL;
		APP::$APP_HOME_LOCATION = APP_HOME_LOCATION;

		$data["post"] = !empty($data["post"]) ? $data["post"] : [];
		$data["get"] = !empty($data["get"]) ? $data["get"] : [];

		$GLOBALS['_REQUEST'] = array_merge($data["get"],$data["post"]);
		$GLOBALS['_GET'] = $data['get'];
		$GLOBALS['_POST'] = $data['post'];
		$GLOBALS['_PHPINPUT'] =$data['input'];
		$GLOBALS['_FILES'] = $data['files'];
		$GLOBALS['_SERVER']['HTTP_USER_AGENT'] = $headers['Xpmse-Useragent'];
		$GLOBALS['_SERVER']['FROM_HOST'] = $headers['Xpmse-Host'];
		$GLOBALS['_SERVER']['HTTP_COOKIE'] = $headers['Xpmse-Cookie'];

		// 载入Cookie
		self::loadCookie( $headers['Xpmse-Cookie'] );

		$content_type = $headers['CONTENT_TYPE'];
		$gwt = $headers['Gateway-Type'];

		// 兼容旧版
		if ( empty($gwt) ) {
			if ( in_array($content_type,["application/api","application/noframe","application/portal"]) ) {
				$gwt = "http/transparent";
			} else {
				$gwt = "http/fetch";
			}
		}

		$method = str_replace('/', '', $gwt);
		$gw = new self();
		if ( !method_exists($gw, $method) ) {
			throw new Excp("处理器不存在 ($gwt)", '404');
		}

		// 获取控制器信息
		$c = ucwords(strtolower($headers['Xpmse-Controller']));
		$a = $headers['Xpmse-Action'];
		$class_name = $c;
		$class_file = APP_ROOT . "/controller/$c.php";
		if (!file_exists($class_file) ) { // 兼容LP3风格
			$lpc = strtolower($c);
			$class_file = "/controller/$lpc.class.php";
			// $class_name = $c."Controller";
		}

		@session_id( APP_SID );
        @session_start();
		return $gw->$method( $class_file, $class_name, $a, $data, $headers );

	}

	private function newInstance( $class_file, $c, $a) {

		if (!file_exists($class_file)) {
			$e =  new Excp("控制器文件不存在", '404', [
				'class_file'=>$class_file,  
				'controller'=>$c, 
				'action'=>$a
			]);
			echo $e->toJSON();
			// exit;
			return null;
		}

		try {
			include_once($class_file);
		} catch( Exception $e ) {
			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => ['class_file'=>$class_file]
			];

			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			// exit;
			return null;
		}

		$class_name = $c;
		if ( !method_exists($class_name, $a) ) {
			$class_name =  $c .'Controller';
		}

		if ( !method_exists($class_name, $a) ) {
			$e =  new Excp("控制器类或方法不存在", '404', [
				'class_file'=>$class_file,  
				'controller'=>$c, 
				'action'=>$a
			]);	

			echo $e->toJSON();
			// exit;
			return null;
		}

		try {
			$app = new $class_name();
		} catch( Exception $e ) {
			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => ['class_file'=>$class_file]
			];

			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			// exit;
			return null;
		}

		return $app;
	}

	/**
	 * 校验签名
	 * @return [type] [description]
	 */
	private function signatureIsEffect( & $headers, & $data ) {

		$appid = $headers['Xpmse-Apikey'];
		if ( empty($appid) ) {
			return false;
		}

		$sc = new Secret();
		$secret = $sc->getSecret( $appid );
		if ( empty($secret) ) {
			return false;
		}


		// 校验签名
		$t = trim($headers['Xpmse-Time']);
		$n = trim($headers['Xpmse-Nonce']);
		$query = array_merge($data['get'], $data['post']);
		foreach($query as &$datum) if($datum===null) $datum='';
		ksort($query);

		$query_string = http_build_query($query);
		$origin = $t . $n . $query_string.  $secret;
		$sign = sha1($origin);

		if ( $sign !== trim($headers['Xpmse-Signature']) ) {
			return $origin;
		}

		return true;
	}



	private  function httpFetch( $class_file, $c,  $a,  & $data, & $headers ) {

		// 校验请求签名是否合法
		$sign = $this->signatureIsEffect( $headers, $data );
		if (  $sign !== true ) {
			$error = [
				"code" => 403,
				"message" => "请求签名不正确",
				"extra" => [ "aaa"=>"HELLO"],
				"trace" =>[[
					'file'=>$class_file, 
					'line'=>0,
					'class' => $c,
					'function' => $a,
					"signature" => $headers['Xpmse-Signature'],
					"shoudbe" => $sign,
					"time" => $headers['Xpmse-Time'],
					"nonce" => $headers['Xpmse-Nonce'],
					"apikey" => $headers['Xpmse-Apikey'],
					"query" => array_merge($data['get'], $data['post'])
				]]
			];
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return;
		}

		$app = $this->newInstance( $class_file, $c, $a );
		if ( $app === null ) { return ; }

		if ( method_exists($app, 'init') ) {
			$app->init($data['user'],  $data['_INJECTION'], $headers );
		}

		try {

			ob_start();
			$response = $app->$a();
			$content = ob_get_contents();
			ob_end_clean();
			echo json_encode( [
				'code'=>0,
				'content'=>$content, 
				'data'=>$response 
			]);
			return $response;

		} catch( Excp $e ) {

			$content = ob_get_contents();
			ob_end_clean();

			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => [
					'content' => $content,
					'class_file'=>$class_file,  
					'controller'=>$c, 
					'action'=>$a
				]
			];

			$extra = $e->getExtra();
			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);

			return;

		} catch ( Exception  $e ){

			$content = ob_get_contents();
			ob_end_clean();

			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => [
					'content' => $content,
					'class_file'=>$class_file,  
					'controller'=>$c, 
					'action'=>$a
				]
			];

			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return;
		}

	}


	private  function httpTransparent( $class_file, $c,  $a,  & $data, & $headers ) {

		// 校验请求签名是否合法
		$sign = $this->signatureIsEffect( $headers, $data );
		if (  $sign !== true ) {
			$error = [
				"code" => 403,
				"message" => "请求签名不正确",
				"extra" => [ "aaa"=>"HELLO"],
				"trace" =>[[
					'file'=>$class_file, 
					'line'=>0,
					'class' => $c,
					'function' => $a,
					"signature" => $headers['Xpmse-Signature'],
					"shoudbe" => $sign,
					"time" => $headers['Xpmse-Time'],
					"nonce" => $headers['Xpmse-Nonce'],
					"apikey" => $headers['Xpmse-Apikey'],
					"query" => array_merge($data['get'], $data['post'])
				]]
			];
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return;
		}
		
		$app = $this->newInstance( $class_file, $c, $a );
		if ( $app === null ) { return ; }
		
		if ( method_exists($app, 'init') ) {

			$app->init($data['user'],  $data['_INJECTION'], $headers );
		}

		try {

			ob_start();
			$app->$a();
			$content = ob_get_contents();
			ob_end_clean();
			echo $content;

		} catch( Excp $e ) {

			$content = ob_get_contents();
			ob_end_clean();
			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => [
					'content' => $content,
					'class_file'=>$class_file,  
					'controller'=>$c, 
					'action'=>$a
				]
			];

			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
						
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);

			return;

		} catch ( Exception  $e ){
			$content = ob_get_contents();
			ob_end_clean();

			$error = [
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'extra' => [
					'content' => $content,
					'class_file'=>$class_file,  
					'controller'=>$c, 
					'action'=>$a
				]
			];

			$error['trace'] = array_merge( 
					[[
						'file'=>$e->getFile(), 
						'line'=>$e->getLIne(),
						'class' => $c,
						'function' => $a
					]], $e->getTrace());
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return;
		}
	}


	private  function localTransparent( $class_file, $c,  $a,  & $data, & $headers ) {
        
        
		$app = $this->newInstance( $class_file, $c, $a );
		if ( $app === null ) { return ; }
        
       
		if ( method_exists($app, 'init') ) {
            $app->init($data['user'],  $data['_INJECTION'], $headers );
		}

       
		$app->$a();
	}



	private  function localFetch( $class_file, $c,  $a,  & $data, & $headers ) {

		$app = $this->newInstance( $class_file, $c, $a );
		if ( $app === null ) { return ; }

		if ( method_exists($app, 'init') ) {
			$app->init($data['user'],  $data['_INJECTION'], $headers );
		}

		return $app->$a();
	}

}
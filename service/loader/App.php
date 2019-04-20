<?php
namespace Xpmse\Loader;

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Route as Route; 
use \Xpmse\Utils as Utils;

use \Twig_Loader_Array;
use \Twig_Environment;
use \Twig_Filter;



/**
 * USAGE:
 * 		APP_ROOT 应用根目录
 * 		APP_HOME 应用访问地址
 * 		APP_HOME_NOFRAME 应用访问地址载入内容框架
 *
 *  
 */

class App {
	

	public static $HOME = null; // 系统主目录
	public static $APP_ROOT=null;
	public static $APP_HOME=null;
	public static $APP_HOME_NOFRAME=null;
	public static $APP_HOME_STATIC=null;
	public static $APP_HOME_PORTAL=null;
	public static $APP_HOME_LOCATION=null;

	public static $user=null;
	public static $injections=[];
	public static $headers = [];

	public static $filters = [];
	public static $twig = null;


	function __construct() {
	}


	/**
	 * 渲染模板
	 * @param  [type] $data   [description]
	 * @param  [type] $layout [description]
	 * @param  string $sharp  [description]
	 * @return [type]         [description]
	 */
	public static function render( $data = NULL , $layout = NULL , $sharp = 'default', $return=false ) {

		$layout = ($layout == null || $layout == "" )?"":$layout . '/';
		$layout = strtolower($layout );
		$sharp = strtolower($sharp );
		$layout_file = APP_ROOT . '/view/' . $layout  . $sharp . '.tpl.html';

		if( file_exists( $layout_file ) ) {
			if ( $return ) {
				ob_start();
			}
			@extract( $data );
			require( $layout_file );

			if ( $return ) {
				$content = ob_get_contents();
	        	ob_end_clean();
	        	return $content;
			}
		}
	}


	public static function twig( $data, $layout, $sharp, $return=false ) {

		$data = is_array($data) ?  $data : [];
		$layout = ($layout == null || $layout == "" )?"":$layout . '/';
		$layout = strtolower($layout );
		$sharp = strtolower($sharp );
		$layout_file = APP_ROOT . '/view/' . $layout  . $sharp . '.tpl.html';

		if( file_exists( $layout_file ) ) {
			$loader = new Twig_Loader_Array( ["code" => file_get_contents($layout_file)] );
			$twig = new Twig_Environment( $loader, ['autoescape'=>false]);
			// $this->add_filters( $twig );
			// 
			try {
				ob_start();
				eval("?>" . $twig->render('code', $data ) );
				$code = ob_get_contents();
	        	ob_end_clean();
			} catch( \Exception $e ) { $code =  "";}

			if ( $return ) {
				return $code;
			}
			echo $code;
		}


		
	}


	public static function addFilter( $name, $fn ) {

	}



	/**
	 * 获取模板路径
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public static function tpl( $name ) {
		$file_name = APP_ROOT . "/view/$name.tpl.html";
		return $file_name;
	}



	/**
	 * 跨应用地址跳转
	 * @param string $namespace 核心命名空间或应用SLUG EG: core-app  i/mina/pages
	 * @param string $c         控制器名称
	 * @param string $a         Action 名称
	 * @param array  $query     查询条件
	 * 
	 */
	public static function URI( $namespace, $c, $a, $query=[] ) {

		$params = [];
		// $home = Utils::getHome($_SERVER['HTTP_TUANDUIMAO_LOCATION']);
		$home = self::$HOME;
		$url = "{$home}/_a";

		if ($namespace != null) { $url .= "/{$namespace}"; unset($query['n']); }
		if ($c != null) { $url .= "/{$c}"; unset($query['c']); }
		if ($a != null) { $url .= "/{$a}"; unset($query['a']); }
		

		foreach ($query as $key => $value) {
			$value = urlencode($value);
			array_push($params,"$key=$value");
		}
		
		$queryString = implode('&',$params);

		if ( $queryString != "" ) {
			$url =empty($url) ? "/" : $url;
			$url = $url . "?$queryString";
		}

		if ( strpos($url, '//') === 0  ){
			$prototype = ( strpos( $_SERVER['HTTP_TUANDUIMAO_LOCATION'] , 'https://')  === 0 ) ? 'https:' : 'http:';
			$url = $prototype . $url;
		}

		return $url;

	}



	/**
	 * 获取 (使用通用组件上传的) 图片等资源文件的访问地址
	 * @param string  $path 对象路径
	 * @param boolen  $url  是否返回CDN地址
	 */
	public static function URL( $path, $url=1 ) {
		return self::URI('mina', 'uploader', 'url', ['path'=>$path, 'url'=>$url]);
	}



	public static function input() {
		return $GLOBALS['_PHPINPUT'];
	}



	/**
	 * 路由地址
	 * @param [type] $c     [description]
	 * @param [type] $a     [description]
	 * @param [type] $query [description]
	 */
	public static function R( $c, $a, $query=array(), $type='default', $cacheable=true, $withdomain=true , $rewrite=true ) {

		$c = strtolower($c);
		$a = strtolower($a);
        // $home = ($withdomain==true) ? Conf::G('general/homepage')  : "";
        $home = Utils::getHome();
		// $home = str_replace(Conf::G('general/domain'), $_SERVER['FROM_HOST'], $home );
		// 路由反向请求
		// if (  Conf::G('general/domain', false) != $_SERVER['FROM_HOST'] ) {
		// 	$rt = new Route;
		// 	$reurl =  $rt->getReverse(['c'=>$c, 'a'=>$a]);
		// 	if ( $reurl !== false && !empty($reurl) && !empty($home) ) {

		// 		if ( is_array($reurl) ) {
		// 			$reurl = $reurl['reuri'];
		// 		}

		// 		foreach ($query as $key => $value) {
		// 			$value = urlencode($value);
		// 			$reurl = str_replace("{". $key. "}", $value, $reurl );
		// 		}

		// 		return $home . $reurl;
		// 	}
		// }
		

		// $url = APP_HOME;

		// echo "=-=== $type===== " . APP_HOME .  ' . NR:' .   APP_HOME_NOFRAME . ' ...';
		// if ($noframe) $url = APP_HOME_NOFRAME;
		switch ($type) {
			case 'default':
				$url = APP_HOME;
				break;
			case 'noframe':
				$url = APP_HOME_NOFRAME;
				break;
			
			case 'portal':
				$url = APP_HOME_PORTAL;
				break;

			default:
				$url = APP_HOME;
				break;
		}

		$params = array();

		if ( $rewrite == true ) {

			if ($c != null) { $url .= "/{$c}";  }
			if ($a != null) { $url .= "/{$a}";  }

		} else { 

			if ($c != null){ array_push($params,"c=$c"); }
			if ($a != null) { array_push($params,"a=$a"); }
		}
		
		foreach ($query as $key => $value) {
			$value = urlencode($value);
			array_push($params,"$key=$value");
		}
		
		$queryString = implode('&',$params);
		if ( $queryString != "" ) {

			if ( strpos($url, "?") === false ) {
				$url = $url . "?$queryString";
			} else {
				$url = $url . "&$queryString";
			}
		}

		// $url = $home. $url;
		// if ( strpos($url, '//') === 0  ){
		// 	$prototype = ( strpos( $_SERVER['HTTP_TUANDUIMAO_LOCATION'] , 'https://')  === 0 ) ? 'https:' : 'http:';
		// 	$url = $prototype . $url;
		// }

		return $url;
	}

	/**
	 * 路由地址（不带FRAME）
	 * @param [type] $c     [description]
	 * @param [type] $a     [description]
	 * @param array  $query [description]
	 */
	public static function NR(  $c, $a, $query=array(), $cacheable=true , $withdomain=true, $rewrite=true ) {
		return self::R( $c, $a, $query, 'noframe', $cacheable, $withdomain, $rewrite );
	}
	

	/**
	 * 路由地址 ( Portal)
	 */
	public static function PR(  $c, $a, $query=array(), $cacheable=true, $withdomain=true , $rewrite=true) {
		return self::R( $c, $a, $query, 'portal' , $cacheable, $withdomain, $rewrite);
	}

	/**
	 * 路由地址 ( Static )
	 */
	public static function SR( $path, $cacheable=true, $withdomain=true  ) {
		return $home . APP_HOME_STATIC . $path;

		$home = ($withdomain==true) ? Conf::G('general/homepage')  : "";
		return  $home . APP_HOME_STATIC . $path;
	}


	/**
	 * 运行 Controller，并返回结果 ( 不带FRAME )
	 * @param [type] $c         [description]
	 * @param [type] $a         [description]
	 * @param array  $query     [description]
	 */
	public static function NRUN( $c, $a, $query=[], $data =[], $cacheable=true) {

		$c =  ucwords(strtolower($c));
		$a =  basename(strtolower($a));
		$class_name = $c."Controller";

		$route = APP_ROOT . "/controller/{$c}.php";

		if (!file_exists($route) ) { // 兼容LP3风格
			$lpc = strtolower($c);
			$route = "/controller/{$lpc}.class.php";
		}

		if (!file_exists($route)){
			throw new Excp("控制器文件不存在", '404', ['route'=>$route, 'c'=>$c, 'query'=>$query, 'data'=>$data]);
		}

		try {
			include_once($route);
		} catch( Exception $e ) {
			throw $e;
		}


		if ( !method_exists($class_name, $a) ) {
			throw new Excp("控制器类或方法不存在 ($class_name)", '404', ['route'=>$route, 'class_name'=>$class_name, 'a'=>$a, 'c'=>$c, 'query'=>$query, 'data'=>$data]);
		}

		$GLOBALS['_GET']= $query;
		$GLOBALS['_POST'] = $data;

		$app = new $class_name();
		if ( method_exists($app, 'init') ) {
			
			self::$headers['Xpmse-Controller'] = $c;
			self::$headers['Xpmse-Action'] = $a;

			$app->init(self::$user, self::$injections, self::$headers );
		}

		return call_user_func( array( $app , $a ) );

	}


	/**
	 * 快速创建数据模型对象 ( 即将废弃慎用 )
	 * @param [type] $model_name [description]
	 * @param array  $params     [description]
	 */
	public static function M($model_name, $params=[]) {
		
		$module_root = APP_ROOT  . '/model';

		// echo  "\n module_root = $module_root \n";
		$option = explode('::', $model_name );
		if ( count($option) == 2 ) {
			$name = $option[1];
			$module_path = $module_root."/". strtolower($option[0]);
			$class_name = "{$name}{$option[0]}Model";
		} else {
			$name = $option[0];
			$module_path = $module_root;
			$class_name = "{$name}Model";
		}

		//把首个字符专为大写
		$name = ucwords(strtolower($name)); //Name
		
		$model_file = $module_path . "/$name.php";
		if (!file_exists($model_file) ) {
			$model_file =  $module_path . "/" .strtolower($name) . ".class.php";
		}

		if (!file_exists($model_file) ) {
			throw new Excp('模块文件不存在', "404", ['model_file'=>$model_file, 'model_name'=>$model_name, 'params'=>$params]);
		}


		require_once( $model_file );



		if ( !class_exists($class_name) ) {

			$class_name  = '\\' . __NS__ . '\\' . $name;
			
		}

		if ( !class_exists($class_name) ) { 

			throw new Excp('模块未定义', "404", ['class_name'=>$class_name, 'model_file'=>$model_file, 'model_name'=>$model_name, 'params'=>$params]);
		}

		return new $class_name( $params );
	}

}


	

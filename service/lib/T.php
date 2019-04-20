<?php

namespace Xpmse;

require_once( __DIR__ . '/Mem.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/utils-lib/Validatecode.php');

use \Exception as Exception;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;

use \Twig_Loader_Array;
use \Twig_Environment;
use \Twig_Filter;
use \Twig_Lexer;


/**
 * XpmSE模板引擎
 */
class T {
	
	public static $twig = null;


	function __construct() {
	}

	/**
	 * 解析模板，默认标签为 <%=var %>
	 * @param  [type] $tpl  [description]
	 * @param  [type] $data [description]
	 * @param  [type] $opts [description]
	 * @return [type]       [description]
	 */
	public static function v( $tpl, $data, $opts = [] ) {

		if ( empty($opts['tag']) ) {
			$opts['tag'] = [
				'tag_variable'=>['<%=','%>']
			];
		}
		return self::toString( $tpl, $data, $opts );
	}

	/**
	 * 解析模板，默认标签为 {{var}}
	 * @param  [type] $tpl  [description]
	 * @param  [type] $data [description]
	 * @param  [type] $opts [description]
	 * @return [type]       [description]
	 */
	public static function s( $tpl, $data, $opts = [] ) {
		return self::toString( $tpl, $data, $opts );
	}


	/**
	 * 解析模板 
	 * @param  [type] $tpl  [description]
	 * @param  [type] $data [description]
	 * @param  array  $opts [description]
	 * @return [type]       [description]
	 */
	public static function toString( $tpl, $data, $opts=[] ) {

		if ( !is_array($data) ) {
			$data = [];
		}

		$loader = new Twig_Loader_Array(["code" => $tpl]);
		self::$twig = new Twig_Environment($loader, array_merge([
			'autoescape'=>false,
			"debug" => false  // 是否为 Debug 模式
		], $opts));

		// 加载默认 Filter 
		include(__DIR__ . "/xsfdl/Filter.php");
		$opts['filters'] = !is_array($opts['filters']) ? [] : $opts['filters'];
		$opts['filters'] = array_merge($_Twig_Filters, $opts['filters']);
	
		// 设定 Filter
		if ( !empty($opts['filters']) ) {
			foreach ($opts['filters'] as $name => $filter) {
				self::$twig->addFilter( $filter );
			}
		}

		// 设定标签
		$tag = empty($opts['tag']) ? [] : $opts['tag'];
		if ( !empty($tag) ) {
			$lexer = new Twig_Lexer(self::$twig, $tag);
			self::$twig->setLexer($lexer);
		}

		return self::$twig->render('code', $data );
	}

}
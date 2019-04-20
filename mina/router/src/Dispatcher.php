<?php
/**
 * MINA Pages 动态路由表
 * 
 * @package      \Mina\Router
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Router;

use \Exception;

class Dispatcher {

	private $cache = null; 
	protected $options;

	/**
	 * 页面分发器
	 * @param array $options 配置选项
	 *      		 array    ["cache"]   缓存配置选项
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

		$cacheOptions = is_array($this->options['cache']) ? $this->options['cache'] : [];
		if (!empty($cacheOptions['engine'])) {
			$cacheClassName = "\\Mina\\Cache\\{$cacheOptions['engine']}";

			if ( class_exists($cacheClassName) ) {
				$this->cache = new $cacheClassName( $cacheOptions );
			}
		}
	}


	/**
	 * 配置路由分发器
	 * @param  [type] $get_entries [description]
	 * @param  [type] $render      [description]
	 * @return [type]              [description]
	 */
	function setup(  $get_entries, $render, $nocache = false, $instance="root" ) {

		if ( !is_callable($get_entries) ) {
			throw new Exception("未提供入口读取方法 ", 404);
		}

		if ( !is_callable($render) ){
			throw new Exception("未提供页面渲染方法 ", 404);	
		}

		$domain = $_SERVER['HTTP_HOST'];
		$entries = false;
		if( $nocache === false  && $this->cache !== null ) {
			$cacheName = $this->cacheName("{$instance}:{$domain}");
			$entries = $this->cache->getJSON( $cacheName );
		}

		if ( $entries === false ) {
			$entries = $get_entries( $domain, $instance );
			if( $this->cache !== null ) { // 数据写入缓存
				$cacheName = $this->cacheName( "{$instance}:{$domain}");
				$this->cache->setJSON( $cacheName, $entries );
			}
		}


		if ( !is_array($entries) ) {
			throw new Exception("无效入口信息", 400);	
		}

		$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($entries) {
			$filter = [];
			foreach ( $entries['data'] as $idx=>$entry ) {
				
				$slug = $entry['method'] . ':'. $entry['router'];
				if ( isset($filter[$slug]) ) {
					continue;
				}
			
				$r->addRoute( $entry['method'], $entry['router'], "{$idx}" );
				$filter[$slug] = true;
			}
		});

		$httpMethod = $_SERVER['REQUEST_METHOD'];
		$uri = $_SERVER['REQUEST_URI'];

		if (false !== $pos = strpos($uri, '?')) {
		    $uri = substr($uri, 0, $pos);
		}
		$uri = rawurldecode($uri);
		$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

		switch ($routeInfo[0]) {
			case \FastRoute\Dispatcher::NOT_FOUND:  // ... 404 Not Found
				// header("HTTP/1.1 404 Not Found");  
				// header("Status: 404 Not Found");
				// echo "404 Not Found";
				// exit;
				$render(null, 404, '404 Not Found', $instance);
				break;
		    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED: // ... 405 Method Not Allowed
				$allowedMethods = $routeInfo[1];
				// header("HTTP/1.1 Method Not Allowed");  
				// header("Status: Method Not Allowed");
				// echo "Method Not Allowed";

				$render(null, 405, 'Method Not Allowed', $instance);
				break;
		    case \FastRoute\Dispatcher::FOUND: // ... call $handler with $vars
				$handler = $routeInfo[1];
				$vars = $routeInfo[2];
				$render( $entries['data'][$handler], $vars, $entries['map'], $instance );
				break;
		}
	}


	function cacheName( $name ) {
		return "{$name}";
	}

	function clearCache( $domain = null ) {
		if( empty( $this->cache ) ) {
			return false;
		}
		return $this->cache->delete( $this->cacheName( $domain ));	
	}

}
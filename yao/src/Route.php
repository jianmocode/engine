<?php
/**
 * Class Route
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \FastRoute\simpleDispatcher;
use \Yao\Route\Request;

/**
 * 路由器(Base on FastRoute)
 */
class Route {

    /**
     * 路由设定文件寻址
     */
    protected static $groupMapping;

    /**
     * 路由表
     */
    protected static $routingTable = [];

    /**
     * 构造函数
     */
    public function __construct() {
    }


    /**
     * 设定路由文件寻址
     * 
     * @param array $groupMapping 路由设定文件
     * @return void
     */
    public static function setGroups( $groupMapping ) {
        self::$groupMapping = $groupMapping;
    }

    /**
     * 运行路由
     */
    public static function run() {
        
        $req = new Request();
      
        $uri = explode("/", $req->requestURI );
        array_shift($uri);

        if ( 1 === $pos = strpos($req->requestURI, '__')) {
            array_shift($uri);
        }
        
        if (count($uri) < 1) {
            throw Excp::create("非法API请求", 402 );
        }

        // 解析分组
        $group = $uri[0];
        if ( array_key_exists($group, self::$groupMapping) ) {
            array_shift($uri);
        } else {
            $group = "default";
        }

        // 解析路由文件
        $routeFile = ucfirst(strtolower($uri[0])) . ".php";
        array_shift($uri);
        
        $path = self::$groupMapping[$group];
        $file = "{$path}/{$routeFile}";
        if ( !file_exists($file) ) {
            throw Excp::create("API不存在", 404 );
        }

        // 读取路由表
        include_once( $file );

        // 设定路由表
        $dispatcher = \FastRoute\simpleDispatcher(function($r) use( $group ) {
            if ( $group === "default" ) {
                foreach(Route::$routingTable as $routing ) {
                    $r->addRoute( ...$routing );
                }   
            } else {
                $r->addGroup("/{$group}", function( $r ) {
                    foreach(Route::$routingTable as $routing ) {
                        $r->addRoute( ...$routing );
                    }
                });
            }
        });

        // 解析参数
        $uri = "/" . implode("/", $uri );
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($req->method, $uri);

        // 执行路由函数
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                throw Excp::create("API不存在", 404 );
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $req->setURI($vars);
                return $handler( $req );
                break;
        }

    }

    /**
     * 
     * 设定 HTTP GET 路由表
     * @param string $uri 路由信息
     * @param callable $callback 回调函数 function( \Yao\Route\Request $r ){}
     * @param int $tls 数据缓存时长
     * 
     * @return void
     */
    public static function get( $uri, callable $callback, $tls=0 ) {
        array_push( self::$routingTable, ["GET", $uri, $callback, $tls] );
    }

}

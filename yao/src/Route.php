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
use FastRoute\simpleDispatcher;

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
    protected static $routingTable;

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
     * 运行
     */
    public static function run() {

    }

    /**
     * 设定 HTTP GET 路由表
     */
    public static function get( $uri, callable $callback ) {
        array_push( self::$routingTable, ["$uri", $callback] );
    }

}

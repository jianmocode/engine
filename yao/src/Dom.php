<?php
/**
 * Class Dom
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \PHPHtmlParser\Dom as HtmlParserDom;
use \Excption;

/**
 * Dom 解析器 
 * see https://github.com/paquettg/php-html-parser
 */
class Dom {

    /**
     * Dom 实例
     */
    public static $dom = null;

     /**
     * Dom 对象
     */
    public function __construct() {}

    
    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        if ( is_null(self::$dom) ) {
            self::$dom = new HtmlParserDom();
        }

        try {
            self::$dom->{$method}(...$parameters);
            return self::$dom;
            
        } catch (Excption $e) {

            $excp = Excp::create( $e->getMessage(), 500, [
                "code" => $e->getCode(),
                "method"=>$method, 
                "parameters"=>$parameters
            ]);

            $excp->log();
            throw $excp;
        }
    }

}
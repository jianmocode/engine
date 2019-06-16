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
 * 数组处理迅捷函数
 */
class Arr {


    /**
     * 设定数组默认值
     * 
     * @param array $input 输入数组引用
     * @param array $defaults 默认数值
     * 
     * @return void
     */
    public static function defaults( array & $input, array $defaults ) {
        
        foreach( $defaults as $name=>$value ) {

            if ( is_array($value) ) {
                self::default( $input[$name], $value );
            } else if ( !array_key_exists($name, $input) ) {
                $input[$name] = $value;
            }
        }
        
    }
    

}
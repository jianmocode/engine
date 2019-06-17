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
use \Illuminate\Support\Arr as IlluminateArr;
use \Yao\Route\Request;


/**
 * 数组处理迅捷函数
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Support/Arr.php
 * 
 */
class Arr extends IlluminateArr {


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

            if ( !array_key_exists($name, $input) ) {
                $input[$name] = $value;
            } else if ( is_array($value) ) {
                self::default( $input[$name], $value );
            }
        }
    }

    /**
     * 替换 `{{key}}` 为 bindings 设定数值
     * @param array $input 输入数组引用
     * @param array $bindings 绑定数据
     */
    public static function binds( array & $input, array $bindings ){
        
        $bindings = self::dot( $bindings );
        $bindings = array_filter($bindings, function($v, $k) {
            return is_string($v);
        }, ARRAY_FILTER_USE_BOTH);
        if ( empty($bindings) ) {
            return;
        }

        self::varize( $bindings );
        [$keys,$replaces] = self::divide( $bindings );

        foreach( $input as & $value ) {
            if( is_array($value) ) {
                self::binds( $value, $bindings );
            } else if ( is_string($value) ) {
                $value = str_replace( $keys, $replaces, $value );
            }
        }
    }


    /**
     * 给数组 key 添加 {{}}
     * @param array $input 输入数组引用
     */
    public static function varize( & $input ) {
        
        foreach ( $input as $key => $value ) {
            if ( is_array($value) ) {
                self::varize( $value );
                continue;
            }
            if ( 0 !== strpos($key, "{{") ) {
                $input["{{{$key}}}"] = $value;
                unset($input["$key"]);
            }
        }
    }

}
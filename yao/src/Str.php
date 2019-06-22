<?php
/**
 * Class Str
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \Yao\Arr;
use \Illuminate\Support\Str as IlluminateStr;


/**
 * 字符串处理迅捷函数
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Support/Str.php
 * 
 */
class Str extends IlluminateStr {


    /**
     * 如果输入字符串, 将用分隔符分隔的字符串转换为数组, 同时去掉每一项首位空行. 
     * 如果输入字符串数组, 去掉每一项首尾空行.
     * 
     * @param string        $delimiter 数据分割符
     * @param string|array  $data  输入数据。
     * 
     * @return array 字符串数组
     * 
     */
    public static function explodeAndTrim( string $delimiter, $data ) {

        if ( is_array($data) ) {
            foreach( $data as & $v ) {
                if ( is_array($v) ) {
                    self::explodeAndTrim($delimiter, $v);
                } else if ( is_string($v)) {
                    $v = trim( $v );
                }
            }
            return $data;
        }
        return array_map("trim", explode($delimiter, $data));
    }
    
    /**
     * 生成一个由数字组成的唯一ID
     * @return string Numberic unique string
     */
    public static function uniqid() {
        return number_format(hexdec(uniqid()),0, "", "");
    }

    /**
     * 替换 `{{key}}` 为 bindings 设定的数值
     * @param array &$input 输入字符串
     * @param array $bindings 绑定数据
     */
    public static function binds( string & $input, array $bindings ){
        
        $bindings = Arr::dot( $bindings );
        $bindings = array_filter($bindings, function($v, $k) {
            return is_string($v);
        }, ARRAY_FILTER_USE_BOTH);
        if ( empty($bindings) ) {
            return;
        }

        Arr::varize( $bindings );
        [$keys,$replaces] = Arr::divide( $bindings );
        $input = str_replace( $keys, $replaces, $input );
    }


    /**
     * 检查输入的字符串是否为URL
     * 
     * @param string $input 输入的字符串
     * @return bool 如果是URL返回 true, 否则返回 false
     */
    public static function isURL( string $input ){
        if ( filter_var($input, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED|FILTER_FLAG_HOST_REQUIRED) ) {
            return true;
        } 

        return false;
    }

    /**
     * 强制转换为 Https 协议
     * @param string $input 输入的字符串
     * @return string https:// 开头的地址
     */
    public static function forceHttps( string $input ){
        
        if ( self::isDomain($input) ) {
            return "https://" . $input;
        }

        if ( !self::isURL($input) ) {

            echo "NO URL {$input}\n";
            

            return preg_replace("/^(?:\/\/)/", "https://", $input);
        }

        $regex = "/^(?:http\:)*\/\//";
        return preg_replace("/^(?:http\:)*\/\//", "https://", $input);
    }


    /**
     * 检查输入的字符串是否为PATH
     * 
     * @param string $input 输入的字符串
     * @return bool 如果是PATH返回 true, 否则返回 false
     */
    public static function isPath( string $input ){

    }

    /**
     * 检查输入的字符串是否为域名
     * 
     * @param string $input 输入的字符串
     * @return bool 如果是域名返回 true, 否则返回 false
     */
    public static function isDomain( string $input ){
        if ( filter_var($input, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)  ) {
            return true;
        }

        return false;
    }

    /**
     * 检查输入的字符串是否为 Email
     * 
     * @param string $input 输入的字符串
     * @return bool 如果是Email返回 true, 否则返回 false
     */
    public static function isEmail( string $input ){
        if ( filter_var($input, FILTER_VALIDATE_EMAIL) ){
            return true;
        }
        return false;
    }

    /**
     * 检查输入的字符串是否为IP地址
     * 
     * @param string $input 输入的字符串
     * @return bool 如果是IP地址返回 true, 否则返回 false
     */
    public static function isIP( string $input ){
        if (filter_var($input, FILTER_VALIDATE_IP)  ){
            return true;
        }
        return false;
    }

}
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
        return hexdec(uniqid());
    }
}
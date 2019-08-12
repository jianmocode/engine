<?php
/**
 * Class Arr
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
     * 检查是否为 Key-Value 结构数组
     */
    public static function isAssoc(array $arr) {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }


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
                $input[$name] = !is_array($input[$name]) ? [] : $input[$name];
                self::defaults( $input[$name], $value );
            }
        }
    }

    /**
     * 替换 `{{key}}` 为 bindings 设定数值
     * @param array &$input 输入数组引用
     * @param array $bindings 绑定数据
     * 
     * @return void
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
        list($keys,$replaces) = self::divide( $bindings );

        foreach( $input as & $value ) {
            if( is_array($value) ) {
                self::binds( $value, $bindings );
            } else if ( is_string($value) ) {
                $value = str_replace( $keys, $replaces, $value );
            }
        }
    }


    /**
     * 还原数组 first.second.third  >  $arr["first"]["second"]["third"]
     * 
     * @param array $input 输入数组引用
     * 
     * @return array 
     */
    public static function explode( $input  ) {
        
        $result = [];
        foreach ( $input as $key => $value ) {
            self::set( $result, $key, $value );
        }
        return $result;
    }


    /**
     * 将二维数组转换为以唯一字段为主键的键值结构
     * 
     * 示例 :
     * 
     * [
     *   ["key1"=>"value1"],["key2"=>"value2"],
     * ]
     * 
     * 转换为
     * 
     * [
     *   "key1" => ["key1"=>"value1"],
     *   ”key2“ => ["key2"=>"value2"],
     * ]
     * 
     * @param string $field 唯一主键字段名称
     * @param array  $input 二维数组
     * 
     * @return array 键值数组
     * 
     */
    public static function mapBy( string $field, array $input ) {

        $map = [];
        array_walk($input, function($value, $index) use($field, & $map) {
            if ( !is_array($value) ){
                return $value;
            }
            $key = Arr::get( $value, $field );
            if ( Arr::get($map, $key) !== null ) {
                throw Excp::create("{$key}数据已存在", 402);
            }
            $map[$key] = $value;
        });

        return $map;
    }


    /**
     * 合并并将二维数组转换为以唯一字段为主键的键值结构 (待优化)
     * 
     * @param string    $field 唯一主键字段名称
     * @param array     $array 二维数组
     * @param array     ...$arrayN 二维数组
     * 
     * @return array 
     */
    public static function mapAndMergeBy(string $field, array $array, array ...$arrayN ) {
        
        $array = Arr::mapBy( $field, $array );
        foreach( $arrayN as & $arr ) {
            $arr = Arr::mapBy( $field, $arr );
            $array = array_merge_recursive( $array, $arr );
        }

        $array = array_map(  function($v) use($field) {
            $v[$field] = is_array($v[$field]) ? current($v[$field]) : $v[$field];
            return $v;
        }, $array);

        return $array;
    }


    /**
     * 按分组查询数据
     * 
     * @param string $field 字段名称
     * @param array  $input 数组
     * @return  array  分组映射
     */
    public static function groupBy( string $field, array $input ) {
        
        $map = [];
        array_walk($input, function($value, $index) use($field, & $map) {
            if ( !is_array($value) ){
                return $value;
            }
            $key = Arr::get( $value, $field );
            $map[$key][] = $value;
        });

        return $map;
    }


    /**
     * 给数组 key 添加 {{}}
     * @param array $input 输入数组引用
     * @return void
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
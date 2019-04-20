<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
use \Xpmse\Model;
use \Xpmse\Excp;
use \Mina\Cache\Redis as Cache;

/**
 * XpmSE RESTFul API 构造器
 */
class Validate {

    public static $atLeastOne =[];

    public static $fieldMap = [];


    function __construct( $option = [] ) {
        self::clean();
    }

    public static function clean(){
        self::$atLeastOne = [];
    }
    
    public function test($field, $value, array $rule ) {

        $method = current( array_keys($rule) );
        $args = array_values($rule);
      
        // 其他匹配项
        if ( !method_exists($this, $method) ) {
            throw new Excp("未找到{$method}数据校验方法", 500, [
                "fields"=> [$field],
                "messages"=> ["$field"=>"未找到{$method}数据校验方法"]
            ]);
        }

        return $this->$method( $value, ...$args );

    }

    /**
     * 过滤不需要的数据
     * @param array & $array 待过滤数据
     * @param array $allowed 许可的键值数组
     */
    public function filter( & $array, $allowed ) {
        $keys = array_keys($array);
        $removeKeys = array_diff( $keys, $allowed);
        array_walk($removeKeys , function( $rmkey ) use( & $array ) {
            unset($array["$rmkey"]);
        });
    }

    /**
     * 检查至少包含一个的字段
     */
    public function checkAtLeastOne() {
      
        $test = array_column(self::$atLeastOne, "test");
        if ( array_search(false, $test, true) === false ) {
            $fields = array_keys(self::$atLeastOne);
            $message = current(array_column(self::$atLeastOne, "message"));
            if ( empty($message) ){
                $fieldstrs = \implode(",", $fields);
                $message = "[{$fieldstrs}]至少填写一个";
            }

            throw new Excp($message, 400, [
                "fields"=> $fields,
                "values" => [],
                "messages"=> []
            ]);
        }

        self::clean();
    }

    /**
     * 检查字段数值
     * @param string $field 字段名称
     * @param mix $value  字段数值
     * @param array $rules 一组校验规则
     * @param array $messages 对照规则返回数值
     * @return 成功返回null, 失败抛出400异常
     */
    public function check($field, $value, array $rules, $messages=[] ) {
        
        // 已检测清单
        self::$fieldMap[$field] = $value;

        $methods = array_keys($rules);

        foreach( $rules as $rule ) {
            $key = str_replace("~", "", current( array_keys($rule) ));
            $method =  current( array_keys($rule));
            $message = is_array($messages) ? $messages["$key"] : $message;
            if ( empty($message) ) {
                $message = "{$field}格式不正确";
            }

            // 至少一个(忽略处理)
            if ( $method == "~required" ) {
                self::$atLeastOne[$field] = [
                    "test" => is_null( $value ),
                    "message" => $message
                ];
                return;
            }

            // 检查空值
            if ( is_null($value) && array_search("required", $methods, true) !== false ) {
                throw new Excp($message, 400, [
                    "fields"=> [$field],
                    "values" => ["$field"=>$value],
                    "messages"=> ["$field"=>$message]
                ]);
            }


            // 检查非空数值
            if ( !is_null($value) && !$this->test( $field, $value, $rule) ) {
                throw new Excp($message, 400, [
                    "fields"=> [$field],
                    "values" => ["$field"=>$value],
                    "messages"=> ["$field"=>$message]
                ]);
            }
        }

    }

    /**
     * 匹配正则表达式
     */
    public function match( $value, $reg ){
        // echo"/^{$reg}$/ {$value}";
        return  preg_match( "/^{$reg}$/", $value);
    }

    /**
     * 检查最小长度
     */
    public function minlength( $value, $length ){
        return strlen( $value ) >= $length;
    }

    /**
     * 检查最大长度
     */
    public function maxlength( $value, $length ){
        return strlen( $value ) <= $length;
    }

    /**
     * 检查长度范围
     */
    public function rangelength( $value, $minlength, $maxlength ){
        return strlen( $value ) <= $maxlength &&  strlen( $value ) >= $minlength;
    }

    /**
     * 检查最小值
     */
    public function min( $value, $min ){
        if ( !is_numeric($value)  ) {
            return false;
        }
        return $value >= $min;
    }

    /**
     * 检查最大值
     */
    public function max( $value, $max ){
        if ( !is_numeric($value)  ) {
            return false;
        }
        return $value <= $max;
    }

    /**
     * 检查数值范围
     */
    public function range( $value, $min, $max ){
        return $value <= $max &&  $value >= $min;
    }

    /**
     * 检查字符串范围(单个匹配)
     */
    public function rangestring( $value, $array ){
        return in_array($value, $array);
    }

    /**
     * 检查字符串范围(多个匹配)
     */
    public function rangestrings( $value, $array ){
        if( !is_array($value) )  {
            return false;
        }
        foreach($value as $v ) {
            if ( !in_array($v, $array) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检查日期时间范围
     */
    public function rangedate( $value, $array ){
        return in_array($value, $array);
    }

    /**
     * 检查日期时间最大值
     */
    public function enddate( $value, $array ){
        return in_array($value, $array);
    }

    /**
     * 检查日期时间最小值
     */
    public function begindate( $value, $array ){
        return in_array($value, $array);
    }


    /**
     * 检查数组数值最少个数
     */
    public function mincount( $value, $count ){
        if( !is_array($value) )  {
            return false;
        }
        return count( $value ) <= $count;
    }

    /**
     * 检查数组数值最大个数
     */
    public function maxcount( $value, $count ){
        if( !is_array($value) )  {
            return false;
        }
        return count( $value ) >= $count;
    }

    /**
     * 检查数组数值最大个数
     */
    public function rangecount( $value, $counts ){
        if( !is_array($value) )  {
            return false;
        }
        return count( $value ) >= $count;
    }

    

    /**
     * 检查是否为Email
     */
    public function email( $value ){
        $email = "[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*";
        return  preg_match( "/^{$email}$/", $value);
    }

    /**
     * 检查是否为Url
     */
    public function url( $value ){
        $url = "(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?";
        return  preg_match( "/^{$url}$/", $value);
    }

    /**
     * 检查是否是日期时间
     */
    public function date( $value ){
        if (strtotime($value) === false ) {
            return false;
        }
        return true;
    }

    /**
     * 检查是否是日期时间
     */
    public function dateISO( $value ) {
        $dateiso = "\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01]";
        return  preg_match( "/^{$dateiso}$/", $value);
    }

    /**
     * 检查是否是数字(可带小数点)
     */
    public function number( $value ) {
        return is_numeric($value);
    }

    /**
     * 是否是数字(不可带小数点)
     */
    public function digits( $value ) {
        $digits = "\d+";
        return  preg_match( "/^{$digits}$/", $value);
    }

    /**
     * 检查是否等于某个值/字段的值
     */
    public function equalTo( $value, $another ) {

        // 可替换变量
        if ( strpos($another, "#") === 0 ) {
            $another = str_replace("#", "", $another);
            $another = self::$fieldMap[$another];
        }

        return ( $value == $another );
    }

    /**
     * 验证自定义巢型结构数据
     */
    public function nested( $value, $struct ) {
        return true;
    }
    
}
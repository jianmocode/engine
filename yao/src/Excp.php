<?php
/**
 * Class Excp
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */


namespace Yao;
use \Exception;

/**
 * 异常对象
 * 
 * 错误码定义: 
 *  - 0        未定义错误码
 *  - 400-500  因客户端输入错误，导致接口查询失败, 返回结果为异常描述数据. 
 *  - 500-600  因服务端资源不足或程序异常，导致接口查询失败, 返回结果为异常描述数据.
 *  - 400      因服务端资源不足或程序异常，导致接口查询失败, 返回结果为异常描述数据.
 *  - 401      因用户尚未登录，导致接口查询失败.
 *  - 402      因尚未完成购买, 导致接口查询失败.
 *  - 403      没有对应资源接口的查询权限
 *  - 404      查询资源不存在.
 *  - 405      接口不允许访问
 *  - 406      无法响应请求 
 *  - 407      代理需要权限验证
 *  - 408      接口响应超时
 *  - 409      CONFLICT
 *  - 410      GONE
 *  - 411      Length Required
 *  - 412      Precondition Failed
 *  - 413      Payload 超过最大长度
 *  - 414      URI 超过最大长度
 *  - 415      Unsupported Media Type
 *  - 500      服务端程序抛出异常, 返回结果为具体的异常描述.
 *  - 502      网关错误
 *  - 503      服务器暂时不可访问
 *  - 504      服务器网关超时
 * 
 */
class Excp extends Exception {

    /**
     * 错误扩展数据, 字段约定:
     *  :fields array 错误相关字段
     *  :messages[:field] 字段错误信息
     * 
     * @var array 错误扩展数据
     */
    protected $extra = [];

    /**
     * 构造函数
     * 
     * @param string $message 错误描述
     * @param int $code 错误码 
     * @param array $extra 错误扩展数据
     * @return Excp 异常对象实例
     */
    function __construct( $message, int $code=0, $extra=[] ) {
        $this->message = $message;
        $this->code = $code;
        $this->extra = $extra;
    }


    /**
     * 读取错误扩展信息
     * 
     * @return array $extra 错误扩展数据
     */
    function getExtra(){
        return $this->extra;
    }

    /**
     * 添加错误字段, 示例:
     * 
     *    $excp = new Excp("未找到该用户", 404);
     *    $excp->addField("user_id", "用户({$user_id})不存在")
     *         ->addField("user_slug", "用户({$user_slug})不存在")
     *    ;
     *    echo $excp;
     * 
     * @param string $field 出错的字段名称
     * @param string $message 错误描述
     * @return Excp $this
     */
    function addField( string $field, string $message ){
        $this->extra["fields"][] = $field;
        $this->extra["messages"][$field] = $message;
        $this->extra["fields"] = array_unique( $this->extra["fields"] );
        return $this;
    }

    /**
     * 转换为数组
     * @param bool $with_trace 是否返回追踪信息, 默认为 false, 不反回追踪信息。
     * @return array 错误结构体
     *                  :message string 错误描述
     *                  :code int 错误码
     *                  :extra array 错误扩展数据
     *                  :trace array 追踪信息数组
     */
    function toArray( $with_trace=false ) {
        return [
            "message" => $this->message,
            "code" => $this->code,
            "extra" => $this->extra,
            "trace" => ( $with_trace == true ) ?  $this->getTrace() : []
        ];
    }

    /**
     * 重载错误输出 
     * @example echo new Excp("资源未找到", 404);
     * @return string 错误结构体JSON格式文本
     */
    function __toString(){
        return json_encode( [
            "message" => $this->message,
            "code" => $this->code,
            "extra" => $this->extra,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

}
Yao\Excp
===============

异常对象

错误码定义:
 - 0        未定义错误码
 - 400-500  因客户端输入错误，导致接口查询失败, 返回结果为异常描述数据.
 - 500-600  因服务端资源不足或程序异常，导致接口查询失败, 返回结果为异常描述数据.
 - 400      因服务端资源不足或程序异常，导致接口查询失败, 返回结果为异常描述数据.
 - 401      因用户尚未登录，导致接口查询失败.
 - 402      因尚未完成购买, 导致接口查询失败.
 - 403      没有对应资源接口的查询权限
 - 404      查询资源不存在.
 - 405      接口不允许访问
 - 406      无法响应请求
 - 407      代理需要权限验证
 - 408      接口响应超时
 - 409      CONFLICT
 - 410      GONE
 - 411      Length Required
 - 412      Precondition Failed
 - 413      Payload 超过最大长度
 - 414      URI 超过最大长度
 - 415      Unsupported Media Type
 - 500      服务端程序抛出异常, 返回结果为具体的异常描述.
 - 502      网关错误
 - 503      服务器暂时不可访问
 - 504      服务器网关超时


* Class name: Excp
* Namespace: Yao
* Parent class: Exception





Properties
----------


### $extra

    protected mixed $extra = array()

错误扩展数据. 字段约定:
 :fields array 错误相关字段
 :messages[:field] 字段错误信息



* Visibility: **protected**


Methods
-------


### __construct

    mixed Yao\Excp::__construct(string $message, integer $code, array $extra)

构造函数



* Visibility: **public**


#### Arguments
* $message **string** - &lt;p&gt;错误描述&lt;/p&gt;
* $code **integer** - &lt;p&gt;错误码&lt;/p&gt;
* $extra **array** - &lt;p&gt;错误扩展数据&lt;/p&gt;



### getExtra

    array Yao\Excp::getExtra()

读取错误扩展信息



* Visibility: **public**




### addField

    \Yao\Excp Yao\Excp::addField(string $field, string $message)

添加错误字段



* Visibility: **public**


#### Arguments
* $field **string** - &lt;p&gt;出错的字段名称&lt;/p&gt;
* $message **string** - &lt;p&gt;错误描述&lt;/p&gt;



### toArray

    array Yao\Excp::toArray(boolean $with_trace)

转换为数组



* Visibility: **public**


#### Arguments
* $with_trace **boolean** - &lt;p&gt;是否返回追踪信息, 默认为 false, 不反回追踪信息。&lt;/p&gt;



### __toString

    string Yao\Excp::__toString()

重载错误输出



* Visibility: **public**




Yao\Wxpay
===============

微信支付接口




* Class name: Wxpay
* Namespace: Yao





Properties
----------


### $config

    private array $config = array()

微信支付配置选项



* Visibility: **private**


### $errorCodes

    public array $errorCodes = array("NOAUTH" => "商户无此接口权限", "NOTENOUGH" => "用户帐号余额不足", "ORDERPAID" => "商户订单已支付，无需重复操作", "ORDERCLOSED" => "当前订单已关闭，无法支付", "SYSTEMERROR" => "系统超时", "APPID_NOT_EXIST" => "参数中缺少APPID", "MCHID_NOT_EXIST" => "参数中缺少MCHID", "APPID_MCHID_NOT_MATCH" => "appid和mch_id不匹配", "LACK_PARAMS" => "缺少必要的请求参数", "OUT_TRADE_NO_USED" => "同一笔交易不能多次提交", "SIGNERROR" => "参数签名结果不正确", "XML_FORMAT_ERROR" => "XML格式错误", "REQUIRE_POST_METHOD" => "未使用post传递参数", "POST_DATA_EMPTY" => "post数据不能为空", "NOT_UTF8" => "未使用指定编码格式")

返回错误码定义



* Visibility: **public**
* This property is **static**.


Methods
-------


### log

    void Yao\Wxpay::log(string $method, string $message, array $context)

记录错误日志



* Visibility: **public**


#### Arguments
* $method **string** - &lt;p&gt;记录方法( 有效值 debug/info/notice/warning/error/critical )&lt;/p&gt;
* $message **string** - &lt;p&gt;日志内容&lt;/p&gt;
* $context **array** - &lt;p&gt;上下文信息&lt;/p&gt;



### __construct

    mixed Yao\Wxpay::__construct($config)

微信支付配置



* Visibility: **public**


#### Arguments
* $config **mixed**



### unifiedorder

    array Yao\Wxpay::unifiedorder(array $params)

统一下单接口

see https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1

主要请求参数 `$params` :

 - :appid              string(32)      微信分配的公众账号ID/默认从配置文件中读取
 - :mch_id             string(32)      微信支付分配的商户号(默认从配置文件中读取)
 - :notify_url         string(256)     接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。(默认从配置文件中读取)
 - :scene_info         string(256)     场景信息.(默认从配置文件中读取) 该字段用于上报支付的场景信息 ( 1，IOS移动应用 2，安卓移动应用 3，WAP网站应用 )  {"h5_info": {"type":"Wap","wap_url": "https://pay.qq.com","wap_name": "腾讯充值"}}
 - :body               string(128)     商品简单描述
 - :attach             string(127)     附加数据，在查询API和支付通知中原样返回
 - :out_trade_no       string(32)      商户系统内部的订单号,32个字符内、可包含字母
 - :total_fee          int             订单总金额，单位为分
 - :product_id         string(32)      trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
 - :openid             string(128)     trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。


成功返回数据结构:

 - :return_code        string          SUCCESS
 - :return_msg         string          OK
 - :appid              string          微信分配的公众账号ID
 - :mch_id             string          微信支付分配的商户号
 - :nonce_str          string          微信返回的随机字符串
 - :sign               string          请求签名
 - :result_code        string          业务结果 SUCCESS/FAIL
 - :prepay_id          string          微信生成的预支付回话标识，用于后续接口调用中使用，该值有效期为2小时,针对H5支付此参数无特殊用途
 - :trade_type         string          调用接口提交的交易类型，取值如下：JSAPI，NATIVE，APP，,H5支付固定传MWEB
 - :mweb_url           string          mweb_url为拉起微信支付收银台的中间页面，可通过访问该url来拉起微信客户端，完成支付, mweb_url的有效期为5分钟。

* Visibility: **public**


#### Arguments
* $params **array**



### orderquery

    array Yao\Wxpay::orderquery(array $params)

查询订单
see https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_2&index=2

主要请求参数 `$params` :

 - :appid              string(32)      微信分配的公众账号ID/默认从配置文件中读取
 - :mch_id             string(32)      微信支付分配的商户号(默认从配置文件中读取)
 - :transaction_id     string(32)      微信的订单号，建议优先使用 (微信订单号和商户订单号必填一项)
 - :out_trade_no       string(32)      商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。(微信订单号和商户订单号必填一项)


成功返回数据结构:

 - :return_code        string          SUCCESS
 - :return_msg         string          OK
 - :appid              string          微信分配的公众账号ID
 - :mch_id             string          微信支付分配的商户号
 - :nonce_str          string          微信返回的随机字符串
 - :sign               string          请求签名
 - :result_code        string          业务结果 SUCCESS/FAIL
 - :err_code           string          当result_code为FAIL时返回错误代码，详细参见 $errorCodes
 - :err_code_des       string          当result_code为FAIL时返回错误描述，详细参见下文错误列表

 其他字段参见微信文档

* Visibility: **public**


#### Arguments
* $params **array**



### notifyGet

    array Yao\Wxpay::notifyGet(string $body)

读取微信支付通知数据



* Visibility: **public**


#### Arguments
* $body **string** - &lt;p&gt;微信通知响应结果&lt;/p&gt;



### notifyResponse

    \Yao\void; Yao\Wxpay::notifyResponse($params)

回应微信支付通知



* Visibility: **public**


#### Arguments
* $params **mixed**



### json

    array Yao\Wxpay::json($body)

将结果转换为数组



* Visibility: **public**
* This method is **static**.


#### Arguments
* $body **mixed**



### getRealIP

    string Yao\Wxpay::getRealIP()

读取客户端IP地址



* Visibility: **public**
* This method is **static**.




### checkSignature

    boolean Yao\Wxpay::checkSignature(array $return_data, boolean $return)

校验数据签名



* Visibility: **private**


#### Arguments
* $return_data **array** - &lt;p&gt;微信服务器发送的请求数据&lt;/p&gt;
* $return **boolean** - &lt;p&gt;是否返回校验结果，默认为 false, 失败抛出异常&lt;/p&gt;



### paramsToXml

    string Yao\Wxpay::paramsToXml(array $params, array $cdata_fields)

转换为Xml格式



* Visibility: **public**
* This method is **static**.


#### Arguments
* $params **array** - &lt;p&gt;请求数据&lt;/p&gt;
* $cdata_fields **array** - &lt;p&gt;不需要解析的XML数据&lt;/p&gt;



### signature

    string Yao\Wxpay::signature(array $params)

生成微信支付签名



* Visibility: **private**


#### Arguments
* $params **array** - &lt;p&gt;请求参数表&lt;/p&gt;



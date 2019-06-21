Yao\Route\Request
===============

HTTP Reqeust 数据控制器




* Class name: Request
* Namespace: Yao\Route





Properties
----------


### $agent

    public string $agent = null

请求代理 weibo/wechat/wxapp/null



* Visibility: **public**


### $platform

    public mixed $platform = null

请求平台 android / ios / desktop etc



* Visibility: **public**


### $isMobile

    public mixed $isMobile = false

是否为手机端



* Visibility: **public**


### $hostName

    public string $hostName = ""

域名 xxx.com



* Visibility: **public**


### $hostSubname

    public string $hostSubname = ""

二级域名 xxxx



* Visibility: **public**


### $host

    public string $host = ""

完整域名 xxx.yyy.com



* Visibility: **public**


### $method

    public string $method = ""

HTTP Request 请求方法

GET / POST / PUT /...

* Visibility: **public**


### $requestURI

    public string $requestURI = ""

请求路由



* Visibility: **public**


### $headers

    public mixed $headers = array()

HTTP Request Headers

@var string

* Visibility: **public**


### $contentType

    public mixed $contentType = ""

HTTP Request Content-Type

@var string

* Visibility: **public**


### $payloads

    public mixed $payloads = array()

HTTP Request 提交数据

@var array

* Visibility: **public**


### $params

    public mixed $params = array()

HTTP Request Query Params

@var array

* Visibility: **public**


### $files

    public mixed $files = array()

HTTP Request file upload string

@var array

* Visibility: **public**


### $uri

    public array $uri = array()

HTTP Request 解析后的路由变量

see https://github.com/nikic/FastRoute

* Visibility: **public**


### $responseHeaders

    public mixed $responseHeaders = array()

HTTP Response Headers

@var array

* Visibility: **public**
* This property is **static**.


Methods
-------


### origin

    array Yao\Route\Request::origin()

读取平台请求来源

返回值结构

 - agent string 请求代理
     - "browser" 浏览器
     - "wechat" 微信
     - "weibo"  微博
     - "wxapp"  小程序
 - platform  string 系统平台  windows/android/ios/browser
 - mobile bool 是否为移动端请求 1=移动端 0 非移动端

* Visibility: **public**
* This method is **static**.




### addHeader

    mixed Yao\Route\Request::addHeader($name, $value)

添加 HTTP Response Header

@param $name header name

* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **mixed**
* $value **mixed** - &lt;p&gt;header value
@return void&lt;/p&gt;



### sendHeader

    void Yao\Route\Request::sendHeader()

发送 Response Header



* Visibility: **public**
* This method is **static**.




### __construct

    mixed Yao\Route\Request::__construct()

构造函数



* Visibility: **public**




### setURI

    void Yao\Route\Request::setURI($uri)

设定路由变量

@param array $uri 路由变量

* Visibility: **public**


#### Arguments
* $uri **mixed**



### setRequestData

    mixed Yao\Route\Request::setRequestData()

读取 Request 数据



* Visibility: **private**




### setMethod

    mixed Yao\Route\Request::setMethod()

读取请求方法



* Visibility: **private**




### setHeaders

    mixed Yao\Route\Request::setHeaders()

读取请求Header



* Visibility: **private**




### setRequestURI

    void Yao\Route\Request::setRequestURI()

读取请求路由



* Visibility: **private**




### setHost

    void Yao\Route\Request::setHost()

读取域名信息



* Visibility: **private**




### setOrigin

    void Yao\Route\Request::setOrigin()

读取请求来源



* Visibility: **private**




### setGlobal

    void Yao\Route\Request::setGlobal()

设定全局变量



* Visibility: **private**




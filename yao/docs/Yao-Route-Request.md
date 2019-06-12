Yao\Route\Request
===============

路由器(Base on FastRoute)




* Class name: Request
* Namespace: Yao\Route





Properties
----------


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


### $responseHeader

    public mixed $responseHeader = array()

HTTP Response Headers

@var array

* Visibility: **public**


Methods
-------


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



### addHeader

    mixed Yao\Route\Request::addHeader($name, $value)

添加 HTTP Response Header

@param $name header name

* Visibility: **public**


#### Arguments
* $name **mixed**
* $value **mixed** - &lt;p&gt;header value
@return void&lt;/p&gt;



### getRequestData

    mixed Yao\Route\Request::getRequestData()

读取 Request 数据



* Visibility: **private**




### getMethod

    mixed Yao\Route\Request::getMethod()

读取请求方法



* Visibility: **private**




### getHeaders

    mixed Yao\Route\Request::getHeaders()

读取请求Header



* Visibility: **private**




### getRequestURI

    mixed Yao\Route\Request::getRequestURI()

读取请求路由



* Visibility: **private**




### getHost

    mixed Yao\Route\Request::getHost()

读取域名信息



* Visibility: **private**




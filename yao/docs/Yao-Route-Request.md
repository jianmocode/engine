Yao\Route\Request
===============

路由器(Base on FastRoute)




* Class name: Request
* Namespace: Yao\Route





Properties
----------


### $hostName

    public mixed $hostName = ""





* Visibility: **public**


### $host

    public mixed $host = ""





* Visibility: **public**


### $method

    public mixed $method = ""





* Visibility: **public**


### $requestURI

    public mixed $requestURI = ""





* Visibility: **public**


### $headers

    public mixed $headers = array()





* Visibility: **public**


### $contentType

    public mixed $contentType = ""





* Visibility: **public**


### $payloads

    public mixed $payloads = array()





* Visibility: **public**


### $params

    public mixed $params = array()





* Visibility: **public**


### $files

    public mixed $files = array()





* Visibility: **public**


### $uri

    public mixed $uri = array()





* Visibility: **public**


### $responseHeader

    public mixed $responseHeader = array()





* Visibility: **public**


Methods
-------


### __construct

    mixed Yao\Route\Request::__construct()

构造函数



* Visibility: **public**




### setURI

    mixed Yao\Route\Request::setURI($uri)





* Visibility: **public**


#### Arguments
* $uri **mixed**



### addHeader

    mixed Yao\Route\Request::addHeader($name, $value)





* Visibility: **public**


#### Arguments
* $name **mixed**
* $value **mixed**



### getRequestData

    mixed Yao\Route\Request::getRequestData()





* Visibility: **private**




### getMethod

    mixed Yao\Route\Request::getMethod()





* Visibility: **private**




### getHeaders

    mixed Yao\Route\Request::getHeaders()





* Visibility: **private**




### getRequestURI

    mixed Yao\Route\Request::getRequestURI()





* Visibility: **private**




### getHost

    mixed Yao\Route\Request::getHost()





* Visibility: **private**




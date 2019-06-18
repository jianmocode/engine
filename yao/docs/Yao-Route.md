Yao\Route
===============

路由器(Base on FastRoute)

see https://github.com/nikic/FastRoute


* Class name: Route
* Namespace: Yao





Properties
----------


### $groupMapping

    protected mixed $groupMapping

路由设定文件寻址



* Visibility: **protected**
* This property is **static**.


### $routingTable

    protected mixed $routingTable = array()

路由表



* Visibility: **protected**
* This property is **static**.


Methods
-------


### __construct

    mixed Yao\Route::__construct()

构造函数



* Visibility: **public**




### setGroups

    void Yao\Route::setGroups(array $groupMapping)

设定路由文件寻址



* Visibility: **public**
* This method is **static**.


#### Arguments
* $groupMapping **array** - &lt;p&gt;路由设定文件&lt;/p&gt;



### exec

    mixed Yao\Route::exec($uri, $params)

运行路由并返回数值



* Visibility: **public**
* This method is **static**.


#### Arguments
* $uri **mixed**
* $params **mixed**



### run

    mixed Yao\Route::run()

运行路由



* Visibility: **public**
* This method is **static**.




### get

    void Yao\Route::get(string $uri, callable $callback, integer $tls)

设定 HTTP GET 路由表



* Visibility: **public**
* This method is **static**.


#### Arguments
* $uri **string** - &lt;p&gt;路由信息&lt;/p&gt;
* $callback **callable** - &lt;p&gt;回调函数 function( \Yao\Route\Request $r ){}&lt;/p&gt;
* $tls **integer** - &lt;p&gt;数据缓存时长&lt;/p&gt;



### post

    void Yao\Route::post(string $uri, callable $callback, integer $tls)

设定 HTTP POST 路由表



* Visibility: **public**
* This method is **static**.


#### Arguments
* $uri **string** - &lt;p&gt;路由信息&lt;/p&gt;
* $callback **callable** - &lt;p&gt;回调函数 function( \Yao\Route\Request $r ){}&lt;/p&gt;
* $tls **integer** - &lt;p&gt;数据缓存时长&lt;/p&gt;



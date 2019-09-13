Yao\Appium
===============

Appium Client
see https://github.com/guzzle/guzzle




* Class name: Appium
* Namespace: Yao





Properties
----------


### $config

    private mixed $config = array()

配置信息



* Visibility: **private**


Methods
-------


### __construct

    mixed Yao\Appium::__construct(array $config)

Appium Client



* Visibility: **public**


#### Arguments
* $config **array**



### url

    mixed Yao\Appium::url($api)





* Visibility: **public**


#### Arguments
* $api **mixed**



### get

    array Yao\Appium::get(string $api, array $params, array $body)

GET 方法调用



* Visibility: **public**


#### Arguments
* $api **string** - &lt;p&gt;API 名称&lt;/p&gt;
* $params **array** - &lt;p&gt;查询参数&lt;/p&gt;
* $body **array**



### post

    array Yao\Appium::post(string $api, array $data, array $params)

POST 方法调用



* Visibility: **public**


#### Arguments
* $api **string** - &lt;p&gt;API 名称&lt;/p&gt;
* $data **array** - &lt;p&gt;请求参数&lt;/p&gt;
* $params **array** - &lt;p&gt;查询参数&lt;/p&gt;



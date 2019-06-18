Yao\Http
===============

Http Client
see https://github.com/guzzle/guzzle




* Class name: Http
* Namespace: Yao





Properties
----------


### $client

    public mixed $client = null

predis 实例



* Visibility: **public**
* This property is **static**.


Methods
-------


### json

    \Yao\mix Yao\Http::json(\Psr\Http\Message\ResponseInterface $response)

返回JSON数据



* Visibility: **public**
* This method is **static**.


#### Arguments
* $response **Psr\Http\Message\ResponseInterface** - &lt;p&gt;PSR Http Response Struct&lt;/p&gt;



### __callStatic

    mixed Yao\Http::__callStatic(string $method, array $parameters)

Pass methods onto the default Redis connection.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**



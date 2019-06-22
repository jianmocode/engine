Yao\Redis
===============

Redis
see https://laravel.com/docs/5.8/redis




* Class name: Redis
* Namespace: Yao





Properties
----------


### $predis

    public mixed $predis = null

predis 实例



* Visibility: **public**
* This property is **static**.


Methods
-------


### __construct

    mixed Yao\Redis::__construct(\Yao\Container $container)

创建 Redis 协议



* Visibility: **public**


#### Arguments
* $container **Yao\Container**



### connect

    mixed Yao\Redis::connect()

连接 Redis Server



* Visibility: **public**
* This method is **static**.




### set

    boolean Yao\Redis::set($key, $value, $ttl)

设定缓存数据



* Visibility: **public**
* This method is **static**.


#### Arguments
* $key **mixed**
* $value **mixed**
* $ttl **mixed**



### __callStatic

    mixed Yao\Redis::__callStatic(string $method, array $parameters)

Pass methods onto the default Redis connection.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**



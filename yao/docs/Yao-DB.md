Yao\DB
===============

数据库
see https://laravel.com/docs/5.8/database




* Class name: DB
* Namespace: Yao
* Parent class: Illuminate\Database\Capsule\Manager





Properties
----------


### $isconnected

    public mixed $isconnected = false

连接标记



* Visibility: **public**
* This property is **static**.


Methods
-------


### __construct

    mixed Yao\DB::__construct(\Illuminate\Container\Container $container)

数据库对象



* Visibility: **public**


#### Arguments
* $container **Illuminate\Container\Container**



### connect

    mixed Yao\DB::connect()

连接数据库



* Visibility: **public**
* This method is **static**.




### connectAsync

    \Swoole\Coroutine\MySQL Yao\DB::connectAsync(string $type)

[异步]连接数据库



* Visibility: **public**
* This method is **static**.


#### Arguments
* $type **string** - &lt;p&gt;write = 写连接, read = 读连接&lt;/p&gt;



### config

    mixed Yao\DB::config()

读取数据库配置



* Visibility: **public**
* This method is **static**.




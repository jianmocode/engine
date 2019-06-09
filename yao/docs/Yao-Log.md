Yao\Log
===============

日志对象

示例

```php
<?php
use \Yao\Log;

$log = new log('access');

// add records to the log
$log->warning('Foo');
$log->error('Bar');

```

配置文件

```php
"logger" =>[
     "access" =>["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-access.log", 'debug']],
     "error" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-error.log", 'debug']],
     "debug" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-debug.log", 'debug']],
     ...
     ":channel" => ["handler"=>":CLASS", "args"=>[...:arg]]
],
```


* Class name: Log
* Namespace: Yao
* Parent class: Monolog\Logger







Methods
-------


### __construct

    \Yao\Log Yao\Log::__construct(string $name)

构造函数



* Visibility: **public**


#### Arguments
* $name **string** - &lt;p&gt;日志通道&lt;/p&gt;



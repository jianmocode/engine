Yao\Log
===============

日志

示例

```php
<?php
use \Yao\Log;

$log = new log('access');

// $log = Log::write('access');

// add records to the log
$log->debug('message', ['foo', 'bar']);
$log->info('message');
$log->notice('message', ['foo', 'bar']);
$log->warning('Foo', ['foo', 'bar']);
$log->error('Bar', ['foo', 'bar']);
$log->critical('message', ['foo', 'bar']);
$log->alert('message', ['foo', 'bar']);
$log->emergency('message', ['foo', 'bar']);

```

配置 `/yao/config.inc.php`

```php
...
"logger" =>[
     "access" =>["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-access.log", 'debug']],
     "error" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-error.log", 'debug']],
     "debug" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-debug.log", 'debug']],
     ...
     ":channel" => ["handler"=>":CLASS", "args"=>[...:arg]]
],
...
```

- see https://github.com/Seldaek/monolog
- see https://github.com/php-fig/log/blob/master/Psr/Log/LoggerInterface.php


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



### write

    \Yao\Log Yao\Log::write(string $name)

创建Log对象



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **string** - &lt;p&gt;日志通道&lt;/p&gt;



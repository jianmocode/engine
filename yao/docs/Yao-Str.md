Yao\Str
===============

字符串处理迅捷函数

see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Support/Str.php


* Class name: Str
* Namespace: Yao
* Parent class: Illuminate\Support\Str







Methods
-------


### explodeAndTrim

    array Yao\Str::explodeAndTrim(string $delimiter, string|array $data)

如果输入字符串, 将用分隔符分隔的字符串转换为数组, 同时去掉每一项首位空行.

如果输入字符串数组, 去掉每一项首尾空行.

* Visibility: **public**
* This method is **static**.


#### Arguments
* $delimiter **string** - &lt;p&gt;数据分割符&lt;/p&gt;
* $data **string|array** - &lt;p&gt;输入数据。&lt;/p&gt;



### uniqid

    string Yao\Str::uniqid()

生成一个由数字组成的唯一ID



* Visibility: **public**
* This method is **static**.




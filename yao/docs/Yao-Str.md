Yao\Str
===============

字符串处理迅捷函数

see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Support/Str.php


* Class name: Str
* Namespace: Yao
* Parent class: Illuminate\Support\Str







Methods
-------


### explodeTo2DArray

    array Yao\Str::explodeTo2DArray(string $input, string $array_delimiter, string $object_delimiter, array $columns)

将字符串转换为二维数组



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;待转换字符串&lt;/p&gt;
* $array_delimiter **string** - &lt;p&gt;数组分割字符&lt;/p&gt;
* $object_delimiter **string** - &lt;p&gt;Object 分割字符&lt;/p&gt;
* $columns **array** - &lt;p&gt;数组项字段映射，为空数组则转换为数组&lt;/p&gt;



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




### binds

    mixed Yao\Str::binds(array $input, array $bindings)

替换 `{{key}}` 为 bindings 设定的数值



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **array** - &lt;p&gt;输入字符串&lt;/p&gt;
* $bindings **array** - &lt;p&gt;绑定数据&lt;/p&gt;



### isURL

    boolean Yao\Str::isURL(string $input)

检查输入的字符串是否为URL



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



### forceHttps

    string Yao\Str::forceHttps(string $input)

强制转换为 Https 协议



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



### isPath

    boolean Yao\Str::isPath(string $input)

检查输入的字符串是否为PATH



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



### isDomain

    boolean Yao\Str::isDomain(string $input)

检查输入的字符串是否为域名



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



### isEmail

    boolean Yao\Str::isEmail(string $input)

检查输入的字符串是否为 Email



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



### isIP

    boolean Yao\Str::isIP(string $input)

检查输入的字符串是否为IP地址



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **string** - &lt;p&gt;输入的字符串&lt;/p&gt;



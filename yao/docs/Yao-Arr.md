Yao\Arr
===============

数组处理迅捷函数

see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Support/Arr.php


* Class name: Arr
* Namespace: Yao
* Parent class: Illuminate\Support\Arr







Methods
-------


### isAssoc

    mixed Yao\Arr::isAssoc(array $arr)

检查是否为 Key-Value 结构数组



* Visibility: **public**
* This method is **static**.


#### Arguments
* $arr **array**



### defaults

    void Yao\Arr::defaults(array $input, array $defaults)

设定数组默认值



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **array** - &lt;p&gt;输入数组引用&lt;/p&gt;
* $defaults **array** - &lt;p&gt;默认数值&lt;/p&gt;



### binds

    void Yao\Arr::binds(array $input, array $bindings)

替换 `{{key}}` 为 bindings 设定数值



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **array** - &lt;p&gt;&amp;$input 输入数组引用&lt;/p&gt;
* $bindings **array** - &lt;p&gt;绑定数据&lt;/p&gt;



### explode

    array Yao\Arr::explode(array $input)

还原数组 first.second.third  >  $arr["first"]["second"]["third"]



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **array** - &lt;p&gt;输入数组引用&lt;/p&gt;



### mapBy

    array Yao\Arr::mapBy(string $field, array $input)

将二维数组转换为以唯一字段为主键的键值结构

示例 :

[
  ["key1"=>"value1"],["key2"=>"value2"],
]

转换为

[
  "key1" => ["key1"=>"value1"],
  ”key2“ => ["key2"=>"value2"],
]

* Visibility: **public**
* This method is **static**.


#### Arguments
* $field **string** - &lt;p&gt;唯一主键字段名称&lt;/p&gt;
* $input **array** - &lt;p&gt;二维数组&lt;/p&gt;



### mapAndMergeBy

    array Yao\Arr::mapAndMergeBy(string $field, array $array, array $arrayN)

合并并将二维数组转换为以唯一字段为主键的键值结构 (待优化)



* Visibility: **public**
* This method is **static**.


#### Arguments
* $field **string** - &lt;p&gt;唯一主键字段名称&lt;/p&gt;
* $array **array** - &lt;p&gt;二维数组&lt;/p&gt;
* $arrayN **array** - &lt;p&gt;二维数组&lt;/p&gt;



### groupBy

    array Yao\Arr::groupBy(string $field, array $input)

按分组查询数据



* Visibility: **public**
* This method is **static**.


#### Arguments
* $field **string** - &lt;p&gt;字段名称&lt;/p&gt;
* $input **array** - &lt;p&gt;数组&lt;/p&gt;



### varize

    void Yao\Arr::varize(array $input)

给数组 key 添加 {{}}



* Visibility: **public**
* This method is **static**.


#### Arguments
* $input **array** - &lt;p&gt;输入数组引用&lt;/p&gt;



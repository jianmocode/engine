Yao\Utils
===============

常用方法




* Class name: Utils
* Namespace: Yao







Methods
-------


### json_decode

    \Yao\mix Yao\Utils::json_decode(string $json, integer $flag)

解析JSON字符串，可以准确通报错误 （ 但效率较低 )



* Visibility: **public**
* This method is **static**.


#### Arguments
* $json **string** - &lt;p&gt;JSON字符串或文件&lt;/p&gt;
* $flag **integer** - &lt;p&gt;默认为 0
DETECT_KEY_CONFLICTS 删除重复键
ALLOW_DUPLICATE_KEYS 允许重复键
PARSE_TO_ASSOC 解析为 OBJECT
EG:  PARSE_TO_ASSOC &amp; DETECT_KEY_CONFLICTS&lt;/p&gt;



### json_error

    string Yao\Utils::json_error()

读取JSON解析错误



* Visibility: **public**
* This method is **static**.




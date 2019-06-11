Yao\Observer
===============

数据模型监听器




* Class name: Observer
* Namespace: Yao





Properties
----------


### $publicURL

    protected mixed $publicURL = YAO_PUBLIC_URL

公开文件访问路径



* Visibility: **protected**


### $privateURL

    protected mixed $privateURL = YAO_PRIVATE_URL

私密的访问路径



* Visibility: **protected**


### $publicFiles

    protected mixed $publicFiles = array()

公开文件字段



* Visibility: **protected**


### $privateFiles

    protected mixed $privateFiles = array()

私有文件字段



* Visibility: **protected**


### $filePrefix

    protected mixed $filePrefix = ""

文件前缀



* Visibility: **protected**


Methods
-------


### filesInput

    mixed Yao\Observer::filesInput($model)

处理输入文件类字段



* Visibility: **protected**


#### Arguments
* $model **mixed**



### getFiles

    mixed Yao\Observer::getFiles()

读取文件字段清单



* Visibility: **private**




### writeFile

    string Yao\Observer::writeFile($path, $private)

保存文件



* Visibility: **protected**


#### Arguments
* $path **mixed** - &lt;p&gt;文件路径&lt;/p&gt;
* $private **mixed** - &lt;p&gt;是否为私有文件&lt;/p&gt;



### filesOutput

    mixed Yao\Observer::filesOutput($model)

处理输入文件类字段读取



* Visibility: **protected**


#### Arguments
* $model **mixed**



### creating

    void Yao\Observer::creating($store)

Handle the User "created" event.



* Visibility: **public**


#### Arguments
* $store **mixed**



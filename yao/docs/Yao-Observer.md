Yao\Observer
===============

数据模型监听器




* Class name: Observer
* Namespace: Yao





Properties
----------


### $files

    protected mixed $files = array()

文件字段



* Visibility: **protected**


Methods
-------


### filesInput

    mixed Yao\Observer::filesInput($model)

处理输入文件类字段



* Visibility: **protected**


#### Arguments
* $model **mixed**



### writeFile

    mixed Yao\Observer::writeFile($path)

保存文件



* Visibility: **protected**


#### Arguments
* $path **mixed** - &lt;p&gt;文件路径&lt;/p&gt;



### filesOutput

    mixed Yao\Observer::filesOutput($model)

处理输入文件类字段读取



* Visibility: **protected**


#### Arguments
* $model **mixed**



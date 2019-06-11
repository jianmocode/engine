Yao\Model
===============

数据模型
see https://laravel.com/docs/5.8/eloquent




* Class name: Model
* Namespace: Yao
* Parent class: Illuminate\Database\Eloquent\Model





Properties
----------


### $publicURL

    public mixed $publicURL = YAO_PUBLIC_URL

公开文件访问路径



* Visibility: **public**


### $privateURL

    public mixed $privateURL = YAO_PRIVATE_URL

私密的访问路径



* Visibility: **public**


### $publicFiles

    public mixed $publicFiles = array()

公开文件字段



* Visibility: **public**


### $privateFiles

    public mixed $privateFiles = array()

私有文件字段



* Visibility: **public**


### $filePrefix

    public mixed $filePrefix = ""

文件前缀



* Visibility: **public**


Methods
-------


### filesInput

    void Yao\Model::filesInput()

处理输入文件类字段



* Visibility: **public**




### getFiles

    array Yao\Model::getFiles()

读取文件字段清单



* Visibility: **private**




### writeFile

    string Yao\Model::writeFile($path, $private, $perfix)

保存文件



* Visibility: **private**


#### Arguments
* $path **mixed** - &lt;p&gt;文件路径&lt;/p&gt;
* $private **mixed** - &lt;p&gt;是否为私有文件&lt;/p&gt;
* $perfix **mixed** - &lt;p&gt;文件前缀&lt;/p&gt;



### pathToURL

    void Yao\Model::pathToURL(array $exclude)

处理输入文件类字段读取



* Visibility: **public**


#### Arguments
* $exclude **array** - &lt;p&gt;排除字段&lt;/p&gt;



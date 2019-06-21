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


### $generateId

    public mixed $generateId = null

创建时生成的数字类型ID



* Visibility: **public**


Methods
-------


### filesInput

    void Yao\Model::filesInput()

处理输入文件类字段



* Visibility: **public**




### pathToURL

    void Yao\Model::pathToURL(array $exclude)

处理输入文件类字段读取



* Visibility: **public**


#### Arguments
* $exclude **array** - &lt;p&gt;排除字段&lt;/p&gt;



### paginator

    \Illuminate\Contracts\Pagination\LengthAwarePaginator Yao\Model::paginator(\Illuminate\Database\Eloquent\Builder $qb, integer|string $page, integer|string $perpage, array $params, array $pageName, array $columns)

分页查询结果集



* Visibility: **public**
* This method is **static**.


#### Arguments
* $qb **Illuminate\Database\Eloquent\Builder** - &lt;p&gt;查询器实例&lt;/p&gt;
* $page **integer|string** - &lt;p&gt;当前页码, 默认为 1&lt;/p&gt;
* $perpage **integer|string** - &lt;p&gt;每页显示记录数量, 默认为 20&lt;/p&gt;
* $params **array** - &lt;p&gt;查询参数&lt;/p&gt;
* $pageName **array** - &lt;p&gt;分页参数名称&lt;/p&gt;
* $columns **array** - &lt;p&gt;查询结果集&lt;/p&gt;



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



### debug

    mixed Yao\Model::debug($message, $context)

打印调试信息



* Visibility: **public**
* This method is **static**.


#### Arguments
* $message **mixed**
* $context **mixed**



### fieldsFilter

    array Yao\Model::fieldsFilter(array|string $select, array|string $allow_fields)

根据选择字段和许可清单，过滤选择字段



* Visibility: **public**
* This method is **static**.


#### Arguments
* $select **array|string** - &lt;p&gt;字段列表 foo0,foo1.bar1,foo1.bar2&lt;/p&gt;
* $allow_fields **array|string** - &lt;p&gt;许可数值清单 foo0,foo1.bar1,foo1.bar2&lt;/p&gt;



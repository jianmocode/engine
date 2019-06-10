Yao\MySQL\Query\Builder
===============

MySQL 查询构造器




* Class name: Builder
* Namespace: Yao\MySQL\Query







Methods
-------


### __construct

    mixed Yao\MySQL\Query\Builder::__construct()

构造函数



* Visibility: **public**




### where

    mixed Yao\MySQL\Query\Builder::where($column, $operator, $value, $boolean)





* Visibility: **public**


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### whereRaw

    mixed Yao\MySQL\Query\Builder::whereRaw($expression, array $bindings)





* Visibility: **public**


#### Arguments
* $expression **mixed**
* $bindings **array**



### match

    mixed Yao\MySQL\Query\Builder::match($column, $value, $boolean)





* Visibility: **public**


#### Arguments
* $column **mixed**
* $value **mixed**
* $boolean **mixed**



### join

    mixed Yao\MySQL\Query\Builder::join($table, $f1, $op, $f2, $type, $where)





* Visibility: **public**


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**
* $type **mixed**
* $where **mixed**



### leftjoin

    mixed Yao\MySQL\Query\Builder::leftjoin($table, $f1, $op, $f2)





* Visibility: **public**


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**



### rightjoin

    mixed Yao\MySQL\Query\Builder::rightjoin($table, $f1, $op, $f2)





* Visibility: **public**


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**



### select

    mixed Yao\MySQL\Query\Builder::select($columns)





* Visibility: **public**


#### Arguments
* $columns **mixed**



### selectRaw

    mixed Yao\MySQL\Query\Builder::selectRaw($expression, array $bindings)





* Visibility: **public**


#### Arguments
* $expression **mixed**
* $bindings **array**



### paginate

    mixed Yao\MySQL\Query\Builder::paginate($perPage, $columns, $pageName, $page)





* Visibility: **public**


#### Arguments
* $perPage **mixed**
* $columns **mixed**
* $pageName **mixed**
* $page **mixed**



### get

    mixed Yao\MySQL\Query\Builder::get($columns)





* Visibility: **public**


#### Arguments
* $columns **mixed**



### count

    mixed Yao\MySQL\Query\Builder::count($columns)





* Visibility: **public**


#### Arguments
* $columns **mixed**



### toArray

    mixed Yao\MySQL\Query\Builder::toArray()





* Visibility: **public**




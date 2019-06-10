Yao\MySQL\Query\JoinClause
===============

JoinClause

(Copy From \Illuminate\Database\Query\JoinClause )

see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/JoinClause.php


* Class name: JoinClause
* Namespace: Yao\MySQL\Query
* Parent class: [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)





Properties
----------


### $type

    public string $type

The type of join being performed.



* Visibility: **public**


### $table

    public string $table

The table the join clause is joining to.



* Visibility: **public**


### $parentQuery

    private \Yao\MySQL\Query\Builder $parentQuery

The parent query builder instance.



* Visibility: **private**


### $connection

    protected \Yao\MySQL\Driver $connection

当前查询符



* Visibility: **protected**


### $grammar

    protected \Yao\MySQL\Query\Grammar $grammar

The database query grammar instance.



* Visibility: **protected**


### $bindings

    protected array $bindings = array('select' => array(), 'join' => array(), 'where' => array(), 'having' => array(), 'order' => array(), 'union' => array())

当前查询绑定数据.



* Visibility: **protected**


### $operators

    protected array $operators = array('=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'like binary', 'not like', 'between', 'ilike', '&', '|', '^', '<<', '>>', 'rlike', 'regexp', 'not regexp', '~', '~*', '!~', '!~*', 'similar to', 'not similar to', 'not ilike', '~~*', '!~~*')

有效操作符



* Visibility: **protected**


### $columns

    public array $columns

返回的字段清单



* Visibility: **public**


### $distinct

    public boolean $distinct = false

distinct 标记



* Visibility: **public**


### $from

    public string $from

查询的数表名



* Visibility: **public**


### $joins

    public array $joins

Join 查询条件



* Visibility: **public**


### $wheres

    public array $wheres

Where 查询条件



* Visibility: **public**


### $groups

    public array $groups

Group 查询条件



* Visibility: **public**


### $havings

    public array $havings

Having 查询条件



* Visibility: **public**


### $orders

    public array $orders

Order 排序条件



* Visibility: **public**


### $limit

    public integer $limit

MySQL Limit



* Visibility: **public**


### $offset

    public integer $offset

MySQL Offset



* Visibility: **public**


### $unions

    public array $unions

Union 查询条件



* Visibility: **public**


### $unionLimit

    public integer $unionLimit

Union 记录最大值



* Visibility: **public**


### $unionOffset

    public integer $unionOffset

Union 记录偏移量



* Visibility: **public**


### $unionOrders

    public array $unionOrders

Union 查询排序



* Visibility: **public**


### $lock

    public string $lock

Lock 标记



* Visibility: **public**


### $backups

    protected array $backups = array()

当前查询用到的字段备份



* Visibility: **protected**


### $bindingBackups

    protected array $bindingBackups = array()

当前查询绑定字段备份



* Visibility: **protected**


Methods
-------


### __construct

    mixed Yao\MySQL\Query\Builder::__construct($connection, \Yao\MySQL\Query\Grammar $grammar)

构造查询器



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $connection **mixed**
* $grammar **[Yao\MySQL\Query\Grammar](Yao-MySQL-Query-Grammar.md)**



### on

    \Yao\MySQL\Query\JoinClause Yao\MySQL\Query\JoinClause::on(\Closure|string $first, string|null $operator, string|null $second, string $boolean)

Add an "on" clause to the join.

On clauses can be chained, e.g.

 $join->on('contacts.user_id', '=', 'users.id')
      ->on('contacts.info_id', '=', 'info.id')

will produce the following SQL:

on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`

* Visibility: **public**


#### Arguments
* $first **Closure|string**
* $operator **string|null**
* $second **string|null**
* $boolean **string**



### orOn

    \Illuminate\Database\Query\JoinClause Yao\MySQL\Query\JoinClause::orOn(\Closure|string $first, string|null $operator, string|null $second)

Add an "or on" clause to the join.



* Visibility: **public**


#### Arguments
* $first **Closure|string**
* $operator **string|null**
* $second **string|null**



### newQuery

    mixed Yao\MySQL\Query\Builder::newQuery()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### select

    \Yao\MySQL\Query\Builder Yao\MySQL\Query\Builder::select(array|mixed $columns)

设定选择字段



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $columns **array|mixed** - &lt;p&gt;字段清单&lt;/p&gt;



### selectRaw

    \Yao\MySQL\Builder Yao\MySQL\Query\Builder::selectRaw(string $expression, array $bindings)

添加表达式查询字段



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $expression **string** - &lt;p&gt;查询表达式&lt;/p&gt;
* $bindings **array** - &lt;p&gt;绑定数据集合&lt;/p&gt;



### selectSub

    \Yao\MySQL\Builder Yao\MySQL\Query\Builder::selectSub(\Yao\MySQL\Builder|string $query, string $as)

添加子查询表达式



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **Yao\MySQL\Builder|string**
* $as **string**



### addSelect

    \Yao\MySQL\Query\Builder Yao\MySQL\Query\Builder::addSelect(array|mixed $column)

添加查询字段



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **array|mixed**



### distinct

    \Yao\MySQL\Query\Builder Yao\MySQL\Query\Builder::distinct()

Force the query to only return distinct results.



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### from

    mixed Yao\MySQL\Query\Builder::from($table)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**



### join

    mixed Yao\MySQL\Query\Builder::join($table, $f1, $op, $f2, $type, $where)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**
* $type **mixed**
* $where **mixed**



### joinWhere

    mixed Yao\MySQL\Query\Builder::joinWhere($table, $one, $operator, $two, $type)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $one **mixed**
* $operator **mixed**
* $two **mixed**
* $type **mixed**



### leftjoin

    mixed Yao\MySQL\Query\Builder::leftjoin($table, $f1, $op, $f2)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**



### leftJoinWhere

    mixed Yao\MySQL\Query\Builder::leftJoinWhere($table, $one, $operator, $two)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $one **mixed**
* $operator **mixed**
* $two **mixed**



### rightjoin

    mixed Yao\MySQL\Query\Builder::rightjoin($table, $f1, $op, $f2)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $f1 **mixed**
* $op **mixed**
* $f2 **mixed**



### rightJoinWhere

    mixed Yao\MySQL\Query\Builder::rightJoinWhere($table, $one, $operator, $two)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $one **mixed**
* $operator **mixed**
* $two **mixed**



### crossJoin

    mixed Yao\MySQL\Query\Builder::crossJoin($table, $first, $operator, $second)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $table **mixed**
* $first **mixed**
* $operator **mixed**
* $second **mixed**



### when

    mixed Yao\MySQL\Query\Builder::when($value, $callback, $default)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**
* $callback **mixed**
* $default **mixed**



### where

    mixed Yao\MySQL\Query\Builder::where($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### orWhere

    mixed Yao\MySQL\Query\Builder::orWhere($column, $operator, $value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**



### whereColumn

    mixed Yao\MySQL\Query\Builder::whereColumn($first, $operator, $second, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $first **mixed**
* $operator **mixed**
* $second **mixed**
* $boolean **mixed**



### orWhereColumn

    mixed Yao\MySQL\Query\Builder::orWhereColumn($first, $operator, $second)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $first **mixed**
* $operator **mixed**
* $second **mixed**



### whereRaw

    mixed Yao\MySQL\Query\Builder::whereRaw($expression, array $bindings)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $expression **mixed**
* $bindings **array**



### orWhereRaw

    mixed Yao\MySQL\Query\Builder::orWhereRaw($sql, array $bindings)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $sql **mixed**
* $bindings **array**



### whereBetween

    mixed Yao\MySQL\Query\Builder::whereBetween($column, array $values, $boolean, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **array**
* $boolean **mixed**
* $not **mixed**



### orWhereBetween

    mixed Yao\MySQL\Query\Builder::orWhereBetween($column, array $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **array**



### whereNotBetween

    mixed Yao\MySQL\Query\Builder::whereNotBetween($column, array $values, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **array**
* $boolean **mixed**



### orWhereNotBetween

    mixed Yao\MySQL\Query\Builder::orWhereNotBetween($column, array $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **array**



### whereNested

    mixed Yao\MySQL\Query\Builder::whereNested(\Yao\MySQL\Query\Closure $callback, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **Yao\MySQL\Query\Closure**
* $boolean **mixed**



### forNestedWhere

    mixed Yao\MySQL\Query\Builder::forNestedWhere()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### addNestedWhereQuery

    mixed Yao\MySQL\Query\Builder::addNestedWhereQuery($query, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **mixed**
* $boolean **mixed**



### whereExists

    mixed Yao\MySQL\Query\Builder::whereExists(\Yao\MySQL\Query\Closure $callback, $boolean, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **Yao\MySQL\Query\Closure**
* $boolean **mixed**
* $not **mixed**



### orWhereExists

    mixed Yao\MySQL\Query\Builder::orWhereExists(\Yao\MySQL\Query\Closure $callback, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **Yao\MySQL\Query\Closure**
* $not **mixed**



### whereNotExists

    mixed Yao\MySQL\Query\Builder::whereNotExists(\Yao\MySQL\Query\Closure $callback, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **Yao\MySQL\Query\Closure**
* $boolean **mixed**



### orWhereNotExists

    mixed Yao\MySQL\Query\Builder::orWhereNotExists(\Yao\MySQL\Query\Closure $callback)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **Yao\MySQL\Query\Closure**



### addWhereExistsQuery

    mixed Yao\MySQL\Query\Builder::addWhereExistsQuery(self $query, $boolean, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **self**
* $boolean **mixed**
* $not **mixed**



### whereIn

    mixed Yao\MySQL\Query\Builder::whereIn($column, $values, $boolean, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **mixed**
* $boolean **mixed**
* $not **mixed**



### orWhereIn

    mixed Yao\MySQL\Query\Builder::orWhereIn($column, $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **mixed**



### whereNotIn

    mixed Yao\MySQL\Query\Builder::whereNotIn($column, $values, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **mixed**
* $boolean **mixed**



### orWhereNotIn

    mixed Yao\MySQL\Query\Builder::orWhereNotIn($column, $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $values **mixed**



### whereNull

    mixed Yao\MySQL\Query\Builder::whereNull($column, $boolean, $not)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $boolean **mixed**
* $not **mixed**



### orWhereNull

    mixed Yao\MySQL\Query\Builder::orWhereNull($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### whereNotNull

    mixed Yao\MySQL\Query\Builder::whereNotNull($column, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $boolean **mixed**



### orWhereNotNull

    mixed Yao\MySQL\Query\Builder::orWhereNotNull($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### whereDate

    mixed Yao\MySQL\Query\Builder::whereDate($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### orWhereDate

    mixed Yao\MySQL\Query\Builder::orWhereDate($column, $operator, $value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**



### whereTime

    mixed Yao\MySQL\Query\Builder::whereTime($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### orWhereTime

    mixed Yao\MySQL\Query\Builder::orWhereTime($column, $operator, $value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**



### whereDay

    mixed Yao\MySQL\Query\Builder::whereDay($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### whereMonth

    mixed Yao\MySQL\Query\Builder::whereMonth($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### whereYear

    mixed Yao\MySQL\Query\Builder::whereYear($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### dynamicWhere

    mixed Yao\MySQL\Query\Builder::dynamicWhere($method, $parameters)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $method **mixed**
* $parameters **mixed**



### whereMatch

    mixed Yao\MySQL\Query\Builder::whereMatch($column, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $value **mixed**
* $boolean **mixed**



### orWhereMatch

    mixed Yao\MySQL\Query\Builder::orWhereMatch($column, $value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $value **mixed**



### groupBy

    mixed Yao\MySQL\Query\Builder::groupBy($groups)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $groups **mixed**



### having

    mixed Yao\MySQL\Query\Builder::having($column, $operator, $value, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**
* $boolean **mixed**



### orHaving

    mixed Yao\MySQL\Query\Builder::orHaving($column, $operator, $value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $operator **mixed**
* $value **mixed**



### havingRaw

    mixed Yao\MySQL\Query\Builder::havingRaw($sql, array $bindings, $boolean)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $sql **mixed**
* $bindings **array**
* $boolean **mixed**



### orHavingRaw

    mixed Yao\MySQL\Query\Builder::orHavingRaw($sql, array $bindings)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $sql **mixed**
* $bindings **array**



### orderBy

    mixed Yao\MySQL\Query\Builder::orderBy($column, $direction)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $direction **mixed**



### latest

    mixed Yao\MySQL\Query\Builder::latest($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### oldest

    mixed Yao\MySQL\Query\Builder::oldest($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### inRandomOrder

    mixed Yao\MySQL\Query\Builder::inRandomOrder($seed)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $seed **mixed**



### orderByRaw

    mixed Yao\MySQL\Query\Builder::orderByRaw($sql, $bindings)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $sql **mixed**
* $bindings **mixed**



### offset

    mixed Yao\MySQL\Query\Builder::offset($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### skip

    mixed Yao\MySQL\Query\Builder::skip($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### limit

    mixed Yao\MySQL\Query\Builder::limit($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### take

    mixed Yao\MySQL\Query\Builder::take($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### forPage

    mixed Yao\MySQL\Query\Builder::forPage($page, $perPage)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $page **mixed**
* $perPage **mixed**



### forPageAfterId

    mixed Yao\MySQL\Query\Builder::forPageAfterId($perPage, $lastId, $column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $perPage **mixed**
* $lastId **mixed**
* $column **mixed**



### union

    mixed Yao\MySQL\Query\Builder::union($query, $all)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **mixed**
* $all **mixed**



### unionAll

    mixed Yao\MySQL\Query\Builder::unionAll($query)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **mixed**



### lock

    mixed Yao\MySQL\Query\Builder::lock($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### lockForUpdate

    mixed Yao\MySQL\Query\Builder::lockForUpdate()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### sharedLock

    mixed Yao\MySQL\Query\Builder::sharedLock()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### toSql

    mixed Yao\MySQL\Query\Builder::toSql()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### find

    mixed Yao\MySQL\Query\Builder::find($id, $columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $id **mixed**
* $columns **mixed**



### value

    mixed Yao\MySQL\Query\Builder::value($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### first

    mixed Yao\MySQL\Query\Builder::first($columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $columns **mixed**



### get

    mixed Yao\MySQL\Query\Builder::get($columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $columns **mixed**



### paginate

    mixed Yao\MySQL\Query\Builder::paginate($perPage, $columns, $pageName, $page)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $perPage **mixed**
* $columns **mixed**
* $pageName **mixed**
* $page **mixed**



### simplePaginate

    mixed Yao\MySQL\Query\Builder::simplePaginate($perPage, $columns, $pageName, $page)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $perPage **mixed**
* $columns **mixed**
* $pageName **mixed**
* $page **mixed**



### getCountForPagination

    mixed Yao\MySQL\Query\Builder::getCountForPagination($columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $columns **mixed**



### cursor

    mixed Yao\MySQL\Query\Builder::cursor()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### chunk

    mixed Yao\MySQL\Query\Builder::chunk($count, callable $callback)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $count **mixed**
* $callback **callable**



### chunkById

    mixed Yao\MySQL\Query\Builder::chunkById($count, callable $callback, $column, $alias)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $count **mixed**
* $callback **callable**
* $column **mixed**
* $alias **mixed**



### each

    mixed Yao\MySQL\Query\Builder::each(callable $callback, $count)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $callback **callable**
* $count **mixed**



### pluck

    mixed Yao\MySQL\Query\Builder::pluck($column, $key)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $key **mixed**



### implode

    mixed Yao\MySQL\Query\Builder::implode($column, $glue)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $glue **mixed**



### exists

    mixed Yao\MySQL\Query\Builder::exists()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### count

    mixed Yao\MySQL\Query\Builder::count($columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $columns **mixed**



### min

    mixed Yao\MySQL\Query\Builder::min($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### max

    mixed Yao\MySQL\Query\Builder::max($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### sum

    mixed Yao\MySQL\Query\Builder::sum($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### avg

    mixed Yao\MySQL\Query\Builder::avg($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### average

    mixed Yao\MySQL\Query\Builder::average($column)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**



### aggregate

    mixed Yao\MySQL\Query\Builder::aggregate($function, $columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $function **mixed**
* $columns **mixed**



### numericAggregate

    mixed Yao\MySQL\Query\Builder::numericAggregate($function, $columns)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $function **mixed**
* $columns **mixed**



### insert

    mixed Yao\MySQL\Query\Builder::insert(array $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $values **array**



### insertGetId

    mixed Yao\MySQL\Query\Builder::insertGetId(array $values, $sequence)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $values **array**
* $sequence **mixed**



### update

    mixed Yao\MySQL\Query\Builder::update(array $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $values **array**



### updateOrInsert

    mixed Yao\MySQL\Query\Builder::updateOrInsert(array $attributes, array $values)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $attributes **array**
* $values **array**



### increment

    mixed Yao\MySQL\Query\Builder::increment($column, $amount, array $extra)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $amount **mixed**
* $extra **array**



### decrement

    mixed Yao\MySQL\Query\Builder::decrement($column, $amount, array $extra)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $column **mixed**
* $amount **mixed**
* $extra **array**



### delete

    mixed Yao\MySQL\Query\Builder::delete($id)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $id **mixed**



### truncate

    mixed Yao\MySQL\Query\Builder::truncate()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### mergeWheres

    mixed Yao\MySQL\Query\Builder::mergeWheres($wheres, $bindings)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $wheres **mixed**
* $bindings **mixed**



### raw

    mixed Yao\MySQL\Query\Builder::raw($value)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**



### getBindings

    mixed Yao\MySQL\Query\Builder::getBindings()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### getRawBindings

    mixed Yao\MySQL\Query\Builder::getRawBindings()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




### setBindings

    mixed Yao\MySQL\Query\Builder::setBindings(array $bindings, $type)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $bindings **array**
* $type **mixed**



### addBinding

    mixed Yao\MySQL\Query\Builder::addBinding($value, $type)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $value **mixed**
* $type **mixed**



### mergeBindings

    mixed Yao\MySQL\Query\Builder::mergeBindings(self $query)





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)


#### Arguments
* $query **self**



### toArray

    mixed Yao\MySQL\Query\Builder::toArray()





* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)




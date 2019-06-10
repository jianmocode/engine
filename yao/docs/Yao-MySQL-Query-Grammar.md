Yao\MySQL\Query\Grammar
===============

Grammar 查询语法

(在 Illuminate\Database\Query\Grammars\Builder 基础上优化)

see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Grammar.php
see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Grammars/Grammar.php
see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Grammars/MySqlGrammar.php


* Class name: Grammar
* Namespace: Yao\MySQL\Query





Properties
----------


### $tablePrefix

    protected string $tablePrefix = ''

The grammar table prefix.



* Visibility: **protected**


### $operators

    protected array $operators = array()

The grammar specific operators.



* Visibility: **protected**


### $selectComponents

    protected array $selectComponents = array('aggregate', 'columns', 'from', 'joins', 'wheres', 'groups', 'havings', 'orders', 'limit', 'offset', 'lock')

The components that make up a select clause.



* Visibility: **protected**


Methods
-------


### wrapArray

    array Yao\MySQL\Query\Grammar::wrapArray(array $values)

Wrap an array of values.



* Visibility: **public**


#### Arguments
* $values **array**



### wrapTable

    string Yao\MySQL\Query\Grammar::wrapTable(\Yao\MySQL\Query\Expression|string $table)

Wrap a table in keyword identifiers.



* Visibility: **public**


#### Arguments
* $table **[Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)|string**



### wrap

    string Yao\MySQL\Query\Grammar::wrap(\Yao\MySQL\Query\Expression|string $value, boolean $prefixAlias)

Wrap a value in keyword identifiers.



* Visibility: **public**


#### Arguments
* $value **[Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)|string**
* $prefixAlias **boolean**



### wrapValue

    string Yao\MySQL\Query\Grammar::wrapValue(string $value)

Wrap a single string in keyword identifiers.



* Visibility: **protected**


#### Arguments
* $value **string**



### wrapJsonSelector

    string Yao\MySQL\Query\Grammar::wrapJsonSelector(string $value)

Wrap the given JSON selector.



* Visibility: **protected**


#### Arguments
* $value **string**



### isJsonSelector

    boolean Yao\MySQL\Query\Grammar::isJsonSelector(string $value)

Determine if the given string is a JSON selector.



* Visibility: **protected**


#### Arguments
* $value **string**



### columnize

    string Yao\MySQL\Query\Grammar::columnize(array $columns)

Convert an array of column names into a delimited string.



* Visibility: **public**


#### Arguments
* $columns **array**



### parameterize

    string Yao\MySQL\Query\Grammar::parameterize(array $values)

Create query parameter place-holders for an array.



* Visibility: **public**


#### Arguments
* $values **array**



### parameter

    string Yao\MySQL\Query\Grammar::parameter(mixed $value)

Get the appropriate query parameter place-holder for a value.



* Visibility: **public**


#### Arguments
* $value **mixed**



### getValue

    string Yao\MySQL\Query\Grammar::getValue(\Yao\MySQL\Query\Expression $expression)

Get the value of a raw expression.



* Visibility: **public**


#### Arguments
* $expression **[Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)**



### isExpression

    boolean Yao\MySQL\Query\Grammar::isExpression(mixed $value)

Determine if the given value is a raw expression.



* Visibility: **public**


#### Arguments
* $value **mixed**



### getDateFormat

    string Yao\MySQL\Query\Grammar::getDateFormat()

Get the format for database stored dates.



* Visibility: **public**




### getTablePrefix

    string Yao\MySQL\Query\Grammar::getTablePrefix()

Get the grammar's table prefix.



* Visibility: **public**




### setTablePrefix

    \Yao\MySQL\Query\Grammar Yao\MySQL\Query\Grammar::setTablePrefix(string $prefix)

Set the grammar's table prefix.



* Visibility: **public**


#### Arguments
* $prefix **string**



### compileSelect

    string Yao\MySQL\Query\Grammar::compileSelect(\Yao\MySQL\Query\Builder $query)

Compile a select query into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileComponents

    array Yao\MySQL\Query\Grammar::compileComponents(\Yao\MySQL\Query\Builder $query)

Compile the components necessary for a select clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileAggregate

    string Yao\MySQL\Query\Grammar::compileAggregate(\Yao\MySQL\Query\Builder $query, array $aggregate)

Compile an aggregated select clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $aggregate **array**



### compileColumns

    string|null Yao\MySQL\Query\Grammar::compileColumns(\Yao\MySQL\Query\Builder $query, array $columns)

Compile the "select *" portion of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $columns **array**



### compileFrom

    string Yao\MySQL\Query\Grammar::compileFrom(\Yao\MySQL\Query\Builder $query, string $table)

Compile the "from" portion of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $table **string**



### compileJoins

    string Yao\MySQL\Query\Grammar::compileJoins(\Yao\MySQL\Query\Builder $query, array $joins)

Compile the "join" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $joins **array**



### compileWheres

    string Yao\MySQL\Query\Grammar::compileWheres(\Yao\MySQL\Query\Builder $query)

Compile the "where" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### whereNested

    string Yao\MySQL\Query\Grammar::whereNested(\Yao\MySQL\Query\Builder $query, array $where)

Compile a nested where clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereSub

    string Yao\MySQL\Query\Grammar::whereSub(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where condition with a sub-select.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereBasic

    string Yao\MySQL\Query\Grammar::whereBasic(\Yao\MySQL\Query\Builder $query, array $where)

Compile a basic where clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereColumn

    string Yao\MySQL\Query\Grammar::whereColumn(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where clause comparing two columns.

.

* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereBetween

    string Yao\MySQL\Query\Grammar::whereBetween(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "between" where clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereExists

    string Yao\MySQL\Query\Grammar::whereExists(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where exists clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereNotExists

    string Yao\MySQL\Query\Grammar::whereNotExists(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where exists clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereIn

    string Yao\MySQL\Query\Grammar::whereIn(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where in" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereNotIn

    string Yao\MySQL\Query\Grammar::whereNotIn(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where not in" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereInSub

    string Yao\MySQL\Query\Grammar::whereInSub(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where in sub-select clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereNotInSub

    string Yao\MySQL\Query\Grammar::whereNotInSub(\Yao\MySQL\Query\Builder $query, array $where)

Compile a where not in sub-select clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereNull

    string Yao\MySQL\Query\Grammar::whereNull(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where null" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereNotNull

    string Yao\MySQL\Query\Grammar::whereNotNull(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where not null" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereDate

    string Yao\MySQL\Query\Grammar::whereDate(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where date" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereTime

    string Yao\MySQL\Query\Grammar::whereTime(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where time" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereDay

    string Yao\MySQL\Query\Grammar::whereDay(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where day" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereMonth

    string Yao\MySQL\Query\Grammar::whereMonth(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where month" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereYear

    string Yao\MySQL\Query\Grammar::whereYear(\Yao\MySQL\Query\Builder $query, array $where)

Compile a "where year" clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### dateBasedWhere

    string Yao\MySQL\Query\Grammar::dateBasedWhere(string $type, \Yao\MySQL\Query\Builder $query, array $where)

Compile a date based where clause.



* Visibility: **protected**


#### Arguments
* $type **string**
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### whereRaw

    string Yao\MySQL\Query\Grammar::whereRaw(\Yao\MySQL\Query\Builder $query, array $where)

Compile a raw where clause.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $where **array**



### compileGroups

    string Yao\MySQL\Query\Grammar::compileGroups(\Yao\MySQL\Query\Builder $query, array $groups)

Compile the "group by" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $groups **array**



### compileHavings

    string Yao\MySQL\Query\Grammar::compileHavings(\Yao\MySQL\Query\Builder $query, array $havings)

Compile the "having" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $havings **array**



### compileHaving

    string Yao\MySQL\Query\Grammar::compileHaving(array $having)

Compile a single having clause.



* Visibility: **protected**


#### Arguments
* $having **array**



### compileBasicHaving

    string Yao\MySQL\Query\Grammar::compileBasicHaving(array $having)

Compile a basic having clause.



* Visibility: **protected**


#### Arguments
* $having **array**



### compileOrders

    string Yao\MySQL\Query\Grammar::compileOrders(\Yao\MySQL\Query\Builder $query, array $orders)

Compile the "order by" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $orders **array**



### compileRandom

    string Yao\MySQL\Query\Grammar::compileRandom(string $seed)

Compile the random statement into SQL.



* Visibility: **public**


#### Arguments
* $seed **string**



### compileLimit

    string Yao\MySQL\Query\Grammar::compileLimit(\Yao\MySQL\Query\Builder $query, integer $limit)

Compile the "limit" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $limit **integer**



### compileOffset

    string Yao\MySQL\Query\Grammar::compileOffset(\Yao\MySQL\Query\Builder $query, integer $offset)

Compile the "offset" portions of the query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $offset **integer**



### compileUnions

    string Yao\MySQL\Query\Grammar::compileUnions(\Yao\MySQL\Query\Builder $query)

Compile the "union" queries attached to the main query.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileUnion

    string Yao\MySQL\Query\Grammar::compileUnion(array $union)

Compile a single union statement.



* Visibility: **protected**


#### Arguments
* $union **array**



### compileExists

    string Yao\MySQL\Query\Grammar::compileExists(\Yao\MySQL\Query\Builder $query)

Compile an exists statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileInsert

    string Yao\MySQL\Query\Grammar::compileInsert(\Yao\MySQL\Query\Builder $query, array $values)

Compile an insert statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $values **array**



### compileInsertGetId

    string Yao\MySQL\Query\Grammar::compileInsertGetId(\Yao\MySQL\Query\Builder $query, array $values, string $sequence)

Compile an insert and get ID statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $values **array**
* $sequence **string**



### compileUpdate

    string Yao\MySQL\Query\Grammar::compileUpdate(\Yao\MySQL\Query\Builder $query, array $values)

Compile an update statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $values **array**



### compileJsonUpdateColumn

    string Yao\MySQL\Query\Grammar::compileJsonUpdateColumn(string $key, \Illuminate\Database\Query\JsonExpression $value)

Prepares a JSON column being updated using the JSON_SET function.



* Visibility: **protected**


#### Arguments
* $key **string**
* $value **Illuminate\Database\Query\JsonExpression**



### prepareBindingsForUpdate

    array Yao\MySQL\Query\Grammar::prepareBindingsForUpdate(array $bindings, array $values)

Prepare the bindings for an update statement.



* Visibility: **public**


#### Arguments
* $bindings **array**
* $values **array**



### compileDelete

    string Yao\MySQL\Query\Grammar::compileDelete(\Yao\MySQL\Query\Builder $query)

Compile a delete statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileTruncate

    array Yao\MySQL\Query\Grammar::compileTruncate(\Yao\MySQL\Query\Builder $query)

Compile a truncate table statement into SQL.



* Visibility: **public**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**



### compileLock

    string Yao\MySQL\Query\Grammar::compileLock(\Yao\MySQL\Query\Builder $query, boolean|string $value)

Compile the lock into SQL.



* Visibility: **protected**


#### Arguments
* $query **[Yao\MySQL\Query\Builder](Yao-MySQL-Query-Builder.md)**
* $value **boolean|string**



### supportsSavepoints

    boolean Yao\MySQL\Query\Grammar::supportsSavepoints()

Determine if the grammar supports savepoints.



* Visibility: **public**




### compileSavepoint

    string Yao\MySQL\Query\Grammar::compileSavepoint(string $name)

Compile the SQL statement to define a savepoint.



* Visibility: **public**


#### Arguments
* $name **string**



### compileSavepointRollBack

    string Yao\MySQL\Query\Grammar::compileSavepointRollBack(string $name)

Compile the SQL statement to execute a savepoint rollback.



* Visibility: **public**


#### Arguments
* $name **string**



### concatenate

    string Yao\MySQL\Query\Grammar::concatenate(array $segments)

Concatenate an array of segments, removing empties.



* Visibility: **protected**


#### Arguments
* $segments **array**



### removeLeadingBoolean

    string Yao\MySQL\Query\Grammar::removeLeadingBoolean(string $value)

Remove the leading boolean from a statement.



* Visibility: **protected**


#### Arguments
* $value **string**



### getOperators

    array Yao\MySQL\Query\Grammar::getOperators()

Get the grammar specific operators.



* Visibility: **public**




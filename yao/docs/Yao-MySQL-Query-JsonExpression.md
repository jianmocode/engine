Yao\MySQL\Query\JsonExpression
===============

JsonExpression

(Copy From \Illuminate\Database\Query\JsonExpression )

see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/JsonExpression.php


* Class name: JsonExpression
* Namespace: Yao\MySQL\Query
* Parent class: [Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)





Properties
----------


### $value

    protected mixed $value

查询表达式



* Visibility: **protected**


Methods
-------


### __construct

    void Yao\MySQL\Query\Expression::__construct(mixed $value)

创建查询表达式



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)


#### Arguments
* $value **mixed**



### getJsonBindingParameter

    string Yao\MySQL\Query\JsonExpression::getJsonBindingParameter(mixed $value)

Translate the given value into the appropriate JSON binding parameter.



* Visibility: **protected**


#### Arguments
* $value **mixed**



### getValue

    mixed Yao\MySQL\Query\Expression::getValue()

读取查询表达式



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)




### __toString

    string Yao\MySQL\Query\Expression::__toString()

读取表达式数值



* Visibility: **public**
* This method is defined by [Yao\MySQL\Query\Expression](Yao-MySQL-Query-Expression.md)




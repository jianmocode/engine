<?php
/**
 * Class Expression
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\MySQL\Query;

use Yao\Support\Str;
use Yao\MySQL\Query\Builder;
use Yao\MySQL\Query\JsonExpression;


/**
 * Grammar 查询语法
 * 
 * (在 Illuminate\Database\Query\Grammars\Builder 基础上优化)
 * 
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Grammar.php
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Grammars/Grammar.php
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Grammars/MySqlGrammar.php
 */
class Grammar {
    
    /**
     * The grammar table prefix.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [];
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];


    /**
     * Wrap an array of values.
     *
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values) {
        return array_map([$this, 'wrap'], $values);
    }


    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  \Yao\MySQL\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table) {

        if ($this->isExpression($table)) {
            return $this->getValue($table);
        }
        return $this->wrap($this->tablePrefix.$table, true);
    }


    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Yao\MySQL\Query\Expression|string  $value
     * @param  bool    $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false) {

        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }
        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on it
        // own, and then joins them both back together with the "as" connector.
        if (strpos(strtolower($value), ' as ') !== false) {
            $segments = explode(' ', $value);
            if ($prefixAlias) {
                $segments[2] = $this->tablePrefix.$segments[2];
            }
            return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[2]);
        }
        $wrapped = [];
        $segments = explode('.', $value);
        // If the value is not an aliased table expression, we'll just wrap it like
        // normal, so if there is more than one segment, we will wrap the first
        // segments as if it was a table and the rest as just regular values.
        foreach ($segments as $key => $segment) {
            if ($key == 0 && count($segments) > 1) {
                $wrapped[] = $this->wrapTable($segment);
            } else {
                $wrapped[] = $this->wrapValue($segment);
            }
        }
        return implode('.', $wrapped);
    }


    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value) {

        if ($value === '*') {
            return $value;
        }
        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }
        return '`'.str_replace('`', '``', $value).'`';
    }

    /**
     * Wrap the given JSON selector.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value) {

        $path = explode('->', $value);
        $field = $this->wrapValue(array_shift($path));
        $path = collect($path)->map(function ($part) {
            return '"'.$part.'"';
        })->implode('.');
        return sprintf('%s->\'$.%s\'', $field, $path);
    }

    /**
     * Determine if the given string is a JSON selector.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isJsonSelector($value) {
        return Str::contains($value, '->');
    }


    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns) {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }


    /**
     * Create query parameter place-holders for an array.
     *
     * @param  array   $values
     * @return string
     */
    public function parameterize(array $values) {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }


    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed   $value
     * @return string
     */
    public function parameter($value) {
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * Get the value of a raw expression.
     *
     * @param  \Yao\MySQL\Query\Expression  $expression
     * @return string
     */
    public function getValue($expression) {
        return $expression->getValue();
    }


    /**
     * Determine if the given value is a raw expression.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value) {
        return $value instanceof Expression;
    }


    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the grammar's table prefix.
     *
     * @return string
     */
    public function getTablePrefix() {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix) {
        $this->tablePrefix = $prefix;
        return $this;
    }

     /**
     * Compile a select query into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query) {
        $original = $query->columns;
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }
        $sql = trim($this->concatenate($this->compileComponents($query)));
        $query->columns = $original;

        // Merge MySQL Grammar
        if ($query->unions) {
            $sql = '('.$sql.') '.$this->compileUnions($query);
        }

        return $sql;
    }

    


    /**
     * Compile the components necessary for a select clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return array
     */
    protected function compileComponents(Builder $query) {
        $sql = [];
        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            if (! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);
                $sql[$component] = $this->$method($query, $query->$component);
            }
        }
        return $sql;
    }


    /**
     * Compile an aggregated select clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate) {
        $column = $this->columnize($aggregate['columns']);
        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }
        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }


    /**
     * Compile the "select *" portion of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns) {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (! is_null($query->aggregate)) {
            return;
        }
        $select = $query->distinct ? 'select distinct ' : 'select ';
        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(Builder $query, $table) {
        return 'from '.$this->wrapTable($table);
    }


    /**
     * Compile the "join" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(Builder $query, $joins) {
        $sql = [];
        foreach ($joins as $join) {
            $conditions = $this->compileWheres($join);
            $table = $this->wrapTable($join->table);
            $sql[] = trim("{$join->type} join {$table} {$conditions}");
        }
        return implode(' ', $sql);
    }


    /**
     * Compile the "where" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return string
     */
    protected function compileWheres(Builder $query) {

        $sql = [];
        if (is_null($query->wheres)) {
            return '';
        }
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        foreach ($query->wheres as $where) {
            $method = "where{$where['type']}";
            $sql[] = $where['boolean'].' '.$this->$method($query, $where);
        }
        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql) > 0) {
            $sql = implode(' ', $sql);
            $conjunction = $query instanceof JoinClause ? 'on' : 'where';
            return $conjunction.' '.$this->removeLeadingBoolean($sql);
        }
        return '';
    }


    /**
     * Compile a nested where clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(Builder $query, $where) {
        $nested = $where['query'];
        $offset = $query instanceof JoinClause ? 3 : 6;
        return '('.substr($this->compileWheres($nested), $offset).')';
    }

    /**
     * Compile a where condition with a sub-select.
     *
     * @param  \Yao\MySQL\Query\Builder $query
     * @param  array   $where
     * @return string
     */
    protected function whereSub(Builder $query, $where) {
        $select = $this->compileSelect($where['query']);
        return $this->wrap($where['column']).' '.$where['operator']." ($select)";
    }


    /**
     * Compile a basic where clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where) {
        $value = $this->parameter($where['value']);
        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a where clause comparing two columns..
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereColumn(Builder $query, $where) {
        $second = $this->wrap($where['second']);
        return $this->wrap($where['first']).' '.$where['operator'].' '.$second;
    }


    /**
     * Compile a "between" where clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(Builder $query, $where){
        $between = $where['not'] ? 'not between' : 'between';
        return $this->wrap($where['column']).' '.$between.' ? and ?';
    }


    /**
     * Compile a where exists clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereExists(Builder $query, $where) {
        return 'exists ('.$this->compileSelect($where['query']).')';
    }


    /**
     * Compile a where exists clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotExists(Builder $query, $where) {
        return 'not exists ('.$this->compileSelect($where['query']).')';
    }


    /**
     * Compile a "where in" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(Builder $query, $where) {
        if (empty($where['values'])) {
            return '0 = 1';
        }
        $values = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' in ('.$values.')';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(Builder $query, $where) {
        if (empty($where['values'])) {
            return '1 = 1';
        }
        $values = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' not in ('.$values.')';
    }


    /**
     * Compile a where in sub-select clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInSub(Builder $query, $where) {
        $select = $this->compileSelect($where['query']);
        return $this->wrap($where['column']).' in ('.$select.')';
    }

    /**
     * Compile a where not in sub-select clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInSub(Builder $query, $where) {
        $select = $this->compileSelect($where['query']);
        return $this->wrap($where['column']).' not in ('.$select.')';
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where){
        return $this->wrap($where['column']).' is null';
    }


    /**
     * Compile a "where not null" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where) {
        return $this->wrap($where['column']).' is not null';
    }

    /**
     * Compile a "where date" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where) {
        return $this->dateBasedWhere('date', $query, $where);
    }


    /**
     * Compile a "where time" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(Builder $query, $where) {
        return $this->dateBasedWhere('time', $query, $where);
    }


    /**
     * Compile a "where day" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(Builder $query, $where){
        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(Builder $query, $where){
        return $this->dateBasedWhere('month', $query, $where);
    }


    /**
     * Compile a "where year" clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(Builder $query, $where){
        return $this->dateBasedWhere('year', $query, $where);
    }


    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where) {
        $value = $this->parameter($where['value']);
        return $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }


    /**
     * Compile a raw where clause.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRaw(Builder $query, $where) {
        return $where['sql'];
    }

    /**
     * Compile the "group by" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(Builder $query, $groups){
        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(Builder $query, $havings) {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));
        return 'having '.$this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
     *
     * @param  array   $having
     * @return string
     */
    protected function compileHaving(array $having) {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        if ($having['type'] === 'raw') {
            return $having['boolean'].' '.$having['sql'];
        }
        return $this->compileBasicHaving($having);
    }


    /**
     * Compile a basic having clause.
     *
     * @param  array   $having
     * @return string
     */
    protected function compileBasicHaving($having) {
        $column = $this->wrap($having['column']);
        $parameter = $this->parameter($having['value']);
        return $having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter;
    }


    /**
     * Compile the "order by" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders) {
        if (empty($orders)) {
            return '';
        }
        return 'order by '.implode(', ', array_map(function ($order) {
            if (isset($order['sql'])) {
                return $order['sql'];
            }
            return $this->wrap($order['column']).' '.$order['direction'];
        }, $orders));
    }


    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed){
        return 'RAND('.$seed.')';
    }


    /**
     * Compile the "limit" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit){
        return 'limit '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset){
        return 'offset '.(int) $offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return string
     */
    protected function compileUnions(Builder $query) {
        $sql = '';
        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }
        if (! empty($query->unionOrders)) {
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }
        if (isset($query->unionLimit)) {
            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
        }
        if (isset($query->unionOffset)) {
            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
        }
        return ltrim($sql);
    }


    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union) {
        $joiner = $union['all'] ? ' union all ' : ' union ';
        return $joiner.'('.$union['query']->toSql().')';
    }


    /**
     * Compile an exists statement into SQL.
     *
     * @param \Yao\MySQL\Query\Builder $query
     * @return string
     */
    public function compileExists(Builder $query) {
        $select = $this->compileSelect($query);
        return "select exists($select) as {$this->wrap('exists')}";
    }


    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values) {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);
        if (! is_array(reset($values))) {
            $values = [$values];
        }
        $columns = $this->columnize(array_keys(reset($values)));
        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = [];
        foreach ($values as $record) {
            $parameters[] = '('.$this->parameterize($record).')';
        }
        $parameters = implode(', ', $parameters);
        return "insert into $table ($columns) values $parameters";
    }


    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence) {
        return $this->compileInsert($query, $values);
    }


    /**
     * Compile an update statement into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values) {

        $table = $this->wrapTable($query->from);
        $columns = [];
        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        foreach ($values as $key => $value) {
            if ($this->isJsonSelector($key)) {
                $columns[] = $this->compileJsonUpdateColumn(
                    $key, new JsonExpression($value)
                );
            } else {
                $columns[] = $this->wrap($key).' = '.$this->parameter($value);
            }
        }
        $columns = implode(', ', $columns);
        // If the query has any "join" clauses, we will setup the joins on the builder
        // and compile them so we can attach them to this update, as update queries
        // can get join statements to attach to other tables when they're needed.
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
        } else {
            $joins = '';
        }
        // Of course, update queries may also be constrained by where clauses so we'll
        // need to compile the where clauses and attach it to the query so only the
        // intended records are updated by the SQL statements we generate to run.
        $where = $this->compileWheres($query);
        $sql = rtrim("update {$table}{$joins} set $columns $where");
        if (! empty($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }
        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }
        return rtrim($sql);
    }

    /**
     * Prepares a JSON column being updated using the JSON_SET function.
     *
     * @param  string  $key
     * @param  \Illuminate\Database\Query\JsonExpression  $value
     * @return string
     */
    protected function compileJsonUpdateColumn($key, JsonExpression $value) {
        $path = explode('->', $key);
        $field = $this->wrapValue(array_shift($path));
        $accessor = '"$.'.implode('.', $path).'"';
        return "{$field} = json_set({$field}, {$accessor}, {$value->getValue()})";
    }





    /**
     * Prepare the bindings for an update statement.
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values) {

        foreach ($values as $column => $value) {
            if ($this->isJsonSelector($column) &&
                in_array(gettype($value), ['boolean', 'integer', 'double'])) {
                unset($values[$column]);
            }
        }
        return parent::prepareBindingsForUpdate($bindings, $values);
    }


    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query) {
        $table = $this->wrapTable($query->from);
        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
            $sql = trim("delete $table from {$table}{$joins} $where");
        } else {
            $sql = trim("delete from $table $where");
            if (! empty($query->orders)) {
                $sql .= ' '.$this->compileOrders($query, $query->orders);
            }
            if (isset($query->limit)) {
                $sql .= ' '.$this->compileLimit($query, $query->limit);
            }
        }
        return $sql;
    }



    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query) {
        return ['truncate '.$this->wrapTable($query->from) => []];
    }


    /**
     * Compile the lock into SQL.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value) {
        if (is_string($value)) {
            return $value;
        }
        return $value ? 'for update' : 'lock in share mode';
    }


    /**
     * Determine if the grammar supports savepoints.
     *
     * @return bool
     */
    public function supportsSavepoints() {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name) {

        return 'SAVEPOINT '.$name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name) {
        return 'ROLLBACK TO SAVEPOINT '.$name;
    }


    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array   $segments
     * @return string
     */
    protected function concatenate($segments) {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeLeadingBoolean($value) {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the grammar specific operators.
     *
     * @return array
     */
    public function getOperators() {
        return $this->operators;
    }

}
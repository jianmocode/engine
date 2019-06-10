<?php
/**
 * Class Builder
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\MySQL\Query;
use \Yao\Excp;
use \Yao\MySQL\Query\Grammar;
use \Yao\MySQL\Query\Processor;


/**
 * MySQL 查询构造器
 * 
 * (在 Illuminate\Database\Query\Builder 基础上修改)
 * 
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Builder.php
 * 
 */
class Builder {

    /**
     * 当前查询符
     *
     * @var \Yao\MySQL\Driver
     */
    protected $connection;

    /**
     * The database query grammar instance.
     *
     * @var \Yao\MySQL\Query\Grammar
     */
    protected $grammar;

    /**
     * The database query post processor instance.
     *
     * @var \Yao\MySQL\Query\Processor
     */
    protected $processor;

    /**
     * 当前查询绑定数据.
     *
     * @var array
     */
    protected $bindings = [
        'select' => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
        'union'  => [],
    ];

    /**
     * 有效操作符
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * 返回的字段清单
     *
     * @var array
     */
    public $columns;

    /**
     * distinct 标记
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * 查询的数表名
     *
     * @var string
     */
    public $from;


    /**
     * Join 查询条件
     *
     * @var array
     */
    public $joins;


    /**
     * Where 查询条件
     *
     * @var array
     */
    public $wheres;

    /**
     * Group 查询条件
     *
     * @var array
     */
    public $groups;

    /**
     * Having 查询条件
     *
     * @var array
     */
    public $havings;

    /**
     * Order 排序条件
     *
     * @var array
     */
    public $orders;

    /**
     * MySQL Limit 
     *
     * @var int
     */
    public $limit;

    /**
     * MySQL Offset
     *
     * @var int
     */
    public $offset;

    /**
     * Union 查询条件
     *
     * @var array
     */
    public $unions;

    /**
     * Union 记录最大值
     *
     * @var int
     */
    public $unionLimit;

    /**
     * Union 记录偏移量
     *
     * @var int
     */
    public $unionOffset;


    /**
     * Union 查询排序
     *
     * @var array
     */
    public $unionOrders;


    /**
     * Lock 标记
     *
     * @var string|bool
     */
    public $lock;

    /**
     * 当前查询用到的字段备份
     *
     * @var array
     */
    protected $backups = [];


    /**
     * 当前查询绑定字段备份
     *
     * @var array
     */
    protected $bindingBackups = [];


    /**
     * 构造查询器
     */
    public function __construct( $connection, Grammar $grammar = null, Processor $processor = null) {

        $this->connection = $connection;
        $this->grammar = $grammar ?: new Grammar;
        $this->processor = $processor ?: new Processor;
    }


    /**
     * 设定选择字段
     *
     * @param  array|mixed  $columns 字段清单
     * @return $this
     */
    public function select($columns = ['*']) {

        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }


    /**
     * 添加表达式查询字段
     *
     * @param  string  $expression 查询表达式
     * @param  array   $bindings 绑定数据集合
     * @return \Yao\MySQL\Builder
     */
    public function selectRaw($expression, array $bindings = []) {

        $this->addSelect(new Expression($expression));
        if ($bindings) {
            $this->addBinding($bindings, 'select');
        }
        return $this;
    }

    /**
     * 添加子查询表达式
     *
     * @param  \Yao\MySQL\Builder|string $query
     * @param  string $as
     * @return \Yao\MySQL\Builder
     *
     * @throws \Yao\Excp
     */
    public function selectSub($query, $as) {

        if ($query instanceof Closure) {
            $callback = $query;
            $callback($query = $this->newQuery());
        }
        if ($query instanceof self) {
            $bindings = $query->getBindings();
            $query = $query->toSql();
        } elseif (is_string($query)) {
            $bindings = [];
        } else {
            throw new Excp("传入参数错误", 402);
        }
        return $this->selectRaw('('.$query.') as '.$this->grammar->wrap($as), $bindings);
    }

    /**
     * 添加查询字段
     *
     * @param  array|mixed $column
     * @return $this
     */
    public function addSelect($column) {
        $column = is_array($column) ? $column : func_get_args();
        $this->columns = array_merge((array) $this->columns, $column);
        return $this;
    }


    /**
     * Force the query to only return distinct results.
     *
     * @return $this
     */
    public function distinct() {
        $this->distinct = true;
        return $this;
    }


    public function from($table){
    }

    public function join( $table, $f1, $op = NULL, $f2 = NULL,$type = 'inner', $where = false ){
    }

    public function joinWhere($table, $one, $operator, $two, $type = 'inner'){

    }

    public function leftjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
    }

    public function leftJoinWhere($table, $one, $operator, $two){
    }

    public function rightjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
    }

    public function rightJoinWhere($table, $one, $operator, $two){
    }

    public function crossJoin($table, $first = null, $operator = null, $second = null){
    }

    public function when($value, $callback, $default = null){
    }

    public function where( $column, $operator = null, $value = null, $boolean = 'and') {
    }

    public function orWhere($column, $operator = null, $value = null){
    }

    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and'){
    }

    public function orWhereColumn($first, $operator = null, $second = null){
    }

    public function whereRaw($expression, array $bindings = []) {
    }

    public function orWhereRaw($sql, array $bindings = []){
    }

    public function whereBetween($column, array $values, $boolean = 'and', $not = false){
    }

    public function orWhereBetween($column, array $values){
    }

    public function whereNotBetween($column, array $values, $boolean = 'and'){
    }

    public function orWhereNotBetween($column, array $values){
    }

    public function whereNested(Closure $callback, $boolean = 'and'){
    }

    public function forNestedWhere(){
    }

    public function addNestedWhereQuery($query, $boolean = 'and'){
    }

    public function whereExists(Closure $callback, $boolean = 'and', $not = false){
    }

    public function orWhereExists(Closure $callback, $not = false){
    }

    public function whereNotExists(Closure $callback, $boolean = 'and'){
    }

    public function orWhereNotExists(Closure $callback){
    }

    public function addWhereExistsQuery(self $query, $boolean = 'and', $not = false){
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false){
    }

    public function orWhereIn($column, $values){
    }

    public function whereNotIn($column, $values, $boolean = 'and'){
    }

    public function orWhereNotIn($column, $values){
    }

    public function whereNull($column, $boolean = 'and', $not = false){
    }

    public function orWhereNull($column){
    }

    public function whereNotNull($column, $boolean = 'and'){
    }

    public function orWhereNotNull($column){
    }

    public function whereDate($column, $operator, $value = null, $boolean = 'and'){
    }

    public function orWhereDate($column, $operator, $value){
    }

    public function whereTime($column, $operator, $value, $boolean = 'and'){
    }

    public function orWhereTime($column, $operator, $value){
    }

    public function whereDay($column, $operator, $value = null, $boolean = 'and'){
    }

    public function whereMonth($column, $operator, $value = null, $boolean = 'and'){
    }

    public function whereYear($column, $operator, $value = null, $boolean = 'and'){
    }

    public function dynamicWhere($method, $parameters){
    }

    public function whereMatch($column, $value, $boolean = 'and') {
    }

    public function orWhereMatch($column, $value){
    }

    public function groupBy(...$groups){
    }

    public function having($column, $operator = null, $value = null, $boolean = 'and'){
    }

    public function orHaving($column, $operator = null, $value = null){
    }

    public function havingRaw($sql, array $bindings = [], $boolean = 'and'){
    }

    public function orHavingRaw($sql, array $bindings = []){
    }

    public function orderBy($column, $direction = 'asc'){
    }

    public function latest($column = 'created_at'){
    }

    public function oldest($column = 'created_at'){
    }

    public function inRandomOrder($seed = ''){
    }

    public function orderByRaw($sql, $bindings = []){
    }

    public function offset($value){
    }

    public function skip($value){
    }

    public function limit($value){
    }

    public function take($value){
    }

    public function forPage($page, $perPage = 15){
    }

    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id'){
    }

    public function union($query, $all = false){
    }

    public function unionAll($query){
    }

    public function lock($value = true){
    }

    public function lockForUpdate(){
    }

    public function sharedLock(){
    }

    public function toSql(){
    }

    public function find($id, $columns = ['*']){
    }

    public function value($column){
    }

    public function first($columns = ['*']){
    }

    public function get($columns = ['*']){
    }


    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null) {
    }

    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null){
    }

    public function getCountForPagination($columns = ['*']){
    }

    public function cursor(){
    }

    public function chunk($count, callable $callback){
    }

    public function chunkById($count, callable $callback, $column = 'id', $alias = null){
    }

    public function each(callable $callback, $count = 1000){
    }

    public function pluck($column, $key = null){
    }

    public function implode($column, $glue = ''){
    }

    public function exists(){
    }

    public function count($columns = '*'){
    }

    public function min($column){
    }

    public function max($column){
    }

    public function sum($column){
    }

    public function avg($column){
    }

    public function average($column){
    }

    public function aggregate($function, $columns = ['*']){
    }

    public function numericAggregate($function, $columns = ['*']){
    }

    public function insert(array $values){
    }

    public function insertGetId(array $values, $sequence = null){
    }

    public function update(array $values){
    }

    public function updateOrInsert(array $attributes, array $values = []){
    }

    public function increment($column, $amount = 1, array $extra = []){
    }

    public function decrement($column, $amount = 1, array $extra = []){
    }

    public function delete($id = null){
    }

    public function truncate(){
    }

    public function newQuery(){
    }

    public function mergeWheres($wheres, $bindings){
    }

    public function raw($value){
    }

    public function getBindings(){
    }

    public function getRawBindings(){
    }

    public function setBindings(array $bindings, $type = 'where'){
    }

    public function addBinding($value, $type = 'where'){
    }

    public function mergeBindings(self $query){
    }

    public function toArray() {
    }
    
}
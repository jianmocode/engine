<?php
/**
 * Class JoinClause
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */


namespace Yao\MySQL\Query;
use Closure;


/**
 * JoinClause
 * 
 * (Copy From \Yao\MySQL\Query\JoinClause )
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Database/Query/JoinClause.php
 */
class JoinClause extends Builder
{
    /**
     * The type of join being performed.
     *
     * @var string
     */
    public $type;
    /**
     * The table the join clause is joining to.
     *
     * @var string
     */
    public $table;
    /**
     * The connection of the parent query builder.
     *
     * @var \Yao\MySQL\Connection
     */
    protected $parentConnection;
    /**
     * The grammar of the parent query builder.
     *
     * @var \Yao\MySQL\Query\Grammar
     */
    protected $parentGrammar;
    /**
     * The processor of the parent query builder.
     *
     * @var \Yao\MySQL\Query\Processor
     */
    protected $parentProcessor;
    
    /**
     * The class name of the parent query builder.
     *
     * @var string
     */
    protected $parentClass;
    /**
     * Create a new join clause instance.
     *
     * @param  \Yao\MySQL\Query\Builder $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentClass = get_class($parentQuery);
        $this->parentGrammar = $parentQuery->getGrammar();
        $this->parentProcessor = $parentQuery->getProcessor();
        $this->parentConnection = $parentQuery->getConnection();
        parent::__construct(
            $this->parentConnection, $this->parentGrammar, $this->parentProcessor
        );
    }
    
    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \Yao\Excp
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }
        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Yao\MySQL\Query\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }
    /**
     * Get a new instance of the join clause builder.
     *
     * @return \Yao\MySQL\Query\JoinClause
     */
    public function newQuery()
    {
        return new static($this->newParentQuery(), $this->type, $this->table);
    }
    /**
     * Create a new query instance for sub-query.
     *
     * @return \Yao\MySQL\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }
    /**
     * Create a new parent query instance.
     *
     * @return \Yao\MySQL\Query\Builder
     */
    protected function newParentQuery()
    {
        $class = $this->parentClass;
        return new $class($this->parentConnection, $this->parentGrammar, $this->parentProcessor);
    }
}
<?php
namespace Xpmse\DataDriver;
use \Illuminate\Database\Query\Builder as QueryBuilder;
use \Illuminate\Database\ConnectionInterface;
use \Illuminate\Database\Query\Grammars\Grammar;
use \Illuminate\Database\Query\Processors\Processor;
use \Illuminate\Support\Collection;
use \Illuminate\Pagination\Paginator;
use \Illuminate\Container\Container;
use \Illuminate\Pagination\LengthAwarePaginator;
use \Xpmse\Excp;

/**
 * 数据查询器 （ sqlQueryBuilder ） 
 */
class sqlQueryBuilder extends QueryBuilder {


	private $data = null;
	private $cols_map = [];
    private $tab_map = [];

    // \Illuminate\Support\Collection 
    private $collection = null;
    
    //  \Illuminate\Contracts\Pagination\LengthAwarePaginator
    private $LengthAwarePaginator = null;

	/**
	 * Create a new query builder instance.
	 *
	 * @param  \Illuminate\Database\ConnectionInterface  $connection
	 * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
	 * @param  \Illuminate\Database\Query\Processors\Processor  $processor
     * @param  \Xpmse\DataDriver\<data> $data;  数据驱动
	 * @return void
	 */
	public function __construct(ConnectionInterface $connection,
								Grammar $grammar = null,
								Processor $processor = null, 
								$data = null
							) {
		parent::__construct( $connection, $grammar, $processor) ; 
		$this->data = $data;

    }
    
	public function where($column, $operator = null, $value = null, $boolean = 'and') {
		return parent::where($column, $operator, $value, $boolean);
	}

	public function rightjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
        $this->whereNull($this->tab( $table) .'.deleted_at');
		return parent::join($table, $f1, $op, $f2, 'right');
	}

	public function leftjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
		$this->whereNull($this->tab($table) .'.deleted_at');		
		return parent::join($table, $f1, $op, $f2, 'left');
    }

    public function join( $table, $f1, $op = NULL, $f2 = NULL,$type = 'inner', $where = false ) {
		$this->whereNull($this->tab($table) .'.deleted_at');		
		return parent::join($table, $f1, $op, $f2);
    }

    /**
     * 匹配查询
     */
    public function match($column, $value, $boolean = 'and') {

        // 不带数据表名称
        if ( strpos($column, ".") === false ) {
            $from = $this->grammar->wrapTable($this->from);
            $column = "{$from}.{$column}";

        // 带数据表名称
        } else {
            $columnr = explode(".", $column);
            $from = $this->grammar->wrapTable($columnr[0]);
            $column = "{$from}.{$columnr[1]}";
        }

        $method = ($boolean == 'and') ? "whereRaw" : "orWhereRaw";
        return $this->$method("MATCH ({$column}) AGAINST (? IN NATURAL LANGUAGE MODE)", [$value]);
    }
    
    /**
     * 或关系匹配查询
     */
    public function orMatch($column, $value){
        return $this->match( $column, $value, "or");
    }

    
	/** 
	 * 重载处理字段映射关系
	 */
	public function select( $columns = [] ) {
		$args = func_get_args();
		if ( is_array($args[0]) ) {
			$args = $args[0];
		}
		return parent::select( $args );
    }
    
    /**
     * [reload]Paginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null) {

        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $total = $this->getCountForPagination($columns);
        $results = $total ? $this->forPage($page, $perPage)->_get($columns) : collect();
        $this->LengthAwarePaginator = $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return $this;
    }


     /**
     * [Reload]Create a new length-aware paginator instance.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginator($items, $total, $perPage, $currentPage, $options) {
        return Container::getInstance()->make(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }


    /**
     * [relaod]Get the count of the total records for the paginator.
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])  {
        $results = $this->runPaginationCountQuery($columns);
        // Once we have run the pagination count query, we will get the resulting count and
        // take into account what type of query it was. When there is a group by we will
        // just return the count of the entire results set since that will be correct.
        if (isset($this->groups)) {
            return count($results);
        } elseif (! isset($results[0])) {
            return 0;
        } elseif (is_object($results[0])) {
            return (int) $results[0]->aggregate;
        }
        return (int) array_change_key_case((array) $results[0])['aggregate'];
    }

    /**
     * [reload]Run a pagination count query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*']) {

        $without = $this->unions ? ['orders', 'limit', 'offset'] : ['columns', 'orders', 'limit', 'offset'];
        return $this->cloneWithout($without)
                    ->cloneWithoutBindings($this->unions ? ['order'] : ['select', 'order'])
                    ->setAggregate('count', $this->withoutSelectAliases($columns))
                    ->_get()->all();
    }


     /**
     * Remove the column aliases since they will break count queries.
     *
     * @param  array  $columns
     * @return array
     */
    protected function withoutSelectAliases(array $columns) {
        return array_map(function ($column) {
            return is_string($column) && ($aliasPosition = stripos($column, ' as ')) !== false
                    ? substr($column, 0, $aliasPosition) : $column;
        }, $columns);
    }


     /**
     * Set the aggregate property without running the query.
     *
     * @param  string  $function
     * @param  array  $columns
     * @return $this
     */
    protected function setAggregate($function, $columns) {
        $this->aggregate = compact('function', 'columns');
        if (empty($this->groups)) {
            $this->orders = null;
            $this->bindings['order'] = [];
        }
        return $this;
    }


     /**
     * Clone the query without the given properties.
     *
     * @param  array  $properties
     * @return static
     */
    public function cloneWithout(array $properties) {
        return tap(clone $this, function ($clone) use ($properties) {
            foreach ($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }


    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return mixed
     */
    protected function onceWithColumns($columns, $callback) {
        $original = $this->columns;
        if (is_null($original)) {
            $this->columns = $columns;
        }
        $result = $callback();
        $this->columns = $original;
        return $result;
    }

    /**
     * Clone the query without the given bindings.
     *
     * @param  array  $except
     * @return static
     */
    public function cloneWithoutBindings(array $except) {
        return tap(clone $this, function ($clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

	// + pgArray = paginate()->toArray()
	public function pgArray($perpage=20, $count=['_id'], $link='page', $page=1 ) {

        $pg = $this->paginate( $perpage, $count, $link, $page );
        $resp = $pg->toArray();
        $structs = $this->getQueryFieldStructs(false);
        foreach( $resp["data"] as & $row ) {
            $this->filter( $row, $structs);
        }

		// $resp['next'] = ($pg->lastPage() > $pg->currentPage()) ? ($pg->currentPage() + 1) : false ;
		// $resp['prev'] = ($pg->currentPage() > 1 ) ? ($pg->currentPage() - 1) : false ;
		// $resp['curr'] = $resp['current_page'];
		// $resp['last'] = $resp['last_page'];
		// $resp['perpage'] = $resp['per_page'];
		$end=$resp['current_page']+5;
		if($end>$resp['last_page']){
			$end=$resp['last_page'];
		}
		$resp['end']=$end;
		$frontend=$end-6;
		if($frontend<1){
			$frontend=0;
		}
		$resp['frontend']=$frontend;
		$frontstart=$resp['current_page']-5;
		if($frontstart<=0){
			$frontstart=1;
		}
		$resp['frontstart']=$frontstart;
		return $resp;
	}

	// OverLoad SelectRaw 
	public function selectRaw($expression, array $bindings = []) {
		$prefix =  $this->tablePrefix();
		$expression = preg_replace('/\{([0-9a-zA-Z\_\.]+)\}/i', $prefix . '${1}', $expression);
		return parent::selectRaw( $expression, $bindings );
	}

	/**
	 * 读取前缀
	 * @return [type] [description]
	 */
	public function tablePrefix() {
		return $this->connection->getTablePrefix();
    }
    
    /**
     * 读取表名
     */
    private function tab( $table ) {
        $tb = explode(" as ", $table);
		return ( count($tb) == 2) ? trim($tb[1]) : trim($tb[0]);
    }

    /**
     * 读取字段名称
     */
    private function field( $column ) {
        $table = explode("." , $column);
        $table = ( count($table) == 2) ? trim($table[0]) : null;
        $field = explode(" as ", $column);
        $field[0] = \str_replace("{$table}.", "", $field[0] );
        $as = ( count($field) == 2) ? trim($field[1]) : trim($field[0]);
        $field = $field[0];
        return ["table"=>$table, "field"=>$field, "as"=>$as];
    }

	// OverFlow toSql
	public function getSql( $parseBindings = true ) {
		
		$sql = $this->toSql();

		if ( $parseBindings == true ){
			$bindings = parent::getBindings();
			$needle = '?';
			foreach ($bindings as $replace){
				$pos = strpos($sql, $needle);
				if ($pos !== false) {
					$sql = substr_replace($sql, $replace, $pos, strlen($needle));
				}
			}
		}

		return $sql;
	}

    /***
     * Parent GET 
     */
    public function _get($columns = ['*']) {

        $columns = is_array($columns) ? $columns : [$columns];
        return collect($this->onceWithColumns($columns, function () {
            return $this->processor->processSelect($this, $this->runSelect());
        }));
    }

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return $this
	 */
	public function get($columns = null ) {

		try {

            // Select
            if ( !empty($columns) ) {
                if (is_string($columns) ) {
                    $columns = array_map("trim", explode(",",$columns) );
                }
                $this->select( $columns );
            }
            // \Illuminate\Support\Collection
            $this->collection = parent::get();
           
		} catch( \Illuminate\Database\QueryException $e ) {
            $excp = new Excp('数据库查询错误', 500, ['sql'=>$this->toSql(), 'QueryExceptionMessage'=>$e->getMessage(), 'QueryExceptionCode'=>$e->getCode()]);
            $excp->log();
            throw $e;
		}

		return $this;
    }

    /**
     * [reload] Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        $rows = $this->selectRaw("COUNT('{$columns}') as cnt" )
                     ->get()
                     ->toArray()
                ;
        $row = current( $rows );
        return intval( $row["cnt"] );
    }


    /**
     * 返回结果集
     */
    public function toArray() {
        if ( empty($this->collection) && empty($this->LengthAwarePaginator) ) {
            throw new Excp("请先调用Get/Paginate 方法", 500);
        }

        // Get 查询
        if ( !empty($this->collection) ) {
            $rows = $this->collection->toArray();
            $structs = $this->getQueryFieldStructs();
            foreach($rows as & $row) {
                $this->filter( $row, $structs );
            }

            // Reset collection
            $this->collection = null;
            return $rows;

        // paginate
        } else if ( !empty($this->LengthAwarePaginator) ) {
            $response = $this->LengthAwarePaginator->toArray();
            
            if ( is_array($response["data"]) ) {
                $structs = $this->getQueryFieldStructs();
                foreach($response["data"] as & $row) {
                    $this->filter( $row, $structs );
                }
            }

            // 增加标准数据 (MySQL 5.7 only )
            $response["perpage"] = $response["per_page"];
            $response["page"] = $response["curr"] = $response["current_page"];
            $response["last"] = $response["pagecnt"] = $response["last_page"];
            $response['next'] = ($response["last"] > $response["curr"]) ? ($response["curr"] + 1) : 0 ;
            $response['prev'] = ($response["curr"] > 1 ) ? ($response["curr"] - 1) : 0 ;
            $response['count'] = count( $response["data"]);

            return $response;
        }
    }


    /**
     * 过滤数据结果集
     */
    private function filter( & $row, & $structs ){

        $columns = $this->columns;
        if ( empty($columns) || array_search("*", $columns) !== false ) {
            $struct = current($structs);
            if (is_array($struct["fields"]) ) {
                $columns = array_keys($struct["fields"]);
            }
        }

        // 设置数据
        foreach($columns as $column ) {
            $field = $this->field( $column );
            $struct =  $this->findStruct( $field["field"], $field["table"], $structs);
            
            if ( empty($struct) ) {
                continue;
            }

            // JSON 格式数据
            if ( $struct["type"] == 'json' ) {
                $name = $field["as"];
                if ( is_string($row["{$name}"]) ) {
                    $row["{$name}"] = json_decode( $row["{$name}"], true );
                }
            }

            // 日期时间 格式数据
            else if ( $struct["type"] == 'datetime' ){
                $name = $field["as"];
                if ( !empty($row["{$name}"]) ) {
                    $row["{$name}"] = date('Y-m-d\TH:i:s', strtotime( $row["{$name}"]));
                }
            }
        }
    }

    /**
     * 查找字段结构
     */
    private function findStruct( $field, $table, & $structs ) {
        if ( !empty($table) ) {
            return $structs["{$table}"]["fields"][$field];
        }
        
        $tables = array_keys( $structs );
        foreach( $tables as $table ) {
            $fields = array_keys($structs[$table]["fields"]);
            if ( !empty($fields) && array_search($field, $fields) !== false ) {
                return $structs["{$table}"]["fields"][$field];
            }
        }

        return [];
    }


    /**
     * 查询字段结构
     * @param bool $jsonFieldsOnly 只读取JSON 字段
     */
    private function getQueryFieldStructs( $jsonFieldsOnly=true ){

        if ( empty($this->data) ) {
            throw new Excp("未找到数据驱动", 500);
        }

        // 读取数据表清单
        $tableFields =[];
        $tab = explode(" as ", $this->from);
        $table = ( count($tab) == 2) ? trim($tab[1]) : trim($tab[0]);
        $tableFields[$table] = [
            "table" => $tab[0]
        ];
        
        if ( !empty($this->joins) ) {
            array_walk( $this->joins, function( $join ) use( & $tableFields ) {
                $tab = explode(" as ", $join->table);
                $table = ( count($tab) == 2) ? trim($tab[1]) : trim($tab[0]);
                $tableFields[$table] = [
                    "table" => $tab[0]
                ];
            });
        }

        // 读取字段清单
        foreach( $tableFields as $asTable => $tableField) {
            $table = $tableField["table"];
            $tableFields["$asTable"]["fields"] = $this->data->table($table)->getFields();
            if ( $jsonFieldsOnly ) {
                foreach( $tableFields["$asTable"]["fields"] as  $field => $struct ){

                    if ( $struct["type"] !== 'json' && 
                         $struct["type"] != "datetime" && 
                         $struct["type"] != "date"  ) {
                        unset( $tableFields["$asTable"]["fields"][$field] );
                    }
                }
            }
        }    
        
        return $tableFields;
    }
}

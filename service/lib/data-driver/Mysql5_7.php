<?php
namespace Xpmse\DataDriver;
require_once( __DIR__ . '/../Inc.php');
require_once( __DIR__ . '/../Conf.php');
require_once( __DIR__ . '/../Err.php');
require_once( __DIR__ . '/../Excp.php');
require_once( __DIR__ . '/../Utils.php');
require_once( __DIR__ . '/../data-driver/Data.php');
require_once( __DIR__ . '/../data-driver/Sqlquerybuilder.php');

use \Exception as Exception;
use \PDO as PDO;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;
use \Mina\Cache\Redis as Cache;


use \Xpmse\DataDriver\Data as Data;
use \Illuminate\Database\Capsule\Manager as DB;
use \Illuminate\Database\Query\Builder as QueryBuilder;
use \Illuminate\Database\ConnectionInterface as ConnectionInterface;
use \Illuminate\Database\Query\Grammars\Grammar as Grammar;
use \Illuminate\Database\Query\Processors\Processor as Processor;
use \Illuminate\Support\Collection  as Collection;


/**
 * 数据库驱动 ( Base On Illuminate/database, Required Mysql5.7 ) 
 */
class Mysql5_7 implements Data {

	/**
	 * @var \Illuminate\Database\Capsule\Manager 对象
	 */
	private $db = null;

	/**
	 * @var \Illuminate\Database\Connection 对象数组
	 */
	private $conn = null;


	/**
	 * @var 主表名称
	 */
	private $table = null;
    

	/**
	 * @var 错误结构体
	 */
	public $errors = [];


	private $prefix = null;

	/**
	 * 数据库配置
	 * @var array
	 */
    private $options = [];
    

    /**
     * 数据缓存
     */
    private $cache = null;


    /**
     * 缓存数据
     */
    private $cacheData = [];


	/**
	 * 构造函数
	 * @param array $options 数据库配置选项
	 */
	function __construct( $options = [] ) {


		$this->options = $options;

		$driver = !empty($options['driver']) ? trim($options['driver']) : 'mysql';
		$this->table = !empty($options['table']) ?  trim( $options['table'] ) : null;
		
		// 从配置文件中读取数据库连接信息
		if ( $driver == 'mysql' ) {
			$c = Conf::G('supertable/storage/option');

			$cprefix = Conf::G('supertable/storage/prefix');
			$cprefix = !empty($cprefix) ? trim($cprefix) : '';
			$this->options['global_prefix'] = $cprefix;

			// 兼容旧版
			if ( $cprefix === '{auto}') {
				$cprefix = '';
				$prefix = '{auto}';
			}

			$prefix = !empty($options['prefix']) ? $options['prefix'] : '{auto}';

			$wt = is_array($c['master']) ? current($c['master']) : []; 
			$rd = is_array($c['slave']) ? current($c['slave']) : $wt; 
			
			$db_name = !empty($c['db_name']) ? trim($c['db_name']) : 'xpmse';

			$rd['charset'] = !empty($rd['charset']) ? $rd['charset'] : 'utf8';
			$rd['collation'] = !empty($rd['collation']) ? $rd['collation'] : 'utf8_unicode_ci';

			$wt['charset'] = !empty($wt['charset']) ? $wt['charset'] : 'utf8';
			$wt['collation'] = !empty($wt['collation']) ? $wt['collation'] : 'utf8_unicode_ci';

			// 处理配置前缀
			$prefix =  str_replace('{auto}', Utils::app(), $prefix ) . "_";
			$prefix = str_replace('{app}', Utils::app(), $prefix );
			
		}


		// 读库 连接参数
		$read = [
			"driver" => $driver,
			"host" => !empty($options['read']['host']) ? trim($options['read']['host']): "{$rd['host']}:{$rd['port']}",
			"username" => !empty($options['read']['username']) ? trim($options['read']['username']): "{$rd['user']}",
			"password" => !empty($options['read']['password']) ? trim($options['read']['password']): "{$rd['pass']}",
			"charset" => !empty($options['charset']) ? trim($options['charset']): "{$rd['charset']}",
			"collation" => !empty($options['collation']) ? trim($options['collation']): "{$rd['collation']}",
			"database" => !empty($options['database']) ? trim($options['database']): $db_name,

		];

		// 写库 连接参数
		$write = [
			"driver" => $driver,
			"host" => !empty($options['write']['host']) ? trim($options['write']['host']): "{$wt['host']}:{$rd['port']}",
			"username" => !empty($options['write']['username']) ? trim($options['write']['username']): "{$wt['user']}",
			"password" => !empty($options['write']['password']) ? trim($options['write']['password']): "{$wt['pass']}",
			"charset" => !empty($options['charset']) ? trim($options['charset']): "{$wt['charset']}",
			"collation" => !empty($options['collation']) ? trim($options['collation']): "{$wt['collation']}",
			"database" => !empty($options['database']) ? trim($options['database']): $db_name,
		];

        $this->db = new DB;
        

		$read_cname  = "_db_read_" . md5( implode('_', array_values($read) ) );
		$write_cname  = "_db_write" . md5( implode('_', array_values($write) ) );

		if ( empty($GLOBALS[$read_cname]) ) {
			$this->db->addConnection($read, 'read');
			$GLOBALS[$read_cname] = $this->conn['read'] = $this->db->getConnection('read');
		} else {
			$this->conn['read'] = $GLOBALS[$read_cname];
		}

		if ( empty($GLOBALS[$write_cname]) ) {
			$this->db->addConnection($write, 'write');
			$GLOBALS[$write_cname] = $this->conn['write'] = $this->db->getConnection('write');
		} else {
			$this->conn['write'] = $GLOBALS[$write_cname];
        }
        
        // 支持JSON字段
        // $this->conn['read']->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineType('json');
        // $this->conn['write']->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineType('json');

		// Fix Connection BUG 
		$prefix = !empty($options['prefix']) ? trim($options['prefix']): $prefix;
	
		// 添加 cprefix
		$this->prefix =  ( strpos($prefix, '{nope}') !== false ) ? '' : $cprefix . $prefix;

		// 添加 cprefix
        $this->prefix =  ( strpos($prefix, '{none}') !== false ) ? $cprefix : $this->prefix;
        

        // 数据缓存
        $this->cache = new Cache( [
            "prefix" => "_system:database:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);
	}



	/**
	 * 添加一条记录
	 * @param  array $data 记录数组（需包含所有必填字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function create( $data  ) {
		
		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		
		// 过滤数据
		$this->_fliter($data);

		// 更新创建时间
		if ( !isset($data['created_at']) ) {
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		$table = $this->db()->table($this->table);
		
		try {
			$id = $table->insertGetId( $data );

		} catch ( \Illuminate\Database\QueryException $e  ){

			$message = $e->getMessage();
			// $code = $e->getCode();
			// if ( is_array($e->errorInfo) && count($e->errorInfo) === 3 ) {
			// 	$code= $e->errorInfo[1];
			// 	$message= $e->errorInfo[2];
            // }

			throw new Excp( "数据库查询错误", 500, [
                    "code" => $e->errorInfo[1],    
                    'message'=>$e->errorInfo[2],
                    'sql'=>$e->getSql(),
					'bindings'=>$e->getBindings(),
					"detail"=>$e->getMessage(),
					'input'=>$data,
			]);
		}


		return $this->getLine("_id=?", [], [$id]);
	}





	/**
	 * 根据数据表主键，修改数据记录
	 * @param  array $data 记录数组（需修改的字段 map）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function update( $_id, $data ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		// 过滤数据
		$this->_fliter($data);

		// 更新 更新时间
		if ( !isset($data['updated_at']) ) {
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		try {
			$table = $this->db()->table($this->table);
			$resp = $table->where("_id", $_id )
			 	 ->update( $data );

		} catch ( \Illuminate\Database\QueryException $e  ){

			throw new Excp( $e->getMessage(), $e->getCode(), [
					'_id' => $_id,
					'data'=>$data,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}

        return $this->getLine("_id=? and  ( ISNULL(deleted_at) or deleted_at is not null ) ", [], [$_id]);

	}


	/**
	 * 根据指定唯一索引，修改数据记录
	 * @param  string $uni_key 唯一索引名称
	 * @param  array  $data	记录数组（ 需包含 uni_key 字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function updateBy( $uni_key, $data ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		// 过滤数据
		$this->_fliter($data);

		// Unikey
		if ( !isset($data[$uni_key] ) ) {
			throw new Excp("未知 $uni_key 数值", 404, ['uni_key' => $uni_key, 'data'=>$data] );
		}

		// 更新 更新时间
		if ( !isset($data['updated_at']) ) {
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		try {

			$table = $this->db()->table($this->table);
			$resp = $table->where($uni_key, trim($data[$uni_key]) )
			  	  ->update( $data );

			 // var_dump($table);

		} catch ( \Illuminate\Database\QueryException $e  ){

			throw new Excp( $e->getMessage(), $e->getCode(), [
					'uni_key' => $uni_key,
					'data'=>$data,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}

		return $this->getLine("`$uni_key`=? and ( ISNULL(deleted_at) or deleted_at is not null ) ", ["*"], [$data[$uni_key]]);
	}



	/**
	 * 创建一条数据，如果存在则更新(本函数应该重构)
	 * @param  array	  $data		数据集合
	 * @param  array|null $updateColumns 待更新字段清单，为 null 则更新 data 中填写的字段。
	 * @return boolean 成功返回  true, 失败返回false
	 */
	public function createOrUpdate(  $data,  $updateColumns = null ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}
		
		$this->_fliter($data, false );
		
		if ( isset($data['created_at']) ) {
			unset($data['created_at']);
		}

		if ( isset($data['updated_at']) ) {
			unset($data['updated_at']);
		}

		$table = $this->db()->getTablePrefix().$this->table;
		$questions = []; $rawdata =[]; $rawquestions = []; $dataOrigin = $data;
		foreach ($data as $key => $value) {
			// RAW DATA 
			if ( is_string($value) && preg_match('/^DB\:\:RAW\((.*)\)$/', $value, $match)) { 
				$rawdata[$key] = $this->db()->raw($match[1]);
				$rawquestions[] = $rawdata[$key];
				unset( $data[$key]);
				continue;
			}

			$questions[] = ":$key";
        }


		$columnNamesString =  '`' . implode('`,`', array_keys($data)) . '`';
		$rawcolumnNamesString = "";
		if ( !empty($rawdata) ) {
			$rawcolumnNamesString =  '`' . implode('`,`', array_keys($rawdata)) . '`';
			$rawcolumnNamesString = !empty($rawcolumnNamesString) ? ",$rawcolumnNamesString" : "";
		}
	   

		$rawmarks = implode(',', $rawquestions);
		$rawmarks = !empty($rawmarks) ? ",$rawmarks" : "";
		$marks = '(' . implode(',', $questions) . $rawmarks.  ',CURRENT_TIMESTAMP )';
		$sql  = 'INSERT INTO `' . $table . '`(' . $columnNamesString . $rawcolumnNamesString. ', `created_at`) VALUES' . PHP_EOL;
		
		$sql .=  $marks . PHP_EOL;
		$sql .= 'ON DUPLICATE KEY UPDATE ';
		
		if (empty($updateColumns)) {
			$sql .= static::buildValuesList($dataOrigin);
		}else {
			$sql .= static::buildValuesList($updateColumns) ;
		}

		$sql .= ', `updated_at` = CURRENT_TIMESTAMP';

		try {
			$resp = $this->db()->statement( $sql, $data );

		} catch ( \Illuminate\Database\QueryException $e  ) {

			throw new Excp( $e->getMessage(), $e->getCode(), [
					'sql' => $sql,
					'return' => $return,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}

		return $resp;


	}


	// 新增快捷操作函数 1.6.10
	
	/**
	 * 保存数据，不存在则创建，存在则更新
	 * @param  array $data Key Value 结构数据
	 * @return 插入或更新的数据
	 */
	public function saveBy( $uniqueKey, $data, $keys=null, $select=['*']  ) {

      
		if ( empty($keys) || $keys == null ) {
			$keys = [$uniqueKey];
		}

		$columns = $this->getColumns();
		$updateColumns = array_intersect(array_keys($data), $columns);
		unset($updateColumns[$uniqueKey]);
		
		// 自动生成ID 
		$ukey = current($keys);
		if ( empty($data[$ukey]) ) {
			$data[$ukey] = $this->genId();
		}


		$uni = $this->getFirstUniquekey($data, $keys );
		if ( empty( $uni ) ) {
			throw new Excp("缺少必填字段", 402, ['data'=>$data, 'keys'=>$keys, 'uniqueKey'=>$uniqueKey]);
		}
		$resp = $this->createOrUpdate( $data, $updateColumns );

		// var_dump($resp);

		if ( $resp === false ) {
			throw new Excp("保存数据失败", 500, ['data'=>$data] );
		}

        if ( $uniqueKey != $uni['key'] ) {
            $uni['key'] = $uniqueKey;
            $uni['value'] = $data[$uniqueKey];
        }

        // header("Debug-saveBy:{$uni['key']} VS {$uni['value']}");
		return $this->getBy($uni['key'], $uni['value'], $select );
	}


	/**
	 * 根据唯一键, 查找一条数据
	 * @param  string $uniqueKey 唯一键名
	 * @param  mix $value 数值
	 * @return 数据记录或空数组
	 */
	public function getBy( $uniqueKey, $value,  $select=['*'] ) {

        $qb = $this->query()->where($uniqueKey, '=', $value)->limit(1);
        header("Debug-getBySql: ". $qb->getSql() );
        $rows = $qb->select( $select )->get()->toArray();
        header("Debug-getByRes: ". count($rows) );
		if ( empty($rows) ) {
			return [];
		}
		return current( $rows );
	}


	/**
	 * 读取传入数据中，第一个uni_key
	 * @param  array $data 数据
	 * @param  array $keys 字段数组
	 * @return null / ["key"=>"...", "value"=>"..."]
	 */
	public function getFirstUniquekey( $data, $keys ) {

		$value = null; $uni_key = null;
		foreach ($keys as $key ) {
			if ( array_key_exists($key, $data) ) {
				$value = $data[$key];
				$uni_key = $key;
				break;
			}
		}

		if ( empty($uni_key) ) {
			return null;
		}

		return ["key"=>$uni_key, 'value'=>$value];
	}


	/**
	 * 自动生成ID
	 * @return [type] [description]
	 */
	public function genId() {
		return $this->uniqid();
	}


	/**
	 * 删除数据表
	 * @return [type] [description]
	 */
	public function __clear() {
		return $this->dropTable();
	}

	/**
	 * 添加默认数据 ( + version: 1.16.20 )
	 * @return [type] [description]
	 */
	public function __defaults(){
		return true;
	}


	/**
	 * 处理文件数据 ( + version: 1.16.21 )
	 * @param  array &$rs 数据记录引用
	 * @param  array $fields 文件字段列表
	 * @param  Meida $media Media 对象
	 * @return 
	 */
	public function __fileFields( &$rs, $fields, $media=null ){

		if ( $media == null ) return ;
		if ( empty($fields) ) return ;
        if ( !is_array($fields)) return;
        if ( empty($rs) ) return;

		foreach ($fields as $field ) {

			if ( array_key_exists($field, $rs) ) {
				$values = is_array($rs["$field"]) ? $rs["$field"] :[]; 
				$pad = false;

				// 只有路径
				if ( is_string($rs["$field"]) ) {
					$values["path"] = $rs["$field"];
				}

				// 一条数据
				if ( array_key_exists("path", $values) ) {
					$pad = true;
					$values = [$values];
				}

				// 添加字段
				$rs[$field] = [];
				foreach($values as $v) {

                    // 多张图组形式
                    if  ( is_string( $v) ) {
                        $v = [
                            "path" => $v
                        ];
                    }

					if ( empty($v["path"]) ){
                        if ( is_array($v) && !empty($v["url"]) ) {
                            array_push($rs[$field], $v);    
                        } else {
                            array_push($rs[$field], ["url"=>"","origin"=>""]);
                        }
					}else {
                        $uri = $media->get( $v["path"] );
                        $uri = array_merge( $v, $uri );
						array_push($rs[$field], $uri );
					}
				}

				if ( $pad === true ){
					$rs[$field] = current($rs[$field]);
				}
			}
		}

		return true;
	}



	/**
	 * 清空表中数据
	 * @return [type] [description]
	 */
	public function truncate(){
		return $this->runSQL("truncate table {{table}}");
	}



	// END 新增快捷操作函数 1.6.10


	protected static function buildValuesList(array $updatedColumns) {
		$out = [];
		foreach ($updatedColumns as $key => $value) {
			// $out[] =sprintf('`%s` = VALUES(`%s`)', $key, $key);

			if (is_numeric($key)) {
				$out[] = sprintf('`%s` = VALUES(`%s`)', $value, $value);
			} else {
				$out[] = sprintf('`%s` = VALUES(`%s`)', $key, $key);
			}
		}
		return implode(', ', $out);
	}



	/**
	 * 根据数据表主键，删除数据记录
	 * @param  int  $_id	  数据表主键
	 * @param  boolean $mark_only 是否为标记删除， 默认 fasle	
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	function delete( $_id, $mark_only=false ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		if ( $mark_only === true ) {
			$time = date('Y-m-d H:i:s');
			$row = $this->update( $_id, ['deleted_at'=>$time]);
			if ( $row['deleted_at'] == $time ) {
				return true;
			}
			return false;
		}

		// 真删除
		try {

			$table = $this->db()->table($this->table);
			$table->where('_id', $_id )
			  ->delete();

		} catch ( \Illuminate\Database\QueryException $e  ){

			throw new Excp( $e->getMessage(), $e->getCode(), [
					'_id' => $_id,
					'mark_only'=>$mark_only,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}

		return true;

	}


	/**
	 * 根据数据表唯一索引数值，删除数据记录(待优化)
	 * @param  mix   $data_key  唯一索引数值
	 * @param  string  $uni_key   唯一索引键名，默认 "_id"	
	 * @param  boolean $mark_only 是否为标记删除， 默认 true 
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){
		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		if ( $mark_only === true ) {
            $time = time();
            $data = ['deleted_at'=>date("Y-m-d H:i:s",$time), "$uni_key"=>$data_key];
			$row = $this->updateBy( $uni_key, $data );
			if ( strtotime($row['deleted_at']) == $time ) {
				return true;
			}
			return false;
		}
		
		// 真删除
		try {
			$table = $this->db()->table($this->table);
			$table->where("$uni_key", $data_key )->delete();
		} catch ( \Illuminate\Database\QueryException $e  ){

			throw new Excp( $e->getMessage(), $e->getCode(), [
					'uni_key' => $uni_key,
					'data_key' => $data_key,
					'mark_only'=>$mark_only,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}
		return true;
	}



	/**
	 * 查询数据表, 返回结果集
	 * @param  string $query  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	function select( $sql="where 1", $fields=["*"], $data=[] ) {
		
		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
        }

        if (is_string($fields) ) {
            $fields = array_map('trim', explode(',', $fields));
        }

        if (empty($fields)) {
            $fields = ["*"];
        }


        // SQL 处理
        $sql = str_replace("where", "", strtolower($sql));;
        if ( strpos( $sql, "deleted_at") !== false ){
            $qb = $this->query("read", true);
        } else {
            $qb = $this->query();
        }
        
        $qb ->whereRaw($sql, $data)
                    ->select( ...$fields )
        ;
        $rows = $qb ->get()
                    ->toArray()
        
        ;
       
        return ['data'=>$rows, "total"=>count($rows)];
	}


	/**
	 * 查询数据表, 仅返回结果集
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	function getData( $query="", $fields=[], $data=[]) {
		$resp = $this->select( $query, $fields, $data );
		return ( is_array($resp['data']) ) ? $resp['data'] : [];
	}


	/**
	 * 查询数据表，返回最后一行数据
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	function getLine( $query="where 1", $fields=[], $data=[]) {

		
		$query = empty( $query ) ?  "where 1" : $query;

		$resp = $this->select( $query, $fields, $data ); 
		if ($resp['total'] <= 0 ) {
			return [];
		}

		$data = end( $resp['data']);
		return $data;
	}


	/**
	 * 查询数据表，返回一行中指定字段的数值
	 * @param  string  $field_name 字段名称
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	function getVar( $field_name, $query="", $data=[] ) {
        $row = $this->getLine($query, $field_name, $data ) ;
		return $row[$field_name];
	}



	/**
	 * 读取查询构造器
	 * @see https://laravel.com/docs/5.3/queries
	 * @see https://laravel.com/docs/5.3/pagination
	 * @see https://github.com/illuminate/database/blob/master/Query/Builder.php
	 * @return \Illuminate\Database\Query\Builder 对象
	 */
	function query( $db="read", $include_removed=false ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		$db = $this->db( $db );
		$db->setFetchMode(PDO::FETCH_ASSOC);

		$qb = new sqlQueryBuilder(
			$db, $db->getQueryGrammar(), $db->getPostProcessor(),
			$this
		);

		if ( $include_removed === true) {
			return $qb->from($this->table);
		}


		$tb = explode(" as ", $this->table);
		$table = ( count($tb) == 2) ? trim($tb[1]) : trim($tb[0]);

		return $qb->from($this->table)->whereNull($table .'.deleted_at');
		// return $qb->from($this->table)->where($this->table.'.deleted_at','=',null);
	}


	/**
	 * 运行 SQL
	 * @param  string $sql SQL语句
	 * @param  bool $return 是否返回结果
	 * @return mix $return = false， 成功返回 true, 失败返回 false; $return = true , 返回运行结果
	 */
	function runsql( $sql, $return=false, $data=[] ) {
		
		$db = $this->db();
		$table_fullname = $db->getTablePrefix().$this->table;
		$sql = str_replace('{{table}}', $table_fullname, $sql );

		if ( $return ) {
			
			$db->setFetchMode(PDO::FETCH_ASSOC);
			
			try {
				$rows = $db->select($sql, $data);
			} catch ( \Illuminate\Database\QueryException $e  ){

				throw new Excp( $e->getMessage(), $e->getCode(), [
						'sql' => $sql,
						'return' => $return,
						'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
					]);
            }
            
			return $rows;
		}

		try {

			$resp =  $db->statement( $sql, $data );

		} catch ( \Illuminate\Database\QueryException $e  ){


			throw new Excp( $e->getMessage(), $e->getCode(), [
					'sql' => $sql,
					'return' => $return,
					'message'=>$e->getMessage(), '__trace__'=>$e->getTrace()
				]);
		}

		return $resp;
	}


	/**
	 * 读取数据表前缀
	 * @return [type] [description]
	 */
	public function getPrefix() {
		return $this->db()->getTablePrefix();
	}
	


	/**
	 * 返回错误记录栈
	 * @return array 错误栈
	 */
	function getErrors() {

		return $this->errors;
	}


	/**
	 * 获取自增ID 
	 * @return [type] [description]
	 */
	function nextid() {

		$table_fullname = $this->db()->getTablePrefix().$this->table;

		$data = $this->runsql("SELECT AUTO_INCREMENT as last_id FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME=?", 2 ,  [$table_fullname] );
		$row = end($data );
		if ( isset($row['last_id']))  {
			return intval($row['last_id']);
		}


		throw new Excp("读取自增ID失败", 500, ["data"=>$data]);

		// return intval($last_id);
	}

	/**
	 * 根据数据表ID 读取一条记录
	 * @param  [type] $_id [description]
	 * @return [type]	  [description]
	 */
	function get( $_id ) {
		return $this->getLine("where `_id`=?", [], [$_id]);
	}


	// 数据库结构
	// @see https://laravel.com/docs/5.3/migrations#creating-columns
	
	/**
	 * 创建数据表 ( DataBase )
	 * @return [type] [description]
	 */
	public function createTable() {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		$schema = $this->db()->getSchemaBuilder();
		
		// 创建数据表
		$schema->create( $this->table, function( $table ) {
			$table->bigIncrements('_id')->unsigned();
			$table->softDeletes()->index();
			$table->timestampsTz();
		});

		return $this;
	}


	/**
	 * 删除数据表 ( DataBase Only )
	 * @return [type] [description]
	 */
	public function dropTable() {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		$schema = $this->db()->getSchemaBuilder();
		$schema->dropIfExists($this->table);
		return $this;
	}


	/**
	 * 检查数据表是否存在
	 * @return [type] [description]
	 */
	public function tableExists() {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, ['table' => null] );
		}

		$table_fullname = $this->db()->getTablePrefix(). $this->table;
		$tb = explode(" as ", $table_fullname);
		$tab =  trim($tb[0]);
		$resp = $this->runSql( "SHOW TABLES LIKE '$tab' ", true);

		if ( count($resp) == 1 ) {
			return true;
		}
		
		return false;
	}


	/**
	 * 读取数据表名称
	 * @return [type] [description]
	 */
	function getTable() {
		return $this->table();
	}


	/**
	 * 读取数据表索引字段
	 * @return [type] [description]
	 */
	function getIndexes() {
		$data = $this->runsql("show index from {{table}}", true, []);
		$map = [];
		foreach ($data as & $index ) {
			$field = $index["Column_name"];
			$map[$field] = $index = [
				"field" => $field,
				"key" => strtolower($index['Key_name']),
				"unique" => ($index['Non_unique'] === 0) ? true : false,
				"table_fullname" => strtolower( $index['Table']),
				"table" =>$this->table,
				"prefix" => $this->getPrefix(),
				"origin_data"=>$index
			];
		}
		return ["data"=>$data, "map"=>$map];
	}


	/**
	 * 读取数据表结构信息
	 * @return
	 */
	public function getStruct() {

		$table = $this->table;
		$prefix = $this->db()->getTablePrefix();
		$fields = $this->getColumns();
		$indexes = $this->getIndexes();

		$field_map = [];
		foreach ($fields as & $field ) {
			$field = $this->getColumn($field);
			$name = $field['name'];
			$field["index"] = false;
			$field["unique"] = false;
			if ( is_array($indexes['map'][$name]) ) {
				$field["index"] = true;
				$field["unique"] = $indexes['map'][$name]["unique"];
			}

			$field_map[$name] = $field;
		}

		return [
			'table' => $table, 
			'prefix' => $this->options["prefix"],
			'global_prefix' => $this->options["global_prefix"],
			'full_prefix' => $prefix,
			'indexes'=>$indexes,
			"fields" => ["data"=>$fields, "map"=>$field_map]
		];
	}


	/**
	 * 读取数据表 $column_name 结构
	 * @param  [type] $column_name [description]
	 * @return [Type] Type 结构体
	 */
	public function getColumn( $column_name ){
		
		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
		}

		$schema = $this->db()->getSchemaBuilder();
		$type = $schema->getColumnType( $this->table, $column_name );
		$table_fullname = $this->db()->getTablePrefix().$this->table;
		$column = $this->db()->getDoctrineColumn( $table_fullname, $column_name );
		$resp = $column->toArray();
		$resp['type'] = $type;

		return $resp;
	}


	/**
	 * 读取数据表列结构
	 * @return [type] [description]
	 */
	public function getColumns() {

		// var_dump( '@getColumns table is:', $this->table , ' fullname is:', $this->db()->getTablePrefix().$this->table );

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
		}

		return $this->db()->getSchemaBuilder()->getColumnListing( $this->table );
    }
    
	/**
	 * 为数据表添加一列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type  Type 结构体
	 * @return $this
	 */
	public function addColumn( $column_name, $type ){
        
        
		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
        }

		// 传递参数
		$schema = $this->db()->getSchemaBuilder();
		$GLOBALS['_addColumnArgs'] = func_get_args();

		

		// 创建数据表
		if ( $schema->hasTable( $this->table ) === false ) {
			$this->createTable();
		}

		// 如果字段存在，返回错误
		if ( $schema->hasColumn( $this->table, $column_name ) ) {

			throw new Excp('字段已存在', 1062, [
				'table' => $this->table,
				'column_name'=>$column_name, 
				'type'=>$type ]);
		}

		// 添加字段
		$schema->table( $this->table, function($table){
			$args = $GLOBALS['_addColumnArgs'];
			$column_name = $args[0];
			$type = $args[1];

			$column = $type['column'];
            $index = $type['index'];
            
			// 添加字段
			if ( isset($column['method'])) {
				$args = !empty($column['args']) ? array_merge([$column_name], $column['args']) : [$column_name];

				$cm = call_user_func_array([$table, $column['method']], $args );
				
				// 默认值
				if ( isset($column['option']['default'])  ) { 

					if ( $column['option']['default'] === 'DB::RAW:CURRENT_TIMESTAMP' ) {
	
						$cm->useCurrent();

					} else if ( preg_match('/^DB\:\:RAW\((.*)\)$/', $column['option']['default'], $match)) { 

						$rawdata = $match[1];
						$cm->default($this->db()->raw( $rawdata));

					} else {
						$cm->default( $column['option']['default'] );	
					}
                }
                
                // 添加注释
                if ( isset($column['option']['comment'])  ) { 
                    $cm->comment($column['option']['comment']);
                }


				// 允许空值 ( null = true  ) 默认允许 null=false 不允许
				if ( !isset($column['option']['null']) || $column['option']['null'] === true  ) { 
					try { $cm->nullable(); }catch( Exception $e ) {}
				// 不允许空值 
				} else {
					try { $cm->nullable(false); }catch( Exception $e ) {}
				}

				// unsigned 无符号
				if ( isset($column['option']['unsigned']) 
					&& $column['option']['unsigned']===true  ) { 
					$cm->unsigned();
				}

			}


			// 添加索引
			if ( isset($index['method']) ) {
				$args = [$column_name];
				if ( $index['index_name'] != null ) {
					$args[] = strtolower($index['index_name']);
				}
				call_user_func_array([$table, $index['method']], $args );
			}


		});

		unset($GLOBALS['_addColumnArgs']);
		return $this;
	}



	/**
	 * 修改数据表 $column_name 列结构
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type		Type 结构体
	 * @return $this
	 */
	public function alterColumn( $column_name, $type ) {

		// @see https://laravel.com/docs/5.3/migrations
		// Can't Change types 
		// The following column types can not be "changed": 
		// char, double, enum, mediumInteger, timestamp, tinyInteger, ipAddress, json, jsonb, macAddress, mediumIncrements, morphs, nullableMorphs, nullableTimestamps, softDeletes, timeTz, timestampTz, timestamps, timestampsTz, unsignedMediumInteger, unsignedTinyInteger, uuid.

		$notAllowedTypes = ["char", "double", "enum", "mediumInteger", "timestamp", "tinyInteger", "ipAddress", "json", "jsonb", "macAddress", "mediumIncrements", "morphs", "nullableMorphs", "nullableTimestamps", "softDeletes", "timeTz", "timestampTz", "timestamps", "timestampsTz", "unsignedMediumInteger", "unsignedTinyInteger", "uuid"];

		// file_put_contents("/tmp/db.log", Utils::get($type), FILE_APPEND );

		$typename = isset($type['column']['method']) ? $type['column']['method'] : null;

		if ( in_array($typename, $notAllowedTypes) ) {
			return $this;
		}


		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
		}
        
		// 传递参数
		$schema = $this->db()->getSchemaBuilder();
		$GLOBALS['_alterColumnArgs'] = func_get_args();

		

		// 如果字段存在，返回错误
		if ( !$schema->hasColumn( $this->table, $column_name ) ) {

			throw new Excp('字段不存在', 404, [
				'table' => $this->table,
				'column_name'=>$column_name, 
				'type'=>$type ]);
		}


		// 修改字段
		$schema->table( $this->table, function($table){
			$args = $GLOBALS['_alterColumnArgs'];
			$column_name = $args[0];
			$type = $args[1];

			$column = $type['column'];
            $index = $type['index'];
            
			// 删除索引
			if ( isset($index['method']) ) {

				$sm = $this->db()->getDoctrineSchemaManager();
				$table_fullname = $this->db()->getTablePrefix().$this->table;
				// echo '@index:>'.  $table_fullname . "\n";
				// file_put_contents("/tmp/db2.log", '@index:>'.  $table_fullname . "\n", FILE_APPEND);


				$index_name = [$column_name];
				$index_real_name = strtolower("{$this->table}_{$column_name}_index");
				$index_index_name = strtolower("{$this->table}_{$column_name}_index");
				$index_unique_name = strtolower("{$this->table}_{$column_name}_unique");
				
				if ( $index['index_name'] != null ) {
					$index_unique_name = $index_index_name = $index_real_name = $index_name = $index['index_name'];
				}

				if ( is_string($index['dropindex']) ) {
					$index_unique_name = $index_index_name = $index_real_name = $index_name = $index['dropindex'];
				}

				$indexes = $sm->listTableIndexes( $table_fullname );


				if( isset($indexes[$index_index_name])) {
					$idx = $indexes[$index_index_name];
					if ( $idx->isSimpleIndex() ) {
						$table->dropIndex( $index_name);
					}
				} else if ( isset($indexes[$index_unique_name]) ) {
					$idx = $indexes[$index_unique_name];
					// echo "===== $index_unique_name ===== \n";
					if ( $idx->isUnique() ) {
						$table->dropUnique($index_name);
					}
				}
			}


			// 更新字段
			if ( isset($column['method'])) {
				$args = !empty($column['args']) ? array_merge([$column_name], $column['args']) : [$column_name];

				$cm = call_user_func_array([$table, $column['method']], $args );

				// 默认值
				if ( isset($column['option']['default'])  ) {

					if ( $column['option']['default'] === 'DB::RAW:CURRENT_TIMESTAMP' ) {
						$cm->useCurrent();

					} else if ( preg_match('/^DB\:\:RAW\((.*)\)$/', $column['option']['default'], $match)) { 
						$rawdata = $match[1];
						$cm->default($this->db()->raw( $rawdata));

					} else {
						$cm->default( $column['option']['default'] );	
					}
					
                }
                
                 // 添加注释
                if ( isset($column['option']['comment'])  ) { 
                    $cm->comment($column['option']['comment']);
                }


				// 允许空值 ( null = true  ) 默认允许 null=false 不允许
				if ( !isset($column['option']['null']) || $column['option']['null'] === true  ) { 
					try { $cm->nullable(); }catch( Exception $e ) {}

				// 不允许空值 
				} else { 
					try { $cm->nullable(false); }catch( Exception $e ) {}
				}

				// unsigned 无符号
				if ( isset($column['option']['unsigned']) 
					&& $column['option']['unsigned']===true  ) { 
					$cm->unsigned();
				}

				$cm->change();
			}


			// 添加索引
			if ( isset($index['method']) ) {
				$args = [$column_name];
				if ( $index['index_name'] != null ) {
					$args[] = strtolower($index['index_name']);
				}
				call_user_func_array([$table, $index['method']], $args );
			}

		});

		unset($GLOBALS['_alterColumnArgs']);
		return $this;
	}


	/**
	 * 替换数据表 $column_name 列结构（ 如果列不存在则创建)
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type		Type 结构体
	 * @return $this
	 */
	public function putColumn( $column_name, $type ) {

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
		}

		// 传递参数
		$schema = $this->db()->getSchemaBuilder();

		


		// 如果字段存在，创建
		if ( !$schema->hasColumn( $this->table, $column_name ) ) {
			return $this->addColumn( $column_name, $type );
		} else {
			return $this->alterColumn( $column_name, $type );
		}
	}


	/**
	 * 删除数据表 $column_name 列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param boolen $allow_not_exists 数据表是否存在
	 * @return $this
	 */
	public function dropColumn( $column_name, $allow_not_exists=false ){

		if ( $this->table == null ) {
			throw new Excp('未选定数据表', 404, [
				'table' => null,
				'column_name'=>$column_name, 
				'type'=>$type ] );
		}

		// 传递参数
		$schema = $this->db()->getSchemaBuilder();
		$GLOBALS['_dropColumnArgs'] = func_get_args();

		
		// 如果字段存在，不允许返回错误
		if ( !$schema->hasColumn( $this->table, $column_name ) ) {

			if ( !$allow_not_exists ) {
				throw new Excp('字段不存在', 404, [
					'table' => $this->table,
					'column_name'=>$column_name, 
					'type'=>$type ]);
			}

			unset($GLOBALS['_dropColumnArgs']);
			return $this;
		}

		// 修改字段
		$schema->table( $this->table, function($table){
			$args = $GLOBALS['_dropColumnArgs'];
			$column_name = $args[0];
			$table->dropColumn( $column_name );
		});

		unset($GLOBALS['_dropColumnArgs']);

		return $this;
	
	}

	

	/**
	 * 选定当前数据表格
	 * @param  [type] $table [description]
	 * @return [type]		[description]
	 */
	public function table( $table = null ) {
		
		if ( empty($table) ) {
			return $this->table;
		}

		$this->table = $table;
		return $this;
	}




	/**
	 * 快速读取数据库连接
	 * 
	 * @param  [type] $name [description]
	 * @return [type]	   [description]
	 */
	function db( $name = 'write' ) {

		if (!isset($this->conn[$name])) {
			throw new Excp('数据库连接不存在', 404, ['name'=>$name] );
		}

		$conn = $this->conn[$name];
		$this->conn[$name]->setTablePrefix( $this->prefix );
		if ( $this->conn[$name]->getSchemaGrammar() != null ) {
			$this->conn[$name]->getSchemaGrammar()->setTablePrefix(  $this->prefix );
		}
		
		// $this->conn[$name]->enableQueryLog();
		// $this->conn[$name]->getSchemaGrammar()->setTablePrefix(  $this->prefix );

		return $conn;
	}


	/**
	 * 格式化 Type 结构体 ( 兼容 SuperTable )
	 * @param  [type] $name   [description]
	 * @param  [type] $option [description]
	 * @return [type]		 [description]
	 * 
	 * @see https://laravel.com/docs/5.3/migrations#columns
	 */
	public function type( $name, $option=[] )  {

		$allowTypes = [
			"bigIncrements"=>[],
			"bigInteger"=>[],
			"binary"=>[],
			"boolean"=>[],
			"char"=>['length'],
			"date"=>[],
			"dateTime"=>[],
			"dateTimeTz"=>[],
			"decimal"=>['precision', 'scale'],
			"double"=>['total', 'decimal'],
			"enum"=>['enum'],
			"float"=>['total', 'decimal'],
			"increments"=>[],
			"integer"=>[],
			"ipAddress"=>[],
			"json"=>[],
			"jsonb"=>[],
			"longText"=>[],
			"macAddress"=>[],
			"mediumIncrements"=>[],
			"mediumInteger"=>[],
			"mediumText"=>[],
			"morphs"=>[],
			"nullableTimestamps"=>[],
			"rememberToken"=>[],
			"smallIncrements"=>[],
			"smallInteger"=>[],
			"softDeletes"=>[],
			"string"=>['length'],
			"text"=>[],
			"time"=>[],
			"timeTz"=>[],
			"tinyInteger"=>[],
			"timestamp"=>[],
			"timestampTz"=>[],
			"timestamps"=>[],
			"timestampsTz"=>[],
			"unsignedBigInteger"=>[],
			"unsignedInteger"=>[],
			"unsignedMediumInteger"=>[],
			"unsignedSmallInteger"=>[],
			"unsignedTinyInteger"=>[],
			"uuid"=>[],
		];


		$allowIndexTypes = [
			"primary" => [],
			"unique" => [],
			"index" => []
		];

		$resp = ['index'=>[], 'column'=>[]];

		// 检查类型是否合法
		if ( !isset($allowTypes[$name]) ) {
			throw new Excp('字段类型不允许', 403, ['name'=>$name, 'option'=>$option ]);
		}

		$args = null;
			
		// 带参数
		if ( is_array($allowTypes[$name]) ) {
			foreach ($allowTypes[$name] as $key ) {
                $args[] = $option[$key];
			}
        }
        
		if ( $name == 'string' && $args[0] == null ) {
			$args = null;
        }
        
        // JSON 格式支持，兼容原来格式
		if ( isset($option['json']) && $option['json'] === true ) {
            $name = "json";
            unset($option['json']);
        }

		$resp['column'] = [
			'method' => $name, 
			'args' => $args,
			'option' => $option,
        ];
        


		// 处理索引
		if ( $option['unique'] ) {
			$resp['index'] = [
				'method' => 'unique', 
				'index_name' => $option['index_name'],
				'dropindex' => $option['dropindex'],
			];
		} else if ( $option['index'] ) {
			$resp['index'] = [
				'method' => 'index', 
				'index_name' => $option['index_name'],
				'dropindex' => $option['dropindex'],
			];
		} else if ( $option['primary'] ) {
			$resp['index'] = [
				'method' => 'primary', 
				'index_name' => $option['index_name'],
				'dropindex' => $option['dropindex']
			];
		}

		return $resp;
    }
    

    /**
	 * 返回所有字段(先从缓存中读取)
	 * @return array 字段清单
	 */
	public function getFields( $renew=false ) {

        $fields = $this->getFromCache("Fields");        
        if ( $fields === false || $renew === true ) {
            $fields = [];
            $struct = $this->getStruct();
            if ( is_array($struct["fields"]["data"]) ) {
                array_walk($struct["fields"]["data"], function( $field ) use( &$fields ) {
                    if( $field['comment'] == '{__JSON__}') {
                        $field["type"] = 'json';
                    }
                    $fields["{$field["name"]}"] = $field;
                });
            }
            $this->setCache( "Fields", $fields );
        }

        if ( empty($fields) ) {
            throw new Excp("读取字段列表失败", 500 );
        }

		return $fields;
    }


	// 工具函数
	
	/**
	 * 过滤无效输入参数
	 * @param  [type] $data [description]
	 * @return [type]	   [description]
	 */
	public function _fliter( & $data, $replaceRaw = true  ) {

        $fields = $this->getFields();

        array_walk( $data, function( & $value, $field ) use(& $data, $fields, $replaceRaw) {

            if (!isset($fields["{$field}"]) ) {
				unset($data[$field]);
            }

            // RAW DATA 
			else if ( $replaceRaw && is_string($value) &&  preg_match('/^DB\:\:RAW\((.+)\)$/', $value, $match)) { 
				$value = $this->db()->raw($match[1]);
            }
            
            // JSON DATA
            else if ( $fields["{$field}"]["type"] == 'json' && is_array($value) ) {
                $value = json_encode( $value , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            }

            // DATETIME DATA
            else if ( $fields["{$field}"]["type"] == 'datetime' && !empty($value) ) {
                $value = date("Y-m-d H:i:s",strtotime( $value ));
            }
        });

		return $data;
	}

	/**
	 * 唯一ID
	 * @param  [type]  $in      [description]
	 * @param  boolean $to_num  [description]
	 * @param  boolean $pad_up  [description]
	 * @param  [type]  $passKey [description]
	 * @return [type]           [description]
	 */
	private function uniqid( $length = 16 ) {

		$length = ($length < 16) ?  16 : $length;
		$rlen = $length - 3;
		$bytes = (string)hexdec(bin2hex(random_bytes(ceil($rlen/2))));
		$bytes = substr( $bytes, 0, $length);
		$diff = $length - strlen( $bytes );
		for( $i=0; $i< $diff; $i++) {
			$bytes .= "0";
		}
		return $bytes;
    }
    

    /**
     * 返回数据缓存名称
     */
    private function cacheName( $name  ) {
        $table_name  = trim($table_name);
		$table_name = empty($table_name) ? trim($this->table) : $table_name;
        $table_fullname = $this->db()->getTablePrefix().$table_name;
        return "{$table_fullname}:{$name}";
    }

    /**
     * 从缓存中读取数据 
     * @param string $name 缓存名称
     * @param bool $json  是否为Json格式数据
     * @return mix 成功返回缓存中的数据, 失败返回 fasle
     */
    public function getFromCache( $name, $json=true ){

        $cache_name = $this->cacheName( $name );
        
        // 一级缓存
        if ( !empty($this->cacheData[$cache_name]) ) {
            return $this->cacheData[$cache_name];
        }

        // 二级缓存
        if ( $json ) {
            return $this->cache->getJSON( $cache_name );
        }

        return $this->cache->get($cache_name);
    }

    /**
     * 设置数据缓存
     */
    public function setCache( $name, $data, $json=true ) {

        $cache_name = $this->cacheName( $name );
        
        // 一级缓存
        $this->cacheData[$cache_name] = $data;

        // 二级缓存
        if ( $json ) {
            return $this->cache->setJSON( $cache_name, $data );
        }

        return $this->cache->set($cache_name, $data);
    }

}
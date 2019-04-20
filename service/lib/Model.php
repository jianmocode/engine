<?php

namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/data-driver/Data.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;


/**
 * XpmSE数据模型构造器
 */

class Model  {


	/**
	 * 数据驱动名称
	 * @var string
	 */
	private $driver = null;


	/**
	 * 处理类实例
	 * @var data object
	 */
	private $handler = null;


	
	/**
	 * @var 主表名称
	 */
	private $table = null;


	/**
	 * @var Media 对象
	 */
	protected $media = null;

	/**
	 * 构造函数
	 * @param 模型配置 $option
	 */
	function __construct( $option = [], $driver='Database' ) {

		$this->driver = ucfirst( strtolower($driver ));
		$this->table = !empty($option['table']) ?  trim( $option['table'] ) : null;


		$driver_root =  dirname(__FILE__) . '/data-driver' ;
		$class_name =  "\\Xpmse\\DataDriver\\{$this->driver}";

		if ( !file_exists("{$driver_root}/{$this->driver}.php") ) {
			throw new Excp('数据驱动不存在', 404, ['driver'=>$this->driver, 'class_name'=>$class_name, 'option'=>$option]);
        }
        
        // 如果驱动尚未载入，则载入驱动
        if ( !class_exists($class_name) ) {
            require_once( "$driver_root/{$this->driver}.php" );
        }

		if ( !class_exists($class_name) ) {
			throw new Excp('数据驱动不存在', 404, ['driver'=>$this->driver, 'class_name'=>$class_name, 'option'=>$option]);
		}

		// 创建驱动对象
		$this->handler = new $class_name( $option );
	}


	/**
	 * 添加一条记录
	 * @param  array $data 记录数组（需包含所有必填字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function create( $data  ) {
		return $this->handler->create( $data );
	}

	/**
	 * 创建一条数据，如果存在则更新
	 * @param  array      $data        数据集合
	 * @param  array|null $updateColumns 待更新字段清单，为 null 则更新 data 中填写的字段。
	 * @return boolean 成功返回  true, 失败返回false
	 */
	public function createOrUpdate(  $data,  $updateColumns = null ) {
		return $this->handler->createOrUpdate( $data , $updateColumns);
	}


    // 新增数据校验等个函数 1.9.1
    
    /**
     * 过滤数据
     * @param array &$data 待过滤数据清单
     * @param array $allowed 放行的字段清单
     */
    public function fliter( & $data, $allowed=[]) {
    }


    /**
     * 校验数据 
     */
    public function validate( $data ) {
    }


    /**
     * 返回表单的校验格式
     */
    public function validations() {
        return [];
    }


    /**
     * 返回必填字段清单
     * @return array 字段清单
     */
    public function requiredFields() {
        return [];
    }


    /**
     * 返回索引字段
     * @return array 字段清单
     */
    public function indexFields() {
        return [];
    }

    /**
     * 返回唯一索引字段
     * @return array 字段清单
     */
    public function uniqueFields(){
        return [];
    }

    /**
     * 返回唯一索引字段
     * @return array 字段清单
     */
    public function fulltextFields(){
        return [];
    }

	/**
	 * 返回所有字段(不在Model中提供这个接口)
     * @param bool $renew 是否强制更新
	 * @return array 字段清单
	 */
	// public function getFields( $renew = false ) {
	// 	return $this->handler->getFields( $renew );
	// }

    // END 新增数据校验等个函数 1.9.1


	// 新增快捷操作函数 1.6.10

	/**
	 * 保存数据，不存在则创建，存在则更新
	 * @param  array $data Key Value 结构数据
	 * @return array 数据记录或空数组
	 */
	public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {

		return $this->handler->saveBy( $uniqueKey, $data , $keys, $select );
	}


	/**
	 * 根据唯一键, 查找一条数据
	 * @param  string $uniqueKey 唯一键名
	 * @param  mix $value 数值
	 * @return array 数据记录或空数组
	 */
	public function getBy( $uniqueKey, $value,  $select=['*'] ) {
		return $this->handler->getBy( $uniqueKey, $value,  $select );
	}


	/**
	 * 读取传入数据中，第一个uni_key
	 * @param  array $data 数据
	 * @param  array $keys 字段数组
	 * @return null / ["key"=>"...", "value"=>"..."]
	 */
	public function getFirstUniquekey( $data, $keys ) {
		return $this->handler->getFirstUniquekey( $data , $keys);
	}


	/**
	 * 自动生成ID
	 * @return string 唯一ID
	 */
	public function genId() {
		return $this->handler->genId();
	}


	/**
	 * 删除数据表 ( 同 droptable 
	 * @return [type] [description]
	 */
	public function __clear() {
		return $this->handler->__clear();
	}


	/**
	 * 添加默认数据 ( + version: 1.16.20 )
	 * @return [type] [description]
	 */
	public function __defaults() {
		return $this->handler->__defaults();
	}

	
	/**
	 * 处理文件数据 ( + version: 1.16.21 )
	 * @param  array &$rs 数据记录引用
	 * @param  array $fields 文件字段列表
	 * @param  Meida $media Media 对象
	 * @return 
	 */
	public function __fileFields( &$rs, $fields, $media=null ){

		if ( $media == null ) {
			$media = $this->media;
		}
		return $this->handler->__fileFields( $rs, $fields, $media );
	}



	/**
	 * 清空表中数据
	 * @return [type] [description]
	 */
	public function truncate(){
		return $this->handler->truncate();
	}


	// END 新增快捷操作函数 1.6.10



	/**
	 * 根据数据表主键，修改数据记录
	 * @param  array $data 记录数组（需修改的字段 map）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function update( $_id, $data ) {
		return $this->handler->update( $_id, $data );
	}



	/**
	 * 根据指定唯一索引，修改数据记录
	 * @param  string $uni_key 唯一索引名称
	 * @param  array  $data    记录数组（ 需包含 uni_key 字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	function updateBy( $uni_key, $data ) {
		return $this->handler->updateBy( $uni_key, $data  );
	}


	/**
	 * 根据数据表主键，删除数据记录
	 * @param  int  $_id      数据表主键
	 * @param  boolean $mark_only 是否为标记删除， 默认 fasle	
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	function delete( $_id, $mark_only=false ) {
		return $this->handler->delete( $_id, $mark_only  );
	}


	/**
	 * 根据数据表唯一索引数值，删除数据记录
	 * @param  mix   $data_key  唯一索引数值
	 * @param  string  $uni_key   唯一索引键名，默认 "_id"	
	 * @param  boolean $mark_only 是否为标记删除， 默认 true 
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){
		return $this->handler->remove( $data_key, $uni_key, $mark_only  );
	}


	/**
	 * 查询数据表, 返回结果集
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	function select( $query="", $fields=[], $data=[]) {
		return $this->handler->select( $query, $fields, $data );
	}

	/**
	 * 查询数据表, 仅返回结果集
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	function getData( $query="", $fields=[], $data=[]) {
		
		return $this->handler->getData( $query, $fields, $data );
	}


	/**
	 * 查询数据表，返回最后一行数据
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	function getLine( $query="", $fields=[], $data=[]) {
		return $this->handler->getLine( $query, $fields, $data );
	}


	/**
	 * 查询数据表，返回一行中指定字段的数值
	 * @param  string  $field_name 字段名称
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	function getVar( $field_name, $query="" , $data=[]) {
		return $this->handler->getVar( $field_name, $query, $data );
	}


	/**
	 * 读取查询构造器
	 * @see https://laravel.com/docs/5.3/queries
	 * @see https://laravel.com/docs/5.3/pagination
	 * @see https://github.com/illuminate/database/blob/master/Query/Builder.php
	 * @return \Illuminate\Database\Query\Builder 对象
	 */
	function query( $db="read", $include_removed=false ) {
		return $this->handler->query($db, $include_removed);
	}


	/**
	 * 运行 SQL
	 * @param  string $sql SQL语句
	 * @param  bool $return 是否返回结果
	 * @return mix $return = false， 成功返回 true, 失败返回 false; $return = true , 返回运行结果
	 */
	function runsql( $sql, $return=false , $data=[]) {
		return $this->handler->runsql( $sql, $return, $data );
	}


	function nextid() { 
		return $this->handler->nextid();
	}


	function get( $_id ) {
		return $this->handler->get( $_id );	
	}

	/**
	 * 返回错误记录栈
	 * @return array 错误栈
	 */
	function getErrors() {
		return $this->handler->getErrors();
	}


	// === 数据表结构完善


	/**
	 * 读取数据表 $column_name 结构
	 * @param  [type] $column_name [description]
	 * @return [Type] Type 结构体
	 */
	public function getColumn( $column_name ) {
		return $this->handler->getColumn( $column_name );
	}

	/**
	 * 读取数据表列结构
	 * @param  [type] $column_name [description]
	 * @return [Type] Type 结构体
	 */
	public function getColumns() {
		return $this->handler->getColumns();
	}


	/**
	 * 为数据表添加一列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type  Type 结构体
	 * @return $this
	 */
	public function addColumn( $column_name, $type ) {
		return $this->handler-> addColumn( $column_name, $type );
	}


	/**
	 * 修改数据表 $column_name 列结构
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        Type 结构体
	 * @return $this
	 */
	public function alterColumn( $column_name, $type ) {
		return $this->handler->alterColumn( $column_name, $type );
	}




	/**
	 * 替换数据表 $column_name 列结构（ 如果列不存在则创建)
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        Type 结构体
	 * @return $this
	 */
	public function putColumn( $column_name, $type ) {
		return $this->handler->putColumn( $column_name, $type );
	}



	/**
	 * 删除数据表 $column_name 列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param boolen $allow_not_exists 数据表是否存在
	 * @return $this
	 */
	public function dropColumn( $column_name, $allow_not_exists=false ){
		return $this->handler->dropColumn( $column_name, $allow_not_exists );
	}

	
	/**
	 * 格式化 Type 结构体
	 * @param  [type] $name   [description]
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public function type( $name, $option=[] )  {
		return $this->handler->type( $name, $option );
	}


	/**
	 * 读取数据表前缀
	 * @return [type] [description]
	 */
	public function getPrefix() {
		return $this->handler->getPrefix();
	}



	/**
	 * 读取数据表名称
	 * @return [type] [description]
	 */
	public function getTable(){
		return $this->handler->getTable();
	}


	/**
	 * 读取数据表索引
	 * @return [type] [description]
	 */
	public function getIndexes(){
		return $this->handler->getIndexes();
	}

	/**
	 * 读取数据表结构信息
	 * @return array []
	 */
	public function getStruct(){
		return $this->handler->getStruct();
	}


	// 数据表操作

	/**
	 * 设定当前数据表名称
	 * 
	 * @param  [type] $table [description]
	 * @return [type]        [description]
	 */
	public function table( $table ) {
		$this->table = $table;
		$this->handler->table( $table );
		return $this;
	}


	/**
	 * 检查数据表是否存在
	 * @return [type] [description]
	 */
	public function tableExists() {
		return $this->handler->tableExists();
	}


	/**
	 * 快速读取数据库连接
	 * 
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public function db( $conn='write' ) {
		return $this->handler->db( $conn );
	}

	/**
	 * 删除数据表
	 * @param  [type] $table [description]
	 * @return [type]        [description]
	 */
	public function dropTable() {
		$this->handler->dropTable();
		return $this;
	}

}

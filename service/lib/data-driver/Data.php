<?php

namespace Xpmse\DataDriver;
require_once( __DIR__ . '/../Inc.php');
require_once( __DIR__ . '/../Conf.php');
require_once( __DIR__ . '/../Err.php');
require_once( __DIR__ . '/../Excp.php');


use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;



/**
 * XpmSE数据服务接口
 */

interface Data {

	/**
	 * 添加一条记录
	 * @param  array $data 记录数组（需包含所有必填字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	public function create( $data  ) ;


	/**
	 * 根据数据表主键，修改数据记录
	 * @param  array $data 记录数组（需修改的字段 map）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	public function update( $_id, $data );



	/**
	 * 根据指定唯一索引，修改数据记录
	 * @param  string $uni_key 唯一索引名称
	 * @param  array  $data    记录数组（ 需包含 uni_key 字段）
	 * @return array | boolean 成功返回新记录数组  失败返回 false
	 */
	public function updateBy( $uni_key, $data ) ;


	/**
	 * 创建一条数据，如果存在则更新
	 * @param  array      $data        数据集合
	 * @param  array|null $updateColumns 待更新字段清单，为 null 则更新 data 中填写的字段。
	 * @return boolean 成功返回  true, 失败返回false
	 */
	public function createOrUpdate(  $data,  $updateColumns = null ); 


	// 新增快捷操作函数 1.6.10
	
	
	/**
	 * 保存数据，不存在则创建，存在则更新
	 * @param  string $uniqueKey 唯一键名称，为空则调用 genId() 生成唯一字符串
	 * @param  array  $data  KV结构数据
	 * @param  array  $keys  唯一键列表
	 * @param  array  $select  返回结果中，列出的字段
	 * @return array KV结构数据记录或空数组
	 */
	public function saveBy($uniqueKey, $data, $keys=['_id'], $select=['*'] );

	/**
	 * 根据唯一键, 查找一条数据
	 * @param  string $uniqueKey 唯一键名
	 * @param  mix $value 数值
	 * @param  array  $select  返回的字段清单，默认返回所有记录
	 * @return array 数据记录或空数组
	 */
	public function getBy( $uniqueKey, $value,  $select=['*'] );


	/**
	 * 读取传入数据中，第一个uni_key
	 * @param  array $data 数据
	 * @param  array $keys 字段数组
	 * @return null / ["key"=>"...", "value"=>"..."]
	 */
	public function getFirstUniquekey( $data, $keys );


	/**
	 * 自动生成ID
	 * @return string 唯一ID
	 */
	public function genId();

	
	/**
	 * 删除数据表 ( 同 dropTable 
	 * @return $this
	 */
	public function __clear();


	/**
	 * 添加默认数据 ( + version: 1.16.20 )
	 * @return [type] [description]
	 */
	public function __defaults();

	
	/**
	 * 处理文件数据 ( + version: 1.16.21 )
	 * @param  array &$rs 数据记录引用
	 * @param  array $fields 文件字段列表
	 * @param  Meida $media Media 对象
	 * @return 
	 */
	public function __fileFields( &$rs, $fields, $media=null );



	/**
	 * 清空表中数据
	 * @return [type] [description]
	 */
	public function truncate();
	

	// END 新增快捷操作函数 1.6.10


	/**
	 * 根据数据表主键，删除数据记录
	 * @param  int  $_id      数据表主键
	 * @param  boolean $mark_only 是否为标记删除， 默认 fasle	
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	public function delete( $_id, $mark_only=false );


	/**
	 * 根据数据表唯一索引数值，删除数据记录
	 * @param  mix   $data_key  唯一索引数值
	 * @param  string  $uni_key   唯一索引键名，默认 "_id"	
	 * @param  boolean $mark_only 是否为标记删除， 默认 true 
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	public function remove( $data_key, $uni_key="_id", $mark_only=true );


	/**
	 * 查询数据表, 返回结果集
	 * @param  string $query  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	public function select( $query="", $fields=[], $data=[] ) ;



	/**
	 * 查询数据表, 仅返回结果集
	 * @param  string $query  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 ["data"=>[...], "total"=>1000]  失败返回 false
	 */
	public function getData( $query="", $fields=[], $data=[] ) ;



	/**
	 * 查询数据表，返回最后一行数据
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @param  array || string  $fields 字段清单， 默认为空数组，返回所有字段
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	public function getLine( $query="", $fields=[], $data=[]) ;


	/**
	 * 查询数据表，返回一行中指定字段的数值
	 * @param  string  $field_name 字段名称
	 * @param  string $where  检索条件, 默认为空, 列出所有记录
	 * @return array | boolean 成功返回符合条件的记录 map, 失败返回 false
	 */
	public function getVar( $field_name, $query="", $data=[] ) ;


	/**
	 * 读取查询构造器
	 * @see https://laravel.com/docs/5.3/queries
	 * @see https://laravel.com/docs/5.3/pagination
	 * @see https://github.com/illuminate/database/blob/master/Query/Builder.php
	 * @return \Illuminate\Database\Query\Builder 对象
	 */
	public function query( $db="read" , $include_removed=false ) ;


	/**
	 * 运行 SQL
	 * @param  string $sql SQL语句
	 * @param  bool $return 是否返回结果
	 * @return mix $return = false， 成功返回 true, 失败返回 false; $return = true , 返回运行结果
	 */
	public function runsql( $sql, $return=false, $data=[] );


	/**
	 * 读取错误记录栈
	 * @return array 错误栈
	 */
	public function getErrors() ;


	/**
	 * 读取下一个自增 ID
	 * @return string id
	 */
	public function nextid();


	/**
	 * 根据数据表 ID 读取一条记录
	 * @param  [type] $_id [description]
	 * @return [type]      [description]
	 */
	public function get( $_id );



	// ==== 以下为数据表结构创建和修改
	
	/**
	 * 读取数据表 $column_name 结构
	 * @param  [type] $column_name [description]
	 * @return [Type] Type 结构体
	 */
	public function getColumn( $column_name );

	
	/**
	 * 读取数据表列结构
	 * @param  [type] $column_name [description]
	 * @return [Type] Type 结构体
	 */
	public function getColumns();


	/**
	 * 为数据表添加一列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type  Type 结构体
	 * @return $this
	 */
	public function addColumn( $column_name, $type );


	/**
	 * 修改数据表 $column_name 列结构
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        Type 结构体
	 * @return $this
	 */
	public function alterColumn( $column_name, $type );




	/**
	 * 替换数据表 $column_name 列结构（ 如果列不存在则创建)
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param Type   $type        Type 结构体
	 * @return $this
	 */
	public function putColumn( $column_name, $type );



	/**
	 * 删除数据表 $column_name 列
	 * @param String $column_name 列名称 (由字符、数字和下划线组成，且开头必须为字符)
	 * @param boolen $allow_not_exists 数据表是否存在
	 * @return $this
	 */
	public function dropColumn( $column_name, $allow_not_exists=false );


	/**
	 * 读取数据表前缀
	 * @return string prefix
	 */
	public function getPrefix();


	/**
	 * 读取数据表名称
	 * @return [type] [description]
	 */
	public function getTable();


	/**
	 * 读取数据表索引
	 * @return [type] [description]
	 */
	public function getIndexes();

	/**
	 * 读取数据表结构信息
	 * @return array []
	 */
    public function getStruct();
    

    /**
     * 快速查询数据表字段 
     * @param bool $renew 是否更新缓存, 默认为false 不更新
     * @return array $fields 数据表结构映射
     */
    public function getFields( $renew=false );
	

	/**
	 * 格式化 Type 结构体
	 * @param  [type] $name   [description]
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	public function type( $name, $option=[] );


	/**
	 * 设定当前数据表名称
	 * 
	 * @param  [type] $table [description]
	 * @return [type]        [description]
	 */
	public function table( $table );


	/**
	 * 检查数据表是否存在
	 * @return [type] [description]
	 */
	public function tableExists();


	/**
	 * 删除数据表
	 * @return [type] [description]
	 */
	public function dropTable();
	

	/**
	 * 快速读取数据库连接
	 * 
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public function db( $conn='write' );

}
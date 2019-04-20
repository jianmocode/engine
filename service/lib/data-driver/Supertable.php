<?php
namespace Xpmse\DataDriver;
require_once('lib/Inc.php');
require_once('lib/Conf.php');
require_once('lib/Err.php');
require_once('lib/Excp.php');
require_once('lib/data-driver/Data.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;

use \Xpmse\Supertable\Table as Table;
use \Xpmse\DataDriver\Data as Data;


/**
 * XpmSE数据库服务
 */
class Supertable extends Table implements Data {
	
	private $_tab_conf = [];
	private $_tab_init_flagkey = null;
	private $_tab_mem = null;

	function __construct( $table, $sheet=null, $initcall=null, $coreonly=false ) {

		$prefix = "core";
		$table = strtolower($table);
		if (!empty($sheet)) {
			$sheet = strtolower($sheet);
		}
		
		$path_info = dirname($_SERVER['SCRIPT_FILENAME']);

		if ( strpos($path_info, _XPMAPP_ROOT) !== false ) {
			$path =  str_replace(_XPMAPP_ROOT . '/', '',  $path_info);	
			$info = explode('/', $path);
			$prefix = strtolower($info[0]);  // APP NAME
		}

		// Share
		if ( $coreonly === true ) {
			$prefix = 'core';
		}


		$conf = Conf::G('supertable');
		if ( $conf['storage']['prefix'] == '{auto}') {
			$conf['storage']['prefix'] = "sp_{$prefix}_";
		}


		// 是否支持多个索引，默认支持
		$signleindex = Conf::G('supertable/search/signleindex');
		// 不支持多个索引
		if ( $signleindex === true ) {

			$index_name = Conf::G('supertable/search/index');
			if ( empty($index_name) ) {
				$index_name = 'xpmse';
			}
			
			$signlesheet = Conf::G('supertable/search/signlesheet');
			$name = $sheet;
			$sheet = ($signlesheet === true ) ?  $prefix . '_' . $table : $prefix . '_' . $name  .  '_' . $table;
			$table = $index_name;

			if ( $conf['storage']['prefix'] == "sp_{$prefix}_") {
				$conf['storage']['prefix'] = "sp_";
			}
		}

		$this->_tab_conf = $conf;

		try {
			parent::__construct($conf);

			// 绑定数据表
			$this->bindBucket( [
						'data'=> strtolower($table),
						'schema' => strtolower($table . "_sch"),
					])
				->bindIndex()
				->init();

			if (!empty($sheet) ) { 
				$this->selectSheet( strtolower($sheet) );
			}

		} catch( Exception $e ) {
			Excp::elog( $e );
			throw $e;
		}


		// 是否需要初始化
		$this->_tab_mem = $mem = new Mem( true, 'SuperTable:' ); // 
		if (!empty($sheet) ) {
			$flagkey = "init:{$conf['storage']['prefix']}{$table}:{$sheet}";
		} else {
			$flagkey = "init:{$conf['storage']['prefix']}{$table}";
		}

		$this->_tab_init_flagkey = $flagkey;
		$flag = $mem->get($flagkey);
		if ( $flag === false ) {
			
			$complete = true;
			$this->__schema();
			if ( is_callable($initcall) ) {
				try {
					$initcall( $this );
				} catch( Exception $e ) {
					Excp::elog( $e );
					throw $e;
				}
			}
			$mem->set($flagkey, date(DATE_ATOM));
		}
	}


	/**
	 * 数据表构造函数
	 */
	function __schema() {
		return;
	}


	/**
	 * 删除数据表
	 * @return [type] [description]
	 */
	function __destory(  $mark_only = true ){
		
		$this->clearCache(); // 清空初始化缓存
		return $this->deleteSheet( $mark_only );
	}



	/**
	 * 更新数据表
	 * @param  array  $new_schema 新数据结构
	 * @param  boolean $dropcolumn 是否删除旧字段
	 * @return 成功返回本对象, 失败抛出异常
	 */
	function __updateSchema( $new_schema, $dropcolumn=true ) {
		try {
			$sheet = $this->sheet();
			$old_column =  isset($sheet['columns'])? $sheet['columns'] : [];
			$old_fields = []; $new_fields=[];

			// 更新字段 
			foreach ($new_schema as $idx => $newsch) {
				array_push( $new_fields, $newsch['field'] );
				$this->putColumn($newsch['field'], $this->type($newsch['type'], $newsch['option'] ));
			}

			if ( $dropcolumn === true ) {
				foreach ($old_column  as $field => $type) {
					array_push( $old_fields, $field );
				}

				// 删除字段
				$minus_fields =  array_diff($old_fields, $new_fields );
				foreach ($minus_fields as $field ) {
					$this->dropColumn( $field, true );
				}
			}

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}

		return $this;
	}


	/**
	 * 读取XpmSE配置信息
	 * @return [type] [description]
	 */
	function config() {
		return $this->_tab_conf;
	}


	/**
	 * 读取数据表配置
	 */
	function __table() {
		return array_merge($this->_bucket, $this->_index);
	}

	
	/**
	 * 重载 select 方法，增加错误处理
	 * @param  string $where  [description]
	 * @param  array  $fields [description]
	 * @return [type]         [description]
	 */
	function select( $where="", $fields=array() ) {

		try {
			$resp = parent::select( $where, $fields );
		}catch( Exception $e ) {
			Excp::elog( $e );
			$resp = ['data'=>[], 'total'=>0];
		}
		return $resp;
	}


	/**
	 * 清空数据缓存
	 * @return [type] [description]
	 */
	function clearCache() {

		if ( $this->_tab_mem != null  && $this->_tab_init_flagkey != null ) {
			return $this->_tab_mem->del( $this->_tab_init_flagkey );
		}

		return true;
	}

}
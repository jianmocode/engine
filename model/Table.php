<?php
namespace Xpmse\Model;

/**
 * 
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Table
 *
 * USEAGE: 
 *
 */


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;


class Table extends Model {  // ( 即将废弃 )


	function __construct( $table_name, $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
		$this->table( $table_name );
	}


	/**
	 * 删除表
	 * @return [type] [description]
	 */
	function __clear(){
		$this->dropTable();
	}

	/**
	 * 创建数据表结构
	 * @return [type] [description]
	 */
	function __schema( $fields ) {

		$result = true;
		$errorFields = [];
		$fields = ( !is_array($fields) ) ? []  : $fields; 
		$allowTypes = ['string', 'integer', 'text', 'boolean'];
		
		try {

			foreach ($fields as $idx=>$f ) {

				if ( empty($f['name']) ) {
					$result = false;
					array_push( $errorFields, ['message'=>'field name is null', 'code'=>404, 'field'=> $f, 'index'=>$idx] );
				}

				if ( !in_array($f['type'], $allowTypes) ) {
					$result = false;
					array_push( $errorFields, ['message'=>'field type is now allowed', 'code'=>403, 'field'=> $f, 'index'=>$idx] );
				}

				$f['option'] = ( !is_array( $f['option'])) ? [] : $f['option'];

				if ( $result === true ) { // 数据校验通过，创建数据表
					$this->putColumn( $f['name'], $this->type($f['type'], $f['option'] ) );
				}
			}

			if ( $result == false) {
				throw new Excp('schema struct error', 400, $errorFields );
			}


		} catch( Exception $e ) {
			Excp::elog($e);
			throw new Excp($e->getMessage(), 500, $errorFields );
		}

		return true;
	}


	
}


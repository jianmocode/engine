<?php
namespace Xpmse\Model;

/**
 * 
 * 机构模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Router
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


class Domain extends Model {

	/**
	 * 页面数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
		$this->table('domain');
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// Domain 名称
			$this->putColumn( 'project_id', $this->type('string', [ "null"=>false,  'length'=>128] ) )

			// ssl
			->putColumn( 'ssl_cert', $this->type('text', [ "null"=>true] ) )

			// ssl
			->putColumn( 'ssl_key', $this->type('text', ["null"=>true] ) )

			// domain
			->putColumn( 'domain', $this->type('string', ["length"=>'128',"null"=>true,]) ) 
			
			// instance 
			->putColumn( 'instance', $this->type('string', [ "null"=>true, 'length'=>128] ) )

			// name
			->putColumn( 'name', $this->type('string', [ "null"=>true, 'length'=>128] ) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


}
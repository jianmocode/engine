<?php
namespace Xpmse\Model;

/**
 * 
 * 页面项目模型
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


class Project extends Model {

	/**
	 * 页面数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
		$this->table('project');
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// Project 名称
			$this->putColumn( 'name', $this->type('string', [ "null"=>true, 'index'=>1, 'length'=>128] ) )

			// 中文名称
			->putColumn( 'cname', $this->type('string', [ "null"=>true,  'length'=>200] ) )

			// 项目简介
			->putColumn( 'intro', $this->type('string', [ "null"=>true, 'length'=>600] ) )

			// 绑定域名(废弃)
			->putColumn( 'domain', $this->type('string', ['index'=>1, 'length'=>128] ) )

			// 优先级排序
			->putColumn( 'priority', $this->type('integer', ['index'=>1, 'default'=>"0"]) ) 

			// 是否为默认项目
			->putColumn( 'default', $this->type('integer', ['index'=>1, 'default'=>"0"]) ) 

			// 项目配置
			->putColumn( 'json', $this->type('longText', [] ) )

			// 项目样式
			->putColumn( 'css', $this->type('longText', [] ) )

			// 项目逻辑
            ->putColumn( 'js', $this->type('longText', [] ) )
            
            // 全局JSON
			->putColumn( 'global', $this->type('longText', [] ) )
			
			//机构字段
            ->putColumn( 'instance', $this->type('string', [ "null"=>false, 'length'=>128] ) )
            
            // 名称+机构唯一
            ->putColumn( 'name_instance', $this->type('string', [ "null"=>false, 'unique'=>1, 'length'=>128] ) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


}
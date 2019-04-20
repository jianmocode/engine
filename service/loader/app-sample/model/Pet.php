<?php
namespace {{org}}\{{name}}\Model; 

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;


class Pet extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>strtolower('{{org}}_{{name}}_')]);
		$this->table('pet');
	}
	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
			
			$this->putColumn( 'name', $this->type('string', ['length'=>20, 'default'=>'world']) )
			     ->putColumn( 'mobile', $this->type('string',  ['length'=>100, 'index'=>1]) )
		;
	}


	function __clear() {
		$this->dropTable();
	}
	

	/**
	 * 创建测试数据
	 * @return [type] [description]
	 */
	function fakerdata(){
		
		$faker = Utils::faker();
		for( $i=0; $i<10; $i++ ) {
			try {
				$this->create([
			        'name'=> $faker->name,
			        'mobile'=> $faker->phoneNumber,
			    ]);
			} catch(Excp $e){}
	    }
	}


}
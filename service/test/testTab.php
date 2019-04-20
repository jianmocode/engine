<?php
require_once('lib/Excp.php');
require_once('lib/Tab.php');

use \Xpmse\Tab as Tab;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;

echo "\nXpmse\Tab 测试... \n\n\t";

class testTab extends PHPUnit_Framework_TestCase {

	function testCreateSheetWayOne() {
		$world = new Tab('Hello', 'world');
		$world->putColumn('creator_id', $world->type('BaseInt', ['screen_name'=>'创建者ID','required'=>1]) );
	}

	function testCreateSheetWayTow() {
		$BaoBao = new Tab('BaoBao', 'world');
		$BaoBao->putColumn('creator_id', $BaoBao->type('BaseInt', ['screen_name'=>'创建者ID','required'=>1]) );
	}

	function testCreateData() {
		$time = time();
		$world = new Tab('Hello', 'world');
		$resp = $world->create(['creator_id'=>$time]);
	}

	function testConfig() {
		$world = new Tab('Hello', 'world');
		$conf = $world->config();
		// print_r($conf);
	}

	function testType() {
		
		$typeTest = new Tab('Hello', 'typetest');
		$typeTest->putColumn('typeofBool', $typeTest->type('BaseBool', ['screen_name'=>'测试Bool字段','required'=>1]) );
		$typeTest->putColumn('typeofFloat',  $typeTest->type('BaseFloat',  ['screen_name'=>'测试Float字段', 'required'=>1]) );
		$typeTest->putColumn('typeofObject',  $typeTest->type('BaseObject',  ['screen_name'=>'测试Object字段', 'required'=>1]) );
		$typeTest->putColumn('typeofDate',  $typeTest->type('BaseDate',  ['screen_name'=>'测试Date字段']) );
		$typeTest->putColumn('typeofArrayObject',  $typeTest->type('BaseArray',  ['screen_name'=>'测试Array字段', 'schema'=>'object']) );
		$typeTest->putColumn('typeofArrayLong',  $typeTest->type('BaseArray',  ['screen_name'=>'测试Array字段', 'schema'=>'long']) );
		$typeTest->putColumn('typeofArrayString',  $typeTest->type('BaseArray',  ['screen_name'=>'测试Array字段', 'schema'=>'string']) );
		$typeTest->putColumn('typeofStringOne',  $typeTest->type('BaseString',  ['screen_name'=>'测试String1字段', 'default'=>'HELLO']) );
		$typeTest->putColumn('typeofStringTwo',  $typeTest->type('BaseString',  ['screen_name'=>'测试String2字段', 'default'=>""]) );
		$typeTest->putColumn('typeofStringThree',  $typeTest->type('BaseString',  ['screen_name'=>'测试String3字段']) );

		try{
			$ret = $typeTest->create([
				'typeofBool' => false,
				'typeofFloat' => 1.236,
				'typeofObject' => ['lalal'=>'allal','dkkma'=>'mmakd'],
				'typeofDate' => '2018-02-18 20:00:00',
				'typeofArrayObject' => [['a'=>1],['b'=>2]],
				'typeofArrayLong' => [1,3,2,4,5],
				'typeofArrayString' => ["HELLO","world", "BAOBAO","3"],
			]);

		} catch( Exception $e )  {
			print_r($e);
			exit;
		}

		if ( $ret === false ){
			print_r($typeTest->errors );
		}


		 $resp = $typeTest->select('');

		/*
		$ret = $typeTest->create([
			'typeofBool' => true,
			'typeofFloat' => 1.236,
			'typeofObject' => ['mvp'=>'dhall','mb'=>'ok']
		]); 


		

		$typeTest->create([
			'typeofBool' => true,
			'typeofFloat' => 1.236,
			'typeofObject' => ['lalal'=>'allal','dkkma'=>'mmakd', 'mamaj'=>[1,2,3,4] ]
		]);

		

		*/
	}
}
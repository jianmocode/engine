<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/Object.php" );
require_once(__DIR__ . "/../src/Base.php" );
require_once(__DIR__ . "/../src/Apcu.php" );

use Mina\Cache\Apcu as Cache;

class ApcuTest extends PHPUnit_Framework_TestCase {

	/**
	 * 测试数据连接
	 * @return [type] [description]
	 */
	public function testConnect() {

		echo "\t 测试服务器连接....";
		$m = new Cache();
		$this->assertEquals( $m->ping(), '+PONG');
		echo " [完成]\n";
	}


	// 测试String 增、删、改、查
	// public function testSetGetDel() {

	// 	echo "\t 测试Get, Set, Del 方法....";
	// 	$m = new Cache([
	// 			'host'=>'172.17.0.1',
	// 			'port'=>6379
	// 		]);

	// 	#SET 
	// 	$this->assertEquals( $m->set('r1', 'Hi R1'), true );
	// 	$this->assertEquals( $m->set('r2', 'Hi R2 EXP 2SEC', 1), true );
	// 	$this->assertEquals( $m->set('r3', 'Hi R3'), true );

	// 	#GET 
	// 	$this->assertEquals( $m->get('r1'),  'Hi R1');
	// 	$this->assertEquals( $m->get('r2'),  'Hi R2 EXP 2SEC');
	// 	$this->assertEquals( $m->get('r3'),  'Hi R3');
		
	// 	#GET DELAY
	// 	sleep(2);
	// 	$this->assertEquals( $m->get('r2'),  false);

	// 	#del
	// 	$this->assertEquals( $m->del('r1'),  true);
	// 	$this->assertEquals( $m->del('r2'),  false);
	// 	$this->assertEquals( $m->del('r3', 1), true);

	// 	#DEL CHECK
	// 	sleep(2);
	// 	$this->assertEquals( $m->get('r3'),  false);
	// 	echo " [完成]\n";

	// }

}
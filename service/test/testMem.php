<?php
require_once('lib/Mem.php');

echo "\nXpmse\Mem 测试... \n\n\t";

class testMem extends PHPUnit_Framework_TestCase {

	/**
	 * 测试数据连接
	 * @return [type] [description]
	 */
	public function testConnect() {

		echo "\t 测试服务器连接....";
		$m1 = new Xpmse\Mem();
		$m2 = new Xpmse\Mem([
				'host'=>'127.0.0.1',
				'port'=>'10086'
			]);
		$m3 = new Xpmse\Mem([
				'host'=>'127.0.0.1',
				'port'=>'6379'
			]);
		$this->assertEquals( $m1->ping(), '+PONG');
		$this->assertEquals( $m2->ping(), false);
		$this->assertEquals( $m3->ping(), '+PONG');
		echo " [完成]\n";
	}


	// 测试String 增、删、改、查
	public function testSetGetDel() {

		echo "\t 测试Get, Set, Del 方法....";
		$m = new Xpmse\Mem();

		#SET 
		$this->assertEquals( $m->set('r1', 'Hi R1'), true );
		$this->assertEquals( $m->set('r2', 'Hi R2 EXP 2SEC', 1), true );
		$this->assertEquals( $m->set('r3', 'Hi R3'), true );

		#GET 
		$this->assertEquals( $m->get('r1'),  'Hi R1');
		$this->assertEquals( $m->get('r2'),  'Hi R2 EXP 2SEC');
		$this->assertEquals( $m->get('r3'),  'Hi R3');
		
		#GET DELAY
		sleep(2);
		$this->assertEquals( $m->get('r2'),  false);

		#del
		$this->assertEquals( $m->del('r1'),  true);
		$this->assertEquals( $m->del('r2'),  false);
		$this->assertEquals( $m->del('r3', 1), true);

		#DEL CHECK
		sleep(2);
		$this->assertEquals( $m->get('r3'),  false);
		echo " [完成]\n";

	}

}
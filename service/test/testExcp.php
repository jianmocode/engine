<?php
require_once('lib/Excp.php');

echo "\nXpmse\Log 测试... \n\n\t";

class testExcp extends PHPUnit_Framework_TestCase {

	function testNew() {
		$e = new \Xpmse\Excp('TestNew 异常测试');
		$e->log();

	}

	function test2(){
		$err = new \Xpmse\Err(10010, 'Test2 异常测试', ['HELLO'=>"OPTH"]);
		$e = new \Xpmse\Excp($err);
		$e->log();
	}

	
}
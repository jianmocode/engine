<?php
require_once('lib/Conf.php');

echo "\nXpmse\Conf 测试... \n\n\t";

class testConf extends PHPUnit_Framework_TestCase {


	function testRenew() {
		$c = new \Xpmse\Conf;
		$resp = $c->renew();
		$this->assertEquals( is_array($resp),  true);
	}
	

	function testGetOK() {
		$c = new \Xpmse\Conf;
		$local = $c->get("storage/local");
		$this->assertEquals( is_array($local),  true);
	}

	function testGOK() {
		$local = \Xpmse\Conf::G("storage/local");
		$this->assertEquals( is_array($local),  true);
	}


	function testGetFail(){
		$c = new \Xpmse\Conf;
		$donotexist = $c->get("storage/donotexist");
		$this->assertEquals( empty($donotexist),  true);
	}

	function testGFail(){
		$donotexist = \Xpmse\Conf::G("storage/donotexist");
		$this->assertEquals( empty($donotexist),  true);
	}
}
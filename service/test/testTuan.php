<?php
require_once('lib/Excp.php');
require_once('lib/Stor.php');
require_once('lib/Tuan.php');

use \Xpmse\Stor as Stor;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Tuan as Tuan;

echo "\nXpmse\Tuan 测试... \n\n\t";

class testTuan extends PHPUnit_Framework_TestCase {

	private $photo = "http://h.hiphotos.baidu.com/image/pic/item/4ec2d5628535e5dd2820232370c6a7efce1b623a.jpg";

	function testGetAccessToken() {
		$tuan = new Tuan;
		try{
			$resp = $tuan->getAccessToken();
		} catch(Excp $e ) {

			print_r($e);
			die();
			$e->log();
		}

		echo "\TOKEN=$resp\n";
		$this->assertEquals( is_string($resp), true );
	}

	function testCall(){
		$tuan = new Tuan;
		$resp = $tuan->call('/dept/get',['hi'=>'qhi'], ['hello'=>'dhello']);
		print_r($resp);
	// 	$this->assertEquals( $resp['hello'], 'dhello' );

	}


	function testApps(){
		$tuan = new Tuan;
		$resp = $tuan->call('/apps/helloworld/world/get',['hi'=>'qhi'], ['hello'=>'dhello']);

		print_r($resp);
	}

}


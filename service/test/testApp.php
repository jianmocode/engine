<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;
use \Xpmse\App;


echo "\nXpmse\App 测试... \n\n\t";

class testApp extends PHPUnit_Framework_TestCase {

	function testApi() {
		$account = new App([
			"url"=>"https://wss.xpmjs.com/_api/kexinyun/partners/account",
			"appid" => "150698766059529",
			"secret" => "4990e4107dbfe85c045cf8bbd3508652",
			"debug" => false
		]);

		$resp = $account->create( [], [
			"mobile" => '13436431859',
			"name" => "王伟平",
			"manu_id"=> '150920102484275',
			"company" => "北京云道天成科技有限公司",
			"password" => '$2y$12$/s.1Zrk8Uf1s41D4VjZXCe988.b5dLsL44j2MOFMwJBlU8aLaZYaq'
		]);


		Utils::out($resp);
	}

}
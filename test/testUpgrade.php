<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;

echo "\n \Xpmse\Model\App 单元测试... \n\n\t";

class testUpgrade extends PHPUnit_Framework_TestCase {

	function testOK() {
		$up = M("Upgrade");
		$latest = $up->checkNewVersion();
		Utils::out($latest, "\n");

		$resp = $up->checkLicense();
		var_dump($resp);
	}


}


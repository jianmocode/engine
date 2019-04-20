<?php
require_once('lib/Log.php');

echo "\nXpmse\Log 测试... \n\n\t";

class testLog extends PHPUnit_Framework_TestCase {

	function testLoad() {
		$log = new Xpmse\Log("TESTLOG");
		$log->error("JUST error",['BUG'=>'HELLO']);
		$log->info("JUST info",['HELLO'=>'WORLD']);
		/*
		$log->info("JUST info");
		$log->debug("JUST debug");
		$log->notice("JUST notice");
		$log->warning("JUST warning");
		$log->critical('JUST critical');
		$log->alert('JUST alert');
		$log->emergency('JUST emergency',['BUG'=>'HELLO']);
		*/
	}

}
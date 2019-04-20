<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;

echo "\n \Xpmse\Model\App 单元测试... \n\n\t";

class testPage extends PHPUnit_Framework_TestCase {

	function testCreate() {

		$page = M('Page');
		// $page->import( __DIR__. '/assets/web.zip' );

		// $page->build();		


		$page->__schema();
		
		// $page->save(['name'=>"HELLOW Baobao"] );
		
	}

}


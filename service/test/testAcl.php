<?php
require_once(__DIR__ . '/../lib/Acl.php');

use \Xpmse\Acl as Acl;

echo "\nXpmse\Acl 测试... \n\n\t";

class testAcl extends PHPUnit_Framework_TestCase {


	function testSchema() {	

		$acl = new Acl;
		try {$acl->dropTable();}  catch( Exception $e ) {} 
		$acl->__schema();

	}
	
}
<?php
require_once( "env.inc.php");
require_once( AROOT  . DS . 'model'. DS .'Department.php');
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

echo "\n \Xpmse\Model\Department 单元测试... \n\n\t";

class testDepartment extends PHPUnit_Framework_TestCase {

	function testOK() {
		
		$dept = M("Department");
		
		// try {$dept->dropTable();}  catch( Exception $e ) {} 
		// $dept->__schema();
		
		// $resp = $dept->create([
		// 	"id"=>$dept->nextid(),
		// 	"name"=>"北京云道天成科技有限公司",
		// ]);

		$resp = $dept->runsql("select * from core_department ", true );
		print_r($resp );

	}
}


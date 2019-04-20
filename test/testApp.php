<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;

echo "\n \Xpmse\Model\App 单元测试... \n\n\t";

class testApp extends PHPUnit_Framework_TestCase {

	function testOK() {
		
		$app = M("App");
		// try {$app->dropTable();}  catch( Exception $e ) {} 
		// $app->__schema();
		
		// $a = $user->genAvatar('王伟平');
		// $user->create([
		// 	'userid'=>'9527',
		// 	'name'=>'王伟平',
		// 	"mobile"=>"13436431858",
		// 	"password"=>$user->hashPassowrd("12345678"),
		// 	'department'=>[1,4],
		// 	'orderInDepts'=>["1"=>10, "4"=>1],
		// 	"avatar" => $a['avatar'],
		// 	'deptManagerUseridList'=>["3"=>false, "4"=>true]
		// ]);

		// $resp = $user->select("where department like ?", ["*"], ['%1%']);
		
		$resp = $app->getInstalled( true );
		
		Utils::out( $resp);
	}
}


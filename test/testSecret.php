<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;

echo "\n \Xpmse\Model\Secret 单元测试... \n\n\t";

class testSecret extends PHPUnit_Framework_TestCase {

	function testOK() {

		$se = M("Secret");
		// try {$se->dropTable();}  catch( Exception $e ) {} 
		// $resp = $se->__schema();

		$resp = $se->create($se->genKeyPair());

		utils::out( $resp );

		
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
		
		
	}
}


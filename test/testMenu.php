<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Model.php');
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;
use \Xpmse\Model as Model;

echo "\n \Xpmse\Model\Menu 单元测试... \n\n\t";

class testMenu extends PHPUnit_Framework_TestCase {

	function testOK() {

		$m = M('Menu', ['isAdmin'=>1]);
		$menu = $m->active('s')->get();
		// Utils::out( $menu );
		
		// $m = M("Menu");
		// // try {$app->dropTable();}  catch( Exception $e ) {} 
		// // $app->__schema();
		
		// // $a = $user->genAvatar('王伟平');
		// // $user->create([
		// // 	'userid'=>'9527',
		// // 	'name'=>'王伟平',
		// // 	"mobile"=>"13436431858",
		// // 	"password"=>$user->hashPassowrd("12345678"),
		// // 	'department'=>[1,4],
		// // 	'orderInDepts'=>["1"=>10, "4"=>1],
		// // 	"avatar" => $a['avatar'],
		// // 	'deptManagerUseridList'=>["3"=>false, "4"=>true]
		// // ]);

		// // $resp = $user->select("where department like ?", ["*"], ['%1%']);
		
		// $resp = $m->getAll();
		
		// Utils::out( $resp);
	}
}


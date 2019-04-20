<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Api 测试... \n\n\t";

class testApi extends PHPUnit_Framework_TestCase {


	function testMina() {

		return true;
		// $api = new \Mina\Pages\Api\Article;
		// try {
		// 	$resp = $api->call('get',['article_id'=>31, "select"=>"title,tag,article_id,category"]);
		// }catch( Excp $e) {
		// 	Utils::out( $e->toArray());
		// }

		// Utils::out( $resp );
	}
	
}
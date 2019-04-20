<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/Node.php" );
require_once(__DIR__ . "/../src/Html.php" );
require_once(__DIR__ . "/../src/Delta.php" );
require_once(__DIR__ . "/../src/Wxapp.php" );
require_once(__DIR__ . "/../src/Utils.php" );
require_once(__DIR__ . "/../src/Render.php" );
require_once(__DIR__ . "/../../../service/lib/Model.php" );
require_once(__DIR__ . "/../../../service/lib/Utils.php" );

use Mina\Delta\Node;
use Mina\Delta\Html;
use Mina\Delta\Render as Render;
use Xpmse\Utils;

$delta_text = file_get_contents(__DIR__ . '/assets/d4.json');
$html_text =  file_get_contents(__DIR__ . '/assets/c3.html');
// $delta = json_decode($delta_text);

class RenderTest extends PHPUnit_Framework_TestCase {


	public function testWxapp() {
		global $html_text;
		$render = new Render();
		$wxapp = $render->loadByHTML($html_text)->wxapp();
		$images = $render->images();
		print_r($wxapp);
	}


	public function testDelta() {
		global $html_text;
		$render = new Render();
		$delta = $render->loadByHTML($html_text)->delta();
		// print_r($delta);

	}

	/**
	 * 测试数据连接
	 * @return [type] [description]
	 */
	public function testHtml() {

		// global $delta_text;
		// $render = new Render();
		// $html = $render->load( $delta_text )->html();
		// // echo $html;
		// print_r( $render->images() );
		// print_r( $render->videos() );
		// print_r( $render->files() );

		// // print_r( \Mina\Delta\_FORMAT );

		// $utils = new \Mina\Delta\Utils;
		// $html = $utils->load( $delta_text )->convert()->render();
		// echo $html;

		// $utils->toHtml( $delta_text );
		
		// $utils->load($delta_text)->each(function( $type, $text, $attrs, $index ) use ($utils) {
		// 	print_r( $attrs);
		// });
	}
}
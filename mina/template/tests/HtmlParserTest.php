<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/Helper.php" );
require_once(__DIR__ . "/../src/HtmlParser.php" );

use Mina\Template\HtmlParser; 

class HtmlParserTest extends PHPUnit_Framework_TestCase {

	function testToPHP() {

		$hp = new HtmlParser();
		$phpcode = $hp->load(__DIR__ . "/assets/detail.page")
				   ->insertAssets(["test.css", "test.js"])
				   ->insertScript("
				   		var foo = 'bar';
				   		console.log( foo );
				   	")
				   ->insertWaterMarker()
				   ->toPHP();

        $pos = strpos($phpcode,'var foo = \'bar\';' );  // 4677
        // print_r( $phpcode );
		$this->assertEquals( $pos, 6568 );
    }
    
    function testToPHP2() {

		$hp = new HtmlParser();
		$phpcode = $hp->load(__DIR__ . "/assets/detail2.page")
				   ->insertAssets(["test.css", "test.js"])
				   ->insertScript("
				   		var foo = 'bar';
				   		console.log( foo );
				   	")
				   ->insertWaterMarker()
				   ->toPHP();

        $pos = strpos($phpcode,'var foo = \'bar\';' );  // 4677
        // print_r( $phpcode );
		$this->assertEquals( $pos, 6492 );
	}
}

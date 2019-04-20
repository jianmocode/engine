<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/Helper.php" );
require_once(__DIR__ . "/../src/XmlParser.php" );

use Mina\Template\XmlParser; 

class HtmlParserTest extends PHPUnit_Framework_TestCase {

	function testToPHP() {

		$hp = new XmlParser();
		$phpcode = $hp->load(__DIR__ . "/assets/xml.page")
				   ->insertAssets(["test.css", "test.js"])
				   ->insertScript("
				   		var foo = 'bar';
				   		console.log( foo );
				   	")
				   ->insertWaterMarker()
				   ->toPHP();

		echo $phpcode;

		// $pos = strpos($phpcode,'var foo = \'bar\';' );  // 4677
		// $this->assertEquals( $pos, 6564 );
	}
}

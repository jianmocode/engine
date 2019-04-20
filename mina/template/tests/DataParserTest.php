<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/Helper.php" );
require_once(__DIR__ . "/../src/DataCompiler.php" );
require_once(__DIR__ . "/../src/DataParser.php" );

use Mina\Template\DataParser; 
use Mina\Template\DataCompiler;

class MyDataCompiler extends DataCompiler {
	
	function __construct() {
		parent::__construct(["home"=>"http://apps.minapages.com/1"]);
	}

	function compile( $json_data ) {
		return '
			$data = ["bar"=>"foo"];
		';
	}
}


class DataParserTest extends PHPUnit_Framework_TestCase {

	function testToPHP() {
		
		$dp = new DataParser( ["domain"=>"minapages.com"] );
		$phpcode = $dp->load(__DIR__ . "/assets/detail.json")
				      ->toPHP();

		$pos = strpos($phpcode,'$data = ["foo"=>"bar"];' );  // 1
		$options =  $dp->compiler()->getOptions(); // ["domain"=>"minapages.com"]
		$this->assertEquals( $pos, 1 );
		$this->assertEquals( $options, ["domain"=>"minapages.com"] );
	}

	function testToPHPCustom() {

		$dp = new DataParser( ["domain"=>"minapages.com"] );
		$compiler = new MyDataCompiler();
		$phpcode = $dp->load(__DIR__ . "/assets/detail.json")
				      ->toPHP( $compiler );
		$pos = strpos($phpcode,'$data = ["bar"=>"foo"];' );  // 4
		$options =  $dp->compiler()->getOptions(); // ["domain"=>"minapages.com", "home"=>"http://apps.minapages.com/1"] 

		$this->assertEquals( $pos, 4 );
		$this->assertEquals( $options, ["domain"=>"minapages.com", "home"=>"http://apps.minapages.com/1"] );
	}

}
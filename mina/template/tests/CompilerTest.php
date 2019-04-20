<?php
require_once(__DIR__ . "/../vendor/autoload.php" );
require_once(__DIR__ . "/../src/HtmlParser.php" );
require_once(__DIR__ . "/../src/DataParser.php" );
require_once(__DIR__ . "/../src/DataCompiler.php" );
require_once(__DIR__ . "/../src/Helper.php" );
require_once(__DIR__ . "/../src/Compiler.php" );

use Mina\Template\Compiler; 
use Mina\Template\DataCompiler;

class MyDataCompiler extends DataCompiler {
	
 	function __construct() {
	 	parent::__construct(["home"=>"http://apps.minapages.com/1"]);
   	}

  	function compile( $json_data ) {
	 	return "\t" .'$data = ["foo"=>"bar"];' . "\n\t@extract(\$data);" ;
  	}
}

class CompilerTest extends PHPUnit_Framework_TestCase {

	function testToPHP() {

		$compiler = new MyDataCompiler();
		$cp = new Compiler([
				"script" =>['console.log("page loaded");'],
				"assets" => ["//sdk.minapages.com/1.0/mina.css", "//your.cdn.url/web.css", "//sdk.minapages.com/1.0/mina.js", "//your.cdn.url/web.js"]
			]);

		$phpcode = $cp->load( __DIR__ . "/assets/detail.page", __DIR__ . "/assets/detail.json", [
				"script" =>["done"],
				"assets" => ["//your.cdn.url/pages/detail.css", "//your.cdn.url/pages/detail.js"]
			])
			->toPHP($compiler);

		$pos = strpos($phpcode, '<?=$message[\'msg\']?>');  //4956.
		$htmlpos = strpos( $cp->toHTML(), 'MINA Pages'); // 4024.
		$this->assertEquals( $pos, 4956 );
		$this->assertEquals( $htmlpos, 4024 );
	}

}

<?php
require_once(__DIR__ . "/../vendor/autoload.php" );

require_once(__DIR__ . "/../../cache/src/Object.php" );
require_once(__DIR__ . "/../../cache/src/Base.php" );
require_once(__DIR__ . "/../../cache/src/Redis.php" );

require_once(__DIR__ . "/../src/Helper.php" );
require_once(__DIR__ . "/../src/HtmlParser.php" );
require_once(__DIR__ . "/../src/DataParser.php" );
require_once(__DIR__ . "/../src/DataCompiler.php" );
require_once(__DIR__ . "/../src/Compiler.php" );
require_once(__DIR__ . "/../src/Render.php" );


use Mina\Template\Render; 
use Mina\Template\DataCompiler;

class MyDataCompiler extends DataCompiler {
	
 	function __construct() {
	 	parent::__construct(["home"=>"http://apps.minapages.com/1"]);
   	}

  	function compile( $json_data ) {
  		$options = $this->getOptions();
  		$home = $options['home'];
	 	return "\t" .'$data = ["foo"=>"bar"];' . "\n\t@extract(\$data); echo '$home';  " ;
  	}
}

class RenderTest extends PHPUnit_Framework_TestCase {

	function testExecGetcodeFn() {

		$compiler = new MyDataCompiler();
		$render = new Render([
			"marker" => true,
			"script" =>['console.log("page loaded");'],
			"assets" => [
				"//sdk.minapages.com/1.0/mina.css", 
				"//wss.xpmjs.com/web.css", 
				"//wss.xpmjs.com/1.0/mina.js", 
				"//wss.xpmjs.com/web.js"
			],
			"cache" => [
				"engine" => 'redis',
				"prefix" => 'RenderTest:',
				"host" => "172.17.0.1",
				"port" => 6379
			]
		]);

		$resp = $render->exec("testpage", function( $name, $options ) {

			$that = $options["that"];
			$compiler = new MyDataCompiler();
			$phpcode = $that->compile([
					"page" => file_get_contents(__DIR__ . "/assets/detail.page"),
					"json" => file_get_contents(__DIR__ . "/assets/detail.json"),
					"compiler" => $compiler,
					"script" => [ "console.log('load from function');"],
					"assets" => [
						"//your.cdn.url/pages/detail.css", 
						"//your.cdn.url/pages/detail.js"
					],
				]);

			return $phpcode;

		}, ["return"=>true , "ttl"=>1, "that"=>$render] );

		// 检查缓存
		$cache = $render->cacheInst();
		if ( $cache ) {
			$cacheName = $render->cacheName("testpage", $_REQUEST );
			$resp_from_cache = $cache->get( $cacheName );
			$pos = strpos( $resp_from_cache, "http://apps.minapages.com/1");
		}

		$this->assertEquals( !empty($cache),  true );  // 验证缓存实例
		$this->assertEquals( is_string($resp_from_cache) , true );  // 验证从缓存中读取结果是否正确
		$this->assertEquals( $resp, $resp_from_cache ); // 验证从缓存中读取结果是否正确
		$this->assertEquals( $pos, 1 );// 验证编译结果是否正确

		usleep(1000001);
		$this->assertEquals( $cache->get( $cacheName ), false ); // 验证缓存是否自动失效

	}

}

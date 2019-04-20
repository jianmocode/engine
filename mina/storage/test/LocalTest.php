<?php
require_once(__DIR__ . "/../vendor/autoload.php" );

require_once(__DIR__ . "/../../cache/src/Object.php" );
require_once(__DIR__ . "/../../cache/src/Base.php" );
require_once(__DIR__ . "/../../cache/src/Redis.php" );


require_once(__DIR__ . "/../src/Object.php" );
require_once(__DIR__ . "/../src/Base.php" );
require_once(__DIR__ . "/../src/Local.php" );

use Mina\Storage\Local as Storage;

class LocalTest extends PHPUnit_Framework_TestCase {

	public function testIsa() {
		$stor = new Storage([
			"prefix" => "/data/stor/public",
			"url" => "//wss.xpmjs.com/static-file",
			"origin" => function( $path, $options ) {
				return "//wss.xpmjs.com/static-file/o" . $path;
			},
			"image" => [
				"driver" => "imagick"
			],
			"cache" => [
				"engine" => 'redis',
				"prefix" => 'LocalTest:',
				"host" => "172.17.0.1",
				"port" => 6379,
				"raw" =>1,
				"info" => 1
			]
		]);

		$this->assertEquals( $stor->is_a("/mm/girls.png", "image"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.jpg", "image"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.jpeg", "image"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.gif", "image"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.svg", "image"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am", "image"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.mp4", "video"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.flv", "video"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.3gp", "video"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "video"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.mp3", "audio"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.wav", "audio"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "audio"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.html", "text"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.css", "text"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.page", "text"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.txt", "text"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "text"), false );


		$this->assertEquals( $stor->is_a("/mm/girls.html", "html"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "html"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.css", "css"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "css"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.js", "js"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "js"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.page", "page"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "page"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.doc", "word"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.docx", "word"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "word"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.xls", "excel"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.xlsx", "excel"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "excel"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.ppt", "ppt"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.pptx", "ppt"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "ppt"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.pdf", "pdf"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "pdf"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.json", "json"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "json"), false );

		$this->assertEquals( $stor->is_a("/mm/girls.zip", "zip"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.rar", "zip"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.7z", "zip"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.tar", "zip"), true );
		$this->assertEquals( $stor->is_a("/mm/girls.am",  "zip"), false );

	}



	public function testFileop() {

		$stor = new Storage([
			"prefix" => "/data/stor/public",
			"url" => "//wss.xpmjs.com/static-file",
			"origin" => function( $path, $options ) {
				return "//wss.xpmjs.com/static-file/o" . $path;
			},
			"cache" => [
				"engine" => 'redis',
				"prefix" => 'LocalTest:',
				"host" => "172.17.0.1",
				"port" => 6379,
				"raw" =>1,
				"info" => 1
			]
		]);


		$infoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/mm/girls.page",
		    "origin" => "//wss.xpmjs.com/static-file/o/mm/girls.page",
		    "mime" => "text/mina-pages",
		    "path" => "/mm/girls.page"
		];

		$rawShoudbe = "<b>这是一个女孩</b>";

		$info =  $stor->upload( "/mm/girls.page", "<b>这是一个女孩</b>" );
		$this->assertEquals( $info, $infoShoudbe);

		$this->assertEquals( $stor->isExist( "/mm/girls.page"), true);
		$info = $stor->get( "/mm/girls.page" );
		$this->assertEquals( $info, $infoShoudbe);

		$cache = $stor->cache();
		$resp = $cache->getJSON("/mm/girls.page:info");
		$this->assertEquals( $info, $infoShoudbe);

		// 测试刷新
		$stor->refresh( "/mm/girls.page");
		$this->assertEquals( $cache->getJSON("/mm/girls.page:info"), false);
		$this->assertEquals( $cache->get("/mm/girls.page:raw"), false);

		// 测试 getBlob
		$raw = $stor->getBlob( "/mm/girls.page" );
		$this->assertEquals( $raw, $rawShoudbe);
		$raw = $cache->get("/mm/girls.page:raw");
		$this->assertEquals( $raw, $rawShoudbe);

		usleep(1000001);
		$this->assertEquals( $cache->getJSON("/mm/girls.page:info"), false);
		$this->assertEquals( $cache->get("/mm/girls.page:raw"), false);

		// 重新取值
		$info = $stor->get( "/mm/girls.page" );
		$this->assertEquals( $info, $infoShoudbe);
		$raw = $stor->getBlob( "/mm/girls.page" );
		$this->assertEquals( $raw, $rawShoudbe);
		$this->assertEquals( $cache->getJSON("/mm/girls.page:info"), $infoShoudbe);
		$this->assertEquals( $cache->get("/mm/girls.page:raw"), $rawShoudbe);

		// 测试删除
		$stor->remove( "/mm/girls.page" );
		$this->assertEquals( $stor->isExist( "/mm/girls.page"), false);
		$this->assertEquals( $cache->getJSON("/mm/girls.page:info"), false);
		$this->assertEquals( $cache->get("/mm/girls.page:raw"), false);
	}

	public function testRemove() {

		$stor = new Storage([
			"prefix" => "/data/stor/public",
			"url" => "//wss.xpmjs.com/static-file",
			"origin" => function( $path, $options ) {
				return "//wss.xpmjs.com/static-file/o" . $path;
			},
			"cache" => [
				"engine" => 'redis',
				"prefix" => 'LocalTest:',
				"host" => "172.17.0.1",
				"port" => 6379,
				"raw" =>1,
				"info" => 1
			]
		]);

		$info =  $stor->upload( "/mm/gg/dd/mm/girls.page", "<b>这是一个女孩</b>" );
		$info =  $stor->upload( "/mm/gg/dd/boy.page", "<b>这是一个男孩</b>" );
		
		$resp =  $stor->remove("/mm/gg/dd/mm/boy.page");
		$this->assertEquals( $resp,true);
		$resp =  $stor->remove("/mm");
		$this->assertEquals( $resp,true);
		$this->assertEquals( $stor->isExist("/mm/gg/dd/mm/girls.page"), false);
		$this->assertEquals( $stor->isExist("/mm/gg/dd/boy.page"), false);

	}
}
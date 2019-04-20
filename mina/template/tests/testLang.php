<?php
require_once(__DIR__ . "/../vendor/autoload.php" );

require_once(__DIR__ . "/../../cache/src/Obj.php" );
require_once(__DIR__ . "/../../cache/src/Base.php" );
require_once(__DIR__ . "/../../cache/src/Redis.php" );

require_once(__DIR__ . "/../src/Lang.php" );

use Mina\Template\Lang; 
$config = [
    "cache" => [
        "engine" => 'redis',
        "prefix" => 'Page:Pages:',
        "host" => "172.17.0.1",
        "port" => 6379
    ]
];

class TestLang extends PHPUnit_Framework_TestCase {


    /**
     * 测试安装语言包
     */
	function testInstall() {

        global $config;
        $pkglang = realpath( __DIR__ . "/assets/lang/en.zip");
        $lang = new Lang($config);
                
        // 安装语言包
        $resp = $lang->install("root", "tars", $pkglang, "/data/stor/private/lang");
        $this->assertEquals( $resp,  true );  
    }


    /**
     * 测试载入语言包
     */
    function testLoad() {

        global $config;
        $lang = new Lang($config);
        $lang->load("/data/stor/private/lang");
    }


    function testTranslate() {
        global $config;
        $lang = new Lang($config);

        // 页面
        $page = "root:tars/desktop/index/index";
        $content = file_get_contents(__DIR__ . "/assets/lang/content.html");
        $lang->translate( $content, $page, "en");

    }

    function testTranslateNotFound() {
        global $config;
        $lang = new Lang($config);

        // 页面
        $page = "root:tars/desktop/index/index2";
        $content = file_get_contents(__DIR__ . "/assets/lang/content.html");
        $lang->translate( $content, $page, "en");

    }

    function testData(){
        global $config;
        $lang = new Lang($config);
        // 页面
        $page = "root:tars/desktop/index/index";
        $json_text = file_get_contents(__DIR__ . "/assets/lang/data.json");
        $source = json_decode( $json_text, true );
        $data = $lang->data( $source, $page, "en");
    }

    function testDataNotFound(){
        global $config;
        $lang = new Lang($config);
        // 页面
        $page = "root:tars/desktop/index/index2";
        $json_text = file_get_contents(__DIR__ . "/assets/lang/data.json");
        $source = json_decode( $json_text, true );
        $data = $lang->data( $source, $page, "en");
    }

}
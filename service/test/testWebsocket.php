<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Websocket 测试... \n\n\t";

class testJob extends PHPUnit_Framework_TestCase {


    function testStart() {

        $config =[
           "host" => "0.0.0.0",
           "port" => 10086,
           "home" => "https://www.jianmo.ink",
        //    "ssl_cert_file" => '/config/ssl.crt',
        //    "ssl_key_file"  => '/config/key.crt',
           "user" => 0
        ];
        $ws = new \Xpmse\Websocket(["name"=>"XpmseDefault"]);
        $ws->server($config);

    }
	
}
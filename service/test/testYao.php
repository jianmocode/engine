<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Yao;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Yao 测试... \n\n\t";

const APPID = '152508104986798';
const SECRET = 'beaa5e6fe23f12c4fdb0594acfbabd9a';

class testDom extends PHPUnit_Framework_TestCase {

    /**
     * 测试 GetToken
     */
    function testGetToken() {
        $yao = new Yao();
        $token = $yao->getToken( APPID, SECRET );
        $this->assertArrayHasKey('token', $token );
    }

    /**
     * 测试 Exit
     */
    function testExit() {
        $yao = new Yao();
        $resp = $yao->exit( APPID );
        $this->assertTrue( $resp );
    }

}
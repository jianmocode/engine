<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Wechat;
use \Yao\Arr;

$GLOBALS["config"] =  loadConfig(  __DIR__ . "/config-wxsev.php" );

/**
 * 测试微信API
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testWechat extends TestCase {

    function testAccessToken() {
        global $config;
        $wechat = new Wechat($config);
        $access_token = $wechat->accessToken();
        var_dump( $access_token );
        $this->assertEquals( false, false );

    }

}
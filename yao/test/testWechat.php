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
        $this->assertEquals( is_string($access_token) && !empty($access_token), true );

        $access_token2 = $wechat->accessToken();
        $this->assertEquals( $access_token2, $access_token );

        // 刷新Token
        $access_token3 = $wechat->accessToken( true );
        $this->assertEquals( is_string($access_token3) && !empty($access_token3), true );
        $this->assertEquals( $access_token3 != $access_token, true );

    }

}
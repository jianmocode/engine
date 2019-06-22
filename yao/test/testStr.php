<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Str;

echo "\n";
/**
 * 测试字符串处理工具
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testStr extends TestCase {

    /**
     * 测试 forceHttps
     */
    function testForceHttps() {
        $testUrls = [
            "//www.vpin.biz",
            "www.vpin.biz",
            "2www.vpin.biz",
            "http://www.vpin.biz",
            "https://www.vpin.biz"
        ];
        foreach ( $testUrls as $url ) {
            $url = Str::forceHttps( $url );
            $this->assertEquals( strpos($url, "https://") === 0, true);
            $this->assertEquals( Str::isURL($url), true);
        }
    }

}
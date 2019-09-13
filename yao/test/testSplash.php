<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Splash;


Splash::setting([
    "host" => "172.18.0.1",
    "port" => 8050
]);

/**
 * 测试异常对象
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testSplash extends TestCase {
    
    /**
     * 测试 RenderHtml
     */
    function testRenderHtml() {

        $content = Splash::renderHtml("https://www.vpin.biz", [
            "resource_timeout" => 5,
            "user_agent" => "FROM Yao/Splash",
            "js_source" => "document.title=\"INJECT TITLE\";"
        ]);

        $this->assertEquals(strpos($content, "INJECT TITLE") !== false, true);
    }


}
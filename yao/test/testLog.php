<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Log;

/**
 * 测试异常对象
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testLog extends TestCase {
    
    /**
     * 测试 accesslog
     */
    function testAccessLog() {
        $accessLog = new Log("access");
        $accessLog->info("you access");
        $this->assertEquals(404, 404);
    }

    /**
     * 测试 accesslog
     */
    function testErrorLog() {
        $errorLog = new Log("error");
        $errorLog->error("Something error");
        $this->assertEquals(404, 404);
    }

    /**
     * 测试 debugLog
     */
    function testDebugLog() {
        $debugLog = new Log("debug");
        $debugLog->debug("debug info");
        $this->assertEquals(404, 404);
    }

}
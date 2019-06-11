<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\FS;

/**
 * 测试异常对象
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testFS extends TestCase {
    
    /**
     * 测试 Write
     */
    function testWrite() {

        $time = time();
        $response = FS::write("hello/world-{$time}.md", "这是一个测试文件");
        $this->assertEquals(404, 404);
    }

     
    /**
     * 测试 WriteStream
     */
    function testWriteStream() {
        $time = time();
        $stream = fopen("https://www.baidu.com", 'r');
        $response = FS::writeStream("hello/world-{$time}.html", $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        $this->assertEquals(404, 404);
    }

}
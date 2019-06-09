<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Excp;

/**
 * 测试异常对象
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testExcp extends TestCase {

    /**
     * 测试 getMessage() & getCode();
     */
    function testMessageAndCode() {
        $excp = new Excp("测试404错误", 404);
        $this->assertEquals($excp->getCode(), 404);
        $this->assertEquals($excp->getMessage(), "测试404错误");
    }

    /**
     * 测试 addField
     */
    function testAddField(){

        $excp =(new Excp("测试404错误", 400))
                    ->addField("user_name", "用户名称错误")
                    ->addField("user_id", "用户ID错误")
                ;
        $extra = $excp->getExtra();
        $this->assertEquals($extra["fields"][0], "user_name");
        $this->assertEquals($extra["fields"][1], "user_id");
        $this->assertEquals($extra["messages"]["user_name"], "用户名称错误");
        $this->assertEquals($extra["messages"]["user_id"], "用户ID错误");
    }
    

    /**
     * 测试 __toString()
     */
    function test__toString() {
        $excp = new Excp("测试404错误", 404);
        $string = $excp->__toString();
        $this->assertEquals( strpos( $string, "message"), 7);
    }

    /**
     * 测试 toArray()
     */
    function testToArray() {
        $excp = new Excp("测试404错误", 404);
        $array = $excp->toArray();
        $this->assertArrayHasKey('trace', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('extra', $array);

        $arrayWithTrace = $excp->toArray( true );
        $trace = current($arrayWithTrace["trace"]);
        $this->assertArrayHasKey('file', $trace);
        $this->assertArrayHasKey('line', $trace);
        $this->assertArrayHasKey('function', $trace);
        $this->assertArrayHasKey('class', $trace);
        $this->assertArrayHasKey('type', $trace);
        $this->assertArrayHasKey('args', $trace);

        $this->assertEquals( $trace["function"], __FUNCTION__ );
        $this->assertEquals( $trace["class"], __CLASS__ );

    }
}
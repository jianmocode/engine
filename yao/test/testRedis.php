<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Redis;

/**
 * 测试 Redis
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testRedis extends TestCase {

    function testGet() {
        Redis::set("foo", "bar");
        $value = Redis::get("foo");
        $this->assertEquals( $value, "bar" );
    }

}
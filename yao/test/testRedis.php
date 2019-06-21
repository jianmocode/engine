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

        // Test Set
        Redis::set("foo", "bar");
        $value = Redis::get("foo");
        $this->assertEquals( $value, "bar" );

        // With TTL
        Redis::set("foo_with_ttl", "bar_with_ttl", 1);
        $value = Redis::get("foo_with_ttl");
        $this->assertEquals( $value, "bar_with_ttl" );
        sleep(2);
        $value = Redis::get("foo_with_ttl");
        $this->assertEquals( $value, null );

        // Not exists
        $value = Redis::get("foo_not_exists");
        $this->assertEquals( $value, null );


    }

}
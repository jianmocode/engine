<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\MQ;

/**
 * 消息队列测试程序
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testMQ extends TestCase {
    
    /**
     * 测试 Push
     */
    function testPush() {
        $mq = new MQ("unit_test");
        
        for( $i=0; $i<10; $i++) {
            $time = microtime();
            $mq->push(["hello"=>"9 world {$time}"]);
        }
        $mq->push(["hello"=>"1 world {$time}"], 1);
        $mq->push(["hello"=>"2 world {$time}"], 2);
        $this->assertEquals(true, true);
    }

    /**
     * 测试 Pop
     */
    function testPop() {
        $mq = new MQ("unit_test");
        for( $i=0; $i<12; $i++){
            $mq->pop(function($data){
                echo "\nHello {$data["hello"]}";
            });
        }
        $this->assertEquals(true, true);
    }

    /**
     * 测试 Push 阻塞模式
     */
    function testPushBlocking() {

        $mq = new MQ("unit_test_blocking", ["blocking"=>true]);
        for( $i=0; $i<3; $i++) {
            $time = microtime();
            $mq->push(["hello"=>"9 blocking world {$time}"]);
        }
        $mq->push(["hello"=>"1 blocking world {$time}"], 1);
        $mq->push(["hello"=>"2 blocking world {$time}"], 2);
        $this->assertEquals(true, true);
    }

    /**
     * 测试 Pop 阻塞模式
     */
    function testPopBlocking() {
        $mq = new MQ("unit_test_blocking");
        for( $i=0; $i<5; $i++){
            $mq->pop(function($data){
                echo "\nHello {$data["hello"]}";
            });
        }
        $this->assertEquals(true, true);
    }
    
}
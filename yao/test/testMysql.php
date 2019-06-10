<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\MySQL;

/**
 * 测试 MySQL
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testMySQL extends TestCase {
    
    /**
     * 测试 accesslog
     */
    function testQuery() {

        $my = new MySQL();
        $my->query();
        $this->assertEquals(404, 404);
    }

}
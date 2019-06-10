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
    function testSelect() {

        // test select
        $my = new MySQL();
        $qb = $my   ->query()
                    ->select("name")
                    ->selectRaw("COUNT(id) as cnt")
              ;
        ;
        $this->assertEquals(404, 404);
    }

}
<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\DB;
DB::connect();

/**
 * 测试 DB
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testDB extends TestCase {
    
    /**
     * 测试 accesslog
     */
    function testSelect() {
        $rows = DB::table("store")
                      ->select("name")
                      ->selectRaw("COUNT(id_store) as cnt")
                      ->get()
                      ->toArray()
        ;

        $row = current( $rows );
        $this->assertEquals( $row["cnt"], 0 );
    }

}
<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\DB;
use \Yao\Schema;

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

    function testSchema() {
        Schema::dropIfExists('unit-test');
        Schema::create('unit-test', function ($table) {
            $table->bigIncrements('id');
        });
        $this->assertEquals( true, true );
    }

    /**
     * 测试插入数据
     */
    function testInsert() {

        DB::table('category')->truncate();
        DB::table("category")->insert([
            ['category_sn' => 'unit-test-1234567', "name"=>"测试1", 'icon' => json_encode(["url"=>"http://www.baidu.com", "name"=>"地址一"]) ],
            ['category_sn' => 'unit-test-1234568', "name"=>"测试2", 'icon' => json_encode(["url"=>"http://www.baidu.com", "name"=>"地址二"]) ]
        ]);

        $id = DB::table("category")->insertGetId([
            'category_sn' => 'unit-test-1234569', "name"=>"测试3", 'icon' => json_encode(["url"=>"http://www.baidu.com", "name"=>"地址三"]) 
        ]);

        $this->assertEquals( $id, 3 );
    }


    function testUpdate() {

        DB::table("category")
            ->where('category_sn', "unit-test-1234568")
            ->update([
                "icon->name"=> "变更名称"
            ]);
        $this->assertEquals( true, true );

    }
    
    /**
     * 测试 accesslog
     */
    function testSelect() {

        $count = DB::table("category")->count("id_category");
        $this->assertEquals( $count, 3);

        $rows = DB::table("category")
                      ->where("icon->name", "=", "变更名称")
                      ->select("name","category_sn", "icon")
                      ->selectRaw("COUNT(id_category) as cnt")
                      ->get()
                      ->toArray()
        ;
        $row = current( $rows );
        $this->assertEquals( $row["category_sn"], "unit-test-1234568" );
    }

}
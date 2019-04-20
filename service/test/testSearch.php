<?php
/**
 * 简墨搜索引擎(单元测试)
 * 
 * @author Max<https://github.com/trheyi>
 * @license Apache 2.0 license <https://www.apache.org/licenses/LICENSE-2.0>
 * @copyright 2019 Jianmo.ink
 */

require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Search;

echo "\nXpmse\Search 测试... \n\n\t";

class testApi extends PHPUnit_Framework_TestCase {


    /**
     * 测试推送数据
     */
	function testPush() {

        $se = Search::Engine();

        // 更新数据表
        (new Search())->__schema();
        
        // load test data
        $yaml = file_get_contents(__DIR__ . "/assets/search/data.yml");
        $data = yaml_parse($yaml);

        // 灌入测试数据
        foreach($data as $doc ){
            $resp = $se->push( $doc );
            $this->assertEquals( $resp, true );
        }
    }


    /**
     * 测试精确查询
     */
    function testTerm() {

        $se = Search::Engine();

        // 测试 term 方法
        $resp = $se->term("origin", "unit-test-article")
                   ->get()
        ;
        $this->assertEquals( $resp["total"], 3 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );

        $resp = $se->reset()
                   ->term("tags", "二月二")
                   ->term("origin", "unit-test-article")
                   ->get()
        ;
        $this->assertEquals( $resp["total"], 1 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );

        $resp = $se->reset()
                   ->term("origin", "unit-test-article")
                   ->term("tags", "二月二")
                   ->term("tags", "dancing")
                   ->get()
        ;
        $this->assertEquals( $resp["total"], 0 );

        // 测试 orTerm 方法
        $resp = $se->reset()
                   ->term("origin", "unit-test-article")
                   ->term("tags", "二月二")
                   ->orTerm("tags", "dancing")
                   ->get()
        ;
        $this->assertEquals( $resp["total"], 2 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050003" );

    }
    

    /**
     * 测试匹配查询
     */
    function testMatch() {

        $se = Search::Engine(["contextLength"=>40]);

        // 测试 match 方法
        $resp = $se ->term("origin", "unit-test-article")
                    ->match("title", "孟晚舟")
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 1 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );
      
        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->match( "title", "孟晚舟")
                    ->match( "summary", "王毅")
                    ->match( "content", "企业的权益")
                    ->get()
        ;

        $rs =  current( $resp["data"]);

        $this->assertEquals( $resp["total"], 1 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );

        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->match( "title", "孟晚舟")
                    ->match( "title", "Trump")
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 0 );


        // 测试 orMatch 方法
        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->match( "title", "孟晚舟")
                    ->orMatch( "title", "Trump")
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 2 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050003" );
    }


    /**
     * 测试范围查询
     */
    function testRange(){

        $se = Search::Engine();

        // 测试 range 方法
        $resp = $se->term("origin", "unit-test-article")
            ->range("published_at", ["gte"=>"2019-02-21"])
            ->get()
        ;

        $this->assertEquals( $resp["total"], 2 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050002" );

        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->range("published_at", ["gte"=>"2019-02-21", "lt"=>"2019-02-22"])
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 1);
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );

        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->range("published_at", ["gte"=>"2019-02-21"])
                    ->range("published_at", ["lt"=>"2019-02-22"])
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 1);
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );


        // 测试 orRange 方法 (需要增加验证测试)
        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->range("published_at", ["gt"=>"2019-02-21"])
                    ->orRange("published_at", ["lt"=>"2019-02-20"])
                    ->get()
        ;

        $this->assertEquals( $resp["total"], 2 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050002" );
    }


    /**
     * 测试排序
     */
    function testSort() {

        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->sort("published_at", ["order"=>"asc"])
                    ->get()
        ;
        $this->assertEquals( $resp["total"], 3 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050003" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050002" );
        $this->assertEquals( $resp["data"][2]["doc_id"], "unit-test-1552050001" );

        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->sort("priority", ["order"=>"desc"])
                    ->sort("published_at", ["order"=>"asc"])
                    ->get()
        ;

        $this->assertEquals( $resp["total"], 3 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050001" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050003" );
        $this->assertEquals( $resp["data"][2]["doc_id"], "unit-test-1552050002" );

    }


    /**
     * 测试读取字段
     */
    function testSelect() {

        $default_fields = [
            "doc_id", "origin_id", "title", "summary", "url", "published_at", "type", "tags", "categories", 
            "users", "origin", "author", "priority", "data", "similar", "highlight", "context"
        ];
        sort( $default_fields );
        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->get();
        ;

        $fields = array_keys( current( $resp["data"] ) );
        sort( $fields );
        $this->assertEquals( $fields, $default_fields );
        
    
        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->select(["doc_id", "title", "summary"])
                    ->get();
        
        $fields = array_keys( current( $resp["data"] ) );
        $this->assertEquals( $fields, ["doc_id", "title", "summary", "highlight","context"] );


        $resp = $se ->reset()
                    ->term("origin", "unit-test-article")
                    ->select("doc_id, title, summary")
                    ->get();
        
        $fields = array_keys( current( $resp["data"] ) );
        $this->assertEquals( $fields, ["doc_id", "title", "summary", "highlight","context"] );

    }


    /**
     * 测试分页
     */
    function testPagenation(){

        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->get(2,2)
        ;

        $this->assertEquals( count($resp["data"]), 1 );
        $this->assertEquals( $resp["perpage"], 2 );
        $this->assertEquals( $resp["curr"], 2 );
        $this->assertEquals( $resp["prev"], 1 );
        $this->assertEquals( $resp["next"], false );
        $this->assertEquals( $resp["last"], 2 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050003" );
        
    }


    /**
     * 测试组合查询
     */
    function testComps() {

        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->match("content", "中国人")
                    ->range("published_at", ["gte"=>"2019-02-21"])
                    ->sort("priority", ["order"=>"asc"])
                    ->get(1,2)
        ;

        $this->assertEquals( count($resp["data"]), 2 );
        $this->assertEquals( $resp["perpage"], 2 );
        $this->assertEquals( $resp["curr"], 1 );
        $this->assertEquals( $resp["prev"], false );
        $this->assertEquals( $resp["next"], false );
        $this->assertEquals( $resp["last"], 1 );
        $this->assertEquals( $resp["data"][0]["doc_id"], "unit-test-1552050002" );
        $this->assertEquals( $resp["data"][1]["doc_id"], "unit-test-1552050001" );
    }

    /**
     * 测试删除
     */
    function testRemoveOrigin() {

        $se = Search::Engine();
        $resp = $se ->term("doc_id", "unit-test-1552050001")
                    ->select(["doc_id", "origin", "origin_id"])
                    ->get()
        ;

        foreach( $resp["data"] as $rs ) {
            $this->assertEquals( $se->removeOrigin($rs["origin"], $rs["origin_id"] ), true );
        }

        $resp = $se ->term("doc_id", "unit-test-1552050001")
                    ->select("doc_id")
                    ->get()
        ;

        $this->assertEquals( $resp["total"], 0 );

    }


    /**
     * 测试删除
     */
    function testRemove() {

        $se = Search::Engine();
        $resp = $se ->term("origin", "unit-test-article")
                    ->select("doc_id")
                    ->get()
        ;

        foreach( $resp["data"] as $rs ) {
            $this->assertEquals( $se->remove($rs["doc_id"]), true );
        }

        $resp = $se ->term("origin", "unit-test-article")
                    ->select("doc_id")
                    ->get()
        ;

        $this->assertEquals( $resp["total"], 0 );

    }
	
}
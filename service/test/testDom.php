<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Dom;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Dom 测试... \n\n\t";

class testDom extends PHPUnit_Framework_TestCase {
    

    function testText() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $text = new Dom("数据维护", "text");
        $div->append( $text );
        $text->text("数据维护，更新数据集合 By text");
        $this->assertTrue( "数据维护，更新数据集合 By text" == $text->text );

    }

    function testInnerText() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $div->innerText("数据维护, 更新数据集合 By innerText");
        $this->assertTrue( "数据维护, 更新数据集合 By innerText" == $div->innerText() );
    }

    function testInnerHTML() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $div->innerHTML("<span>数据维护, 更新数据集合 By innerHTML</span>");
        $this->assertTrue( "<span >数据维护, 更新数据集合 By innerHTML</span>" == $div->innerHTML() );

        $span = current($div->children);
        $this->assertTrue( "数据维护, 更新数据集合 By innerHTML" == $span->innerText() );
    }


    function testAttr() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $class = $div->attr('class');
        $this->assertTrue( "unit-test" == $class );

        $now = time() . rand(9,20);
        $div->attr("data-id", $now)
            ->attr("disabled", "disabled")
        ;
        $this->assertTrue( $now == $div->attr('data-id') );
        $this->assertTrue( true === $div->attr('disabled') );
    }


    function testAddClass() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $now = time() . rand(9,20);
        $div->addClass("unit-test-{$now}");
        $this->assertTrue( "unit-test unit-test-{$now}" == $div->attr('class') );
    }


    function testRemoveClass() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test unit-test2 unit-test3", 
            "style"=>"padding:5px"
        ]);

        $div->removeClass("unit-test2");
        $this->assertTrue( "unit-test unit-test3" == $div->attr('class') );
    }

    function testCss() {
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test unit-test2 unit-test3", 
            "style"=>"padding:5px"
        ]);

        $div->css("margin",".5em")
            ->css("border", "1px solid red")
        ;
        $this->assertTrue( ".5em" == $div->css('margin') );
        $this->assertTrue(  "5px" == $div->css('padding') );
        $this->assertTrue(  "1px solid red" == $div->css('border') );
    }

    function testAppend() {

        $now = time() . rand(9,20);
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);

        $span = new Dom( 'span', ['class'=>"unit-test-span"]);
        $div->append( $span );
        $span->attr("data-id", $now );

        $node = current( $div->children );
        $this->assertTrue( $now == $node->attr('data-id') );
        
    }

    function testBefore() {

        $now = time() . rand(9,20);
        $div = new Dom("div", [
            "data-id"=>time(), 
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);
        $span = new Dom( 'span', ['class'=>"unit-test-span-2"]);
        $insert = new Dom( 'span', ['class'=>"unit-test-span-before"] );

        $div->append( new Dom( 'span', ['class'=>"unit-test-span-1"]) );
        $div->append( $span );
        $div->append( new Dom( 'span', ['class'=>"unit-test-span-3"]) );
        $span->before( $insert );
        $insert->attr("data-id", $now );

        $node = $div->children[1];
        $this->assertTrue( $now == $node->attr('data-id') );

    }


    function testAfter() {

        $now = time() . rand(9,20);
        $div = new Dom("div", [
            "data-id"=>time(), 
            "disabled" => "disabled",
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);
        $span = new Dom( 'span', ['class'=>"unit-test-span-2"]);
        $insert = new Dom( 'span', ['class'=>"unit-test-span-after"] );

        $div->append( new Dom( 'span', ['class'=>"unit-test-span-1"]) );
        $div->append( $span );
        $div->append( new Dom( 'span', ['class'=>"unit-test-span-3"]) );
        $span->after( $insert );
        $insert->attr("data-id", $now );

        $node = $div->children[2];
        $this->assertTrue( $now == $node->attr('data-id') );
    }


    function testRemove() {

        $now = time() . rand(9,20);
        $div = new Dom("div", [
            "data-id"=>time(), 
            "disabled" => "disabled",
            "class"=>"unit-test", 
            "style"=>"padding:5px"
        ]);
        $span = new Dom( 'span', ['class'=>"unit-test-span-2"]);
        $insert = new Dom( 'span', ['class'=>"unit-test-span-after"] );

        $div->append( new Dom( 'span', ['class'=>"unit-test-span-1"]) );
        $div->append( $span );
        $div->append( new Dom( 'span', ['class'=>"unit-test-span-3"]) );
        $span->after( $insert );
        $insert->attr("data-id", $now );

        $node = $div->children[2];
        $this->assertTrue( $now == $node->attr('data-id') );

        // Remove
        $resp = $node->remove();
        $node = $div->children[2];
        $this->assertTrue( "unit-test-span-3" == $node->attr('class') );
    }


    function testLoadHtml() {

        $now = time() . rand(9,20);
        $div = Dom::loadHTML("
            <div data-id=\"{$now}\"  disabled class=\"unit-test\" style=\"padding:5px\">
                <span class=\"unit-test-span-1\">
                    你好，SPAN 1
                </span>
                <span class=\"unit-test-span-2\"> 你好，SPAN 2 </span>
                <span class=\"unit-test-span-3\"> 你好，SPAN 3 </span>
            </div>
        ");

        $node = $div->children[2];
        $this->assertTrue( "unit-test-span-3" == $node->attr('class') );
        $this->assertTrue( $now == $div->attr('data-id') );
    }


    function testLoadJSON() {
        $now = time() . rand(9,20);
        $div = Dom::loadJSON('
            {
                "name":"div",
                "type":"node",
                "attrs":{
                    "data-id":"155135926313",
                    "disabled":"",
                    "class":"unit-test",
                    "style":"padding:5px"
                },
                "children":[
                    {
                        "name":"span",
                        "type":"node",
                        "attrs":{
                            "class":"unit-test-span-1"
                        },
                        "children":[
                            {
                                "text":"你好，SPAN 1",
                                "type":"text"
                            }
                        ]
                    },
                    {
                        "name":"span",
                        "type":"node",
                        "attrs":{
                            "class":"unit-test-span-2"
                        },
                        "children":[
                            {
                                "text":" 你好，SPAN 2 ",
                                "type":"text"
                            }
                        ]
                    },
                    {
                        "name":"span",
                        "type":"node",
                        "attrs":{
                            "class":"unit-test-span-3"
                        },
                        "children":[
                            {
                                "text":" 你好，SPAN 3 ",
                                "type":"text"
                            }
                        ]
                    }
                ]
            }
        ');

        $node = $div->children[2];
        $this->assertTrue( "unit-test-span-3" == $node->attr('class') );
    }

}
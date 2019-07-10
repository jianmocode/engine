<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Arr;
use \Yao\Xlsx;

$xlsx_file = sys_get_temp_dir() . "/" . time() . ".xlsx";
$rows = [
    ["姓名", "手机号", "地址"],
    ["小明", "13193911918", "北京市海淀区大望路1号国贸中心"],
    ["小红", "13266101916", "北京市海淀区知春路五道口华清"],
];

/**
 * 测试Xlsx对象
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testXlsx extends TestCase {

    /**
     * 测试 testCreateNew
     */
    function testCreateNew() {

        global $xlsx_file, $rows;
        $y = 1;
        foreach( $rows as $row ) {
            foreach( $row as $x=>$col ) {
                Xlsx::setCellValueByColumnAndRow($x+1, $y, $col );
            }
            $y++;
        }

        Xlsx::save( $xlsx_file );
        $this->assertEquals( file_exists($xlsx_file), true);
    }

    /**
     * 测试读取表格
     */
    function testReadLine() {
        
        global $xlsx_file, $rows;
        Xlsx::readLine( function($cells, $y ) use($rows) {
            foreach( $cells as $x=>$cell ) {
                $idx = $y-1 . "." . $x;
                $valueShouldBe = Arr::get( $rows, $idx );
                $this->assertEquals( $valueShouldBe,  $cell->getValue() );
            }
        }, "工作表1", $xlsx_file);
        unlink( $xlsx_file );
    }

}
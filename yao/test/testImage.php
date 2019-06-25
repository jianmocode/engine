<?php
require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Image;

/**
 * 图片处理函数单元测试
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testImage extends TestCase {
    
    /**
     * 测试二维码生成
     */
    function testQrcode() {
        $blob = Image::qrcode("hello world", ["writer"=>"png","size"=>200, "foreground_color"=>["r"=>35,"g"=>35, "b"=>35], "margin"=>20]);
        file_put_contents("test.png", $blob);
        $this->assertEquals( md5( $blob ), "3dc955a9cc489fc608b219195ea67ed6");

        $blob = Image::qrcode("hello world", ["writer"=>"png","size"=>200, "foreground_color"=>["r"=>35,"g"=>35, "b"=>35], "margin"=>20], 20);
        $this->assertEquals( md5( $blob ), "3dc955a9cc489fc608b219195ea67ed6");

        $blob = Image::qrcode("hello world", ["writer"=>"png","size"=>200, "foreground_color"=>["r"=>35,"g"=>35, "b"=>35], "margin"=>20], 20);
        $this->assertEquals( md5( $blob ), "3dc955a9cc489fc608b219195ea67ed6");
    }

}
<?php
namespace App;

require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Model;


class Category extends Model {

    protected $table = 'category';
    protected $primaryKey = 'id_category';
    public $incrementing = false;

}

/**
 * 测试 DB
 * 
 * @package Vpin
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */
class testModel extends TestCase {


    /**
     * 测试选择数据
     */
    function testSelect() {
        $category = \App\Category::where('category_sn', 'unit-test-1234567')->first();
        $this->assertEquals( $category->category_sn, 'unit-test-1234567' );
    }

}
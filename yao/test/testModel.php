<?php
namespace App;

require_once('env.inc.php');
use \PHPUnit\Framework\TestCase;
use \Yao\Model;


class Category extends Model {

    protected $table = 'category';
    protected $primaryKey = 'id_category';
    public $incrementing = false;

    /**
     * JSON 解析
     */
    public function getIconAttribute($value){
        return json_decode( $value, true );
    }

}

class unitTestModel extends Model {
    protected $table = 'unit_test';
    protected $primaryKey = 'id_supplier_item';
    protected $schemaFile = __DIR__ . "/assets/item.json";
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
    function testSetup() {

        $ut = new unitTestModel();
        $ut->setup();
        $this->assertEquals(true, true);
    }

}
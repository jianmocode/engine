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
     * 测试创建数据结构
     */
    function testSchema() {

        $ut = new unitTestModel();
        $ut->schema();
        $this->assertEquals(true, true);
    }

    /**
     * 导出数据结构
     */
    function testExportSchema() {

        $ut = new unitTestModel();
        $schemaExport = $ut->exportSchema();
        $schemaOrigin = \yaml_parse_file(__DIR__ . "/assets/item.json");
        krsort($schemaOrigin);
        krsort($schemaExport);

        foreach( $schemaExport["indexes"] as & $indexes ) {
            ksort( $indexes );
        }

        foreach( $schemaExport["fields"] as & $fields ) {
            ksort( $fields );
        }

        foreach( $schemaOrigin["indexes"] as & $indexes ) {
            ksort( $indexes );
        }

        foreach( $schemaOrigin["fields"] as & $fields ) {
            unset($fields["nested"]);
            unset($fields["array"]);
            ksort( $fields );
        }

        // 打印对比结果
        if ( md5(json_encode($schemaExport) ) != md5(json_encode($schemaOrigin)) ) {

            echo "\n";
            echo "export data:\n";
            echo json_encode($schemaExport, JSON_PRETTY_PRINT |JSON_UNESCAPED_UNICODE )  . "\n";
            
            echo "\n";
            echo "origin data:\n";
            echo json_encode($schemaOrigin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

            echo "\n";
            $diff = xdiff_string_diff( json_encode($schemaExport, JSON_PRETTY_PRINT |JSON_UNESCAPED_UNICODE ) . "\n", json_encode($schemaOrigin, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE). "\n");
            echo "diff \n";
            echo $diff;
        }
        
        $this->assertEquals(md5(json_encode($schemaExport) ),  md5(json_encode($schemaOrigin)));
    }

}
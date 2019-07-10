<?php
/**
 * Class Xlsx
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;

use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;


/**
 * Xlsx 数据表类
 * 
 * see https://github.com/PHPOffice/PhpSpreadsheet
 * 
 */
class Xlsx {


    /**
     * Xlsx 工作表
     * 
     * @var Spreadsheet 对象 ( \PhpOffice\PhpSpreadsheet\Spreadsheet )
     */
    public static $spreadSheet = null;


    /**
     * 当前工作表
     * 
     * @var Worksheet 对象 (\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)
     */
    public static $activeSheet = null;


    /**
     * Xlsx 对象, 用于写入
     * 
     * @var Writer 对象 (\PhpOffice\PhpSpreadsheet\Writer\Xlsx)
     */
    public static $writer = null;



    /**
     * Xlsx 数据表对象
     */
    public function __construct() {}



    /**
     * 读取数据表每一行
     * @param callable $callback( Cell $cells, int $row );
     * @param string   $sheet 数据表名称
     * @param string $xlsx_file Excel文件
     * 
     * @return void
     * @throws Excp
     */
    public static function readLine( callable $callback, string $sheet = "",  $xlsx_file=null ) {

        if ( is_null(self::$activeSheet) ) {
            self::activeSheet( $sheet, $xlsx_file );   
        }

        $highestRow = self::$activeSheet->getHighestRow(); // e.g. 10
        $highestColumn = self::$activeSheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); 

        for ($row = 1; $row <= $highestRow; ++$row) {
            $cells = [];
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                array_push($cells, self::$activeSheet->getCellByColumnAndRow($col, $row));
            }
            $callback( $cells, $row );
        }
    }


    /**
     * 读取 Xlsx 对象, 用于写入
     * 
     * @param string $xlsx_file Excel文件
     * 
     * @return Writer 对象
     * @throws Excp 
     */
    public static function writer( $xlsx_file = null  ) {
        
        if ( is_null(self::$spreadSheet) ) {
            self::spreadSheet( $xlsx_file );   
        }

        self::$writer = new Writer(self::$spreadSheet);
        return self::$writer;
    }


    /**
     * 保存数据表到文件
     * 
     * @param string $output_file  文件保存路径
     * @return void
     * @throws Excp 
     */
    public static function save( string $output_file ) {

        if ( is_null(self::$spreadSheet) ) {
            throw Excp::create("未找到Excel文件信息", 402);
        }

        $writer = self::writer();
        try {
            $writer->save( $output_file );
        } catch( Exception $e ) {
            throw Excp::create($e->getMessage() . "(" . $e->getCode() .")", 500);
        }

    }

    
    /**
     * 读取当前工作表 
     * 
     * @param string $sheet     工作表名称
     * @param string $xlsx_file Excel文件
     * 
     * @return Worksheet 对象
     * @throws Excp 
     */
    public static function activeSheet( string $sheet, $xlsx_file = null  ) {

        if ( is_null(self::$spreadSheet) ) {
            self::spreadSheet( $xlsx_file );   
        }
        
        $sheetNames = self::$spreadSheet->getSheetNames();
        if( in_array( $sheet, $sheetNames) ) {
            self::$spreadSheet->setActiveSheetIndexByName( $sheet );

        } else { // 创建并激活读取Worksheet
            $index =  count($sheetNames) - 1;
            $worksheet =  new Worksheet(self::$spreadSheet, $sheet);
            self::$spreadSheet->addSheet($worksheet, $index );
            self::$spreadSheet->setActiveSheetIndex( $index );
        }

        self::$activeSheet =  self::$spreadSheet->getActiveSheet();
        return self::$activeSheet;
    }


    /**
     * 创建 Spreadsheet 对象
     * 
     * @param string $xlsx_file Excel文件
     * 
     * @return Spreadsheet  Spreadsheet对象
     * @throws Excp 
     */
    public static function spreadSheet( $xlsx_file = null ) {
        
        if ( is_null($xlsx_file) ) {
            self::$spreadSheet = new Spreadsheet();
            return self::$spreadSheet;
        }

        if ( !file_exists($xlsx_file) ) {
            throw Excp::create("Excel文件不存在($xlsx_file)", 404);
        }

        if ( !is_readable($xlsx_file) ) {
            throw Excp::create("没有Excel文件访问权限($xlsx_file)", 403);
        }

        $reader = IOFactory::createReader("Xlsx");
        self::$spreadSheet = $reader->load($xlsx_file);
        return self::$spreadSheet;
    }


    /**
     * Pass methods onto activeSheet.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {

        if ( is_null(self::$activeSheet) ) {
            self::activeSheet("工作表1");
        }

        try {
            return self::$activeSheet->$method(...$parameters);
        } catch( Excption $e ) {
            throw Excp::create( "调用{$method}方法出错" . $e->getMessage() . "(" . $e->getCode() .")", 500);
        }
    }

}
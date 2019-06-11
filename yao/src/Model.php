<?php
/**
 * Class Model
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Illuminate\Database\Eloquent\Model as EloquentModel;
use \League\Flysystem\AdapterInterface;

defined("YAO_PUBLIC_URL") ?: define("YAO_PUBLIC_URL", $GLOBALS["YAO"]["storage"]["public"]);
defined("YAO_PRIVATE_URL") ?: define("YAO_PRIVATE_URL", $GLOBALS["YAO"]["storage"]["private"]);


// 连接数据库
DB::connect();

/**
 * 数据模型 
 * see https://laravel.com/docs/5.8/eloquent
 */
class Model extends EloquentModel {

    /**
     * 公开文件访问路径
     */
    public $publicURL = YAO_PUBLIC_URL;


    /**
     * 私密的访问路径
     */
    public $privateURL = YAO_PRIVATE_URL;


    /**
     * 公开文件字段
     */
    public $publicFiles = [];
    

    /**
     * 私有文件字段
     */
    public $privateFiles = [];


    /**
     * 文件前缀
     */
    public $filePrefix = "";

    /**
     * 处理输入文件类字段
     */
    public function filesInput() {

        // 文件前缀
        $filePrefix = $this->filePrefix;
        if ( strpos( $filePrefix, "@") === 0 ) {
            $prefix = substr($filePrefix, 1, strlen($filePrefix));
            $filePrefix = "{$this->$prefix}/";
        }

        // 读取文件字段
        $files = $this->getFiles();
        foreach( $files as $attr=>$isPrivate ) {

            // 单一文件路径
            if ( is_string($this->$attr) ) {
                $this->$attr = $this->writeFile( $this->$attr, $isPrivate, $filePrefix );
            
            // 二维数组
            } else {

                // 批量处理文件
                $values = $this->$attr;
                foreach( $values as $key=>$path ) {
                    $values[$key] = $this->writeFile( $path, $isPrivate,  $filePrefix );
                }
                $this->$attr = $values;
            }
        }
    }

    /**
     * 读取文件字段清单
     */
    private function getFiles() {

        $files = [];
        if ( !empty($this->publicFiles) ) {
            $files = array_merge(
                $files,
                array_combine( $this->publicFiles, array_fill(0, count($this->publicFiles), false) )
            );
        }

        if ( !empty($this->privateFiles) ) {
            $files = array_merge(
                $files,
                array_combine( $this->privateFiles, array_fill(0, count($this->privateFiles), true) )
            );
        }

        return $files;
    }


    /**
     * 保存文件
     * @param $path 文件路径
     * @param $private 是否为私有文件
     * @param $perfix 文件前缀
     * @return string 文件名称;
     */
    private function writeFile( $path, $private=false, $perfix="" ) {

        if ( strpos( $path, "@") !== 0 ) {
            return $path;
        }

        $path = substr($path, 1, strlen($path));
        if ( !file_exists($path) ) {
            throw Excp::create("文件不存在({$path})", 404);
        }

        // 转存到指定的存储引擎
        $ext = pathinfo($path, PATHINFO_EXTENSION);         
        $name = FS::getPathName( $ext, $perfix );
        $stream = fopen($path, 'r');
        FS::writeStream($name, $stream, [
            'visibility' => $private ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $name;
    }

    /**
     * 处理输入文件类字段读取
     */
    public function filesOutput() {
    }


}
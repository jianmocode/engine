<?php
/**
 * Class Observer
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \League\Flysystem\AdapterInterface;

defined("YAO_PUBLIC_URL") ?: define("YAO_PUBLIC_URL", $GLOBALS["YAO"]["storage"]["public"]);
defined("YAO_PRIVATE_URL") ?: define("YAO_PRIVATE_URL", $GLOBALS["YAO"]["storage"]["private"]);

/**
 * 数据模型监听器
 */
class Observer {

    /**
     * 公开文件访问路径
     */
    protected $publicURL = YAO_PUBLIC_URL;


    /**
     * 私密的访问路径
     */
    protected $privateURL = YAO_PRIVATE_URL;


    /**
     * 公开文件字段
     */
    protected $publicFiles = [];
    

    /**
     * 私有文件字段
     */
    protected $privateFiles = [];


    /**
     * 文件前缀
     */
    protected $filePrefix = "";

    
    /**
     * 处理输入文件类字段
     */
    protected function filesInput( & $model ) {

        // 文件前缀
        if ( strpos( $this->filePrefix, "@") === 0 ) {
            $perfix = substr($this->filePrefix, 1, strlen($this->filePrefix));
            $this->filePrefix = $model->$perfix;
        }

        $files = $this->getFiles();

        foreach( $files as $attr=>$isPrivate ) {

            // 单一文件路径
            if ( is_string($model->$attr) ) {
                $model->$attr = $this->writeFile( $model->$attr, $isPrivate );
            
            // 二维数组
            } else {

                // 批量处理文件
                $values = $model->$attr;
                foreach( $values as $key=>$path ) {
                    $values[$key] = $this->writeFile( $path, $isPrivate );
                }
                $model->$attr = $values;
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
     * @return string 文件名称;
     */
    protected function writeFile( $path, $private=false ) {

        if ( strpos( $path, "@") !== 0 ) {
            return $path;
        }

        $path = substr($path, 1, strlen($path));
        if ( !file_exists($path) ) {
            throw Excp::create("文件不存在({$path})", 404);
        }

        // 转存到指定的存储引擎
        $ext = pathinfo($path, PATHINFO_EXTENSION); 
        $name = FS::getPathName( $ext, $this->filePrefix );
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
    protected function filesOutput( & $model ) {
    }


    /**
     * Handle the User "created" event.
     *
     * @param Model $user
     * @return void
     */
    public function creating( $store ) {
        $this->filesInput( $store );
    }

}
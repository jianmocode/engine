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

/**
 * 数据模型监听器
 */
class Observer {

    /**
     * 文件字段
     */
    protected $files = [];


    /**
     * 处理输入文件类字段
     */
    protected function filesInput( & $model ) {

        foreach( $this->files as $attr ) {

            // 单一文件路径
            if ( is_string($model->$attr) ) {
                $model->$attr = $this->writeFile( $model->$attr );
            
            // 二维数组
            } else {

                // 批量处理文件
                $values = $model->$attr;
                foreach( $values as $key=>$path ) {
                    $values[$key] = $this->writeFile( $path );
                }
                $model->$attr = $values;
            }
        }

    }


    /**
     * 保存文件
     * @param $path 文件路径
     */
    protected function writeFile( $path ) {

        if ( strpos( $path, "@") !== 0 ) {
            return $path;
        }

        $path = substr($path, 1, strlen($path));
        if ( !file_exists($path) ) {
            throw Excp::create("文件不存在({$path})", 404);
        }

        // 转存到指定的存储引擎
        $ext = pathinfo($path, PATHINFO_EXTENSION); 
        $name = FS::getPathName( $ext );
        $stream = fopen($path, 'r');
        FS::writeStream($name, $stream);
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

}
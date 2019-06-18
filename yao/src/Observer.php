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
     * Handle the User "creating" event.
     *
     * @param Model $model
     * @return void
     */
    public function creating( $model ) {

        // 自动生成数据
        $key = $model->generateId;
        if ( null !== $key && empty($model->{$key}) ) {
            $model->{$key} = Str::uniqid();
        }

        $model->filesInput();
    }

    /**
     * Handle the User "updating" event.
     *
     * @param Model $model
     * @return void
     */
    public function updating( $model ) {
        $model->filesInput();
    }

}
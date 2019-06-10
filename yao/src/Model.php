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

// 连接数据库
DB::connect();

/**
 * 数据模型 
 * see https://laravel.com/docs/5.8/eloquent
 */
class Model extends EloquentModel {}
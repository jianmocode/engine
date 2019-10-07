<?php
/**
 * Class Schema
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use Illuminate\Support\Facades\Schema as FacadesSchema;

// 连接数据库
DB::setting();

/**
 * 数据库
 * see https://laravel.com/docs/5.8/migrations#creating-tables
 */
class Schema extends FacadesSchema {
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return DB::connection($name)->getSchemaBuilder();
    }
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return DB::connection()->getSchemaBuilder();
    }
}

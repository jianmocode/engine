<?php
/**
 * Class Processor
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\MySQL\Query;
use \Yao\MySQL\Query\Builder;


/**
 * Processor
 * 
 * (Copy From \Illuminate\Database\Query\Processors\Processor )
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Database/Query/Processors/Processor.php
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Database/Query/Processors/MySqlProcessor.php
 */
class Processor
{
    /**
     * Process the results of a "select" query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }
    
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Yao\MySQL\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $query->getConnection()->insert($sql, $values);
        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);
        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object) $result)->column_name;
        }, $results);
    }
}
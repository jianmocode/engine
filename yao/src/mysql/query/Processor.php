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
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Processors/Processor.php
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/Processors/MySqlProcessor.php
 */
class Processor {
    /**
     * Process the results of a "select" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results) {
        return $results;
    }
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null){
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
    public function processColumnListing($results) {
        $mapping = function ($r) {
            $r = (object) $r;
            return $r->column_name;
        };
        return array_map($mapping, $results);
    }
}

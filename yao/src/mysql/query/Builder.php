<?php
/**
 * Class Builder
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\MySQL\Query;

/**
 * MySQL 查询构造器
 */
class Builder {

    /**
     * 构造函数
     */
    function __construct() {
    }


    public function where( $column, $operator = null, $value = null, $boolean = 'and') {
    }

    public function whereRaw($expression, array $bindings = []) {
    }

    public function match($column, $value, $boolean = 'and') {
    }

    public function join( $table, $f1, $op = NULL, $f2 = NULL,$type = 'inner', $where = false ){
    }

    public function leftjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
    }

    public function rightjoin( $table, $f1, $op = NULL, $f2 = NULL ) {
    }

    public function select( $columns = [] ) {
    }

    public function selectRaw($expression, array $bindings = []) {
    }

    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null) {
    }

    public function get($columns = null ) {
    }

    public function count($columns = '*'){
    }

    public function toArray() {
    }
    
}
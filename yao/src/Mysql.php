<?php
/**
 * Class 
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\MySQL\Driver;
use \Yao\MySQL\Query\Builder;

/**
 * MySQL数据库
 */
class MySQL {

    /**
     * 构造函数
     */
    public function __construct() {
    }

    /**
     * 构建查询器
     */
    public function Query() {
        return new Builder( $this );
    }

}
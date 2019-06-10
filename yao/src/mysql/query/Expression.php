<?php
/**
 * Class Expression
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\MySQL\Query;

/**
 * Builder 查询表达式
 */
class Expression {

    /**
     * 查询表达式
     *
     * @var mixed
     */
    protected $value;

    /**
     * 创建查询表达式
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value) {
        $this->value = $value;
    }

    /**
     * 读取查询表达式
     *
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }


    /**
     * 读取表达式数值
     *
     * @return string
     */
    public function __toString() {
        return (string) $this->getValue();
    }
}
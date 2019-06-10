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
 * Expression
 * 
 * (Copy From \Illuminate\Database\Query\Expression )
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Database/Query/Expression.php
 */
class Expression
{
    /**
     * The value of the expression.
     *
     * @var mixed
     */
    protected $value;
    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
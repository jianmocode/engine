<?php
/**
 * Class JsonExpression
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */


namespace Yao\MySQL\Query;
use InvalidArgumentException;


/**
 * JsonExpression
 * 
 * (Copy From \Illuminate\Database\Query\JsonExpression )
 * 
 * see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Database/Query/JsonExpression.php
 */
class JsonExpression extends Expression {

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
        $this->value = $this->getJsonBindingParameter($value);
    }
    /**
     * Translate the given value into the appropriate JSON binding parameter.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getJsonBindingParameter($value)
    {
        switch ($type = gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $value;
            case 'string':
                return '?';
            case 'object':
            case 'array':
                return '?';
        }
        throw new InvalidArgumentException('JSON value is of illegal type: '.$type);
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
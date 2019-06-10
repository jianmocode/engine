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
use \Yao\Excp;


/**
 * JsonExpression
 * 
 * (Copy From \Illuminate\Database\Query\JsonExpression )
 * 
 * see https://github.com/laravel/framework/blob/5.8/src/Illuminate/Database/Query/JsonExpression.php
 */
class JsonExpression extends Expression
{
    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        parent::__construct(
            $this->getJsonBindingParameter($value)
        );
    }
    /**
     * Translate the given value into the appropriate JSON binding parameter.
     *
     * @param  mixed  $value
     * @return string
     *
     * @throws \Yao\Excp
     */
    protected function getJsonBindingParameter($value)
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }
        switch ($type = gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'NULL':
            case 'integer':
            case 'double':
            case 'string':
                return '?';
            case 'object':
            case 'array':
                return '?';
        }
        throw new Excp("JSON value is of illegal type: {$type}", 402);
    }
}
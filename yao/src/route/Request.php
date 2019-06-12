<?php
/**
 * Class Request
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\Request;

/**
 * 路由器(Base on FastRoute)
 */
class Request {

    public $host = "";

    public $uri = [];

    public $params = [];

    public $files = [];

    /**
     * 构造函数
     */
    public function __construct() {
    }

    public function header() {
    }
}
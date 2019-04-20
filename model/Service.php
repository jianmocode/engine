<?php
namespace Xpmse\Model;

/**
 * 
 * XpmSE 通用服务表
 * XpmSE 1.8.1 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Service
 *
 * USEAGE: 
 *
 */

use \Xpmse\Service  as ServiceModel;
class Service extends ServiceModel {
 	function __construct( $param=[] ) {
 		parent::__construct($param);
 	}
}
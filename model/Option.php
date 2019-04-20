<?php
namespace Xpmse\Model;

/**
 * 
 * 后台执行任务库 ( 用来查看后台正在运行进程 )
 * XpmSE 1.4.8 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Option
 *
 * USEAGE: 
 *
 */

use \Xpmse\Option  as OptionModel;
class Option extends OptionModel {
 	function __construct( $param=[] ) {
 		parent::__construct($param);
 	}
}
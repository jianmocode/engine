<?php

namespace Xpmse\Data;
require_once('lib/Inc.php');
require_once('lib/Conf.php');
require_once('lib/Err.php');
require_once('lib/Excp.php');
require_once('lib/data-driver/Data.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;

use \Xpmse\DataDriver\Data as Data;


/**
 * XpmSE数据服务
 */

class Elasticsearch implements Data {

	/**
	 * 构造函数
	 * @param 数据库配置 $conf [description]
	 */
	function __construct( $conf = [] ) {

	}

}
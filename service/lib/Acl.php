<?php

namespace Xpmse;

require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/tabs/Acl.php');


use \Exception as Exception;
use \Xpmse\Supertable\Table as Table;

use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;

/**
 * AclTable 别名
 */
class Acl extends \Xpmse\AclTable {
	function __construct() {
		parent::__construct();
	}
}

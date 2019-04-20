<?php
namespace Xpmse\Model;
define('XPMSE_API', 'http://v.xpmjs.com/api.php');


/**
 * 
 * 系统升级模型
 *
 * CLASS 
 * 		\Xpmse\Upgrade
 *
 * USEAGE:
 *
 */

use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;



class Upgrade {

	function __construct() {
	}

	/**
	 * 检查是否包含新版本
	 * @return [type] [description]
	 */
	function checkNewVersion() {
		$latest = $this->call('latest');
		if ( isset($latest['code']) && isset($latest['message']) && isset($latest['extra']) ) {
			return false;
		}
		return $latest;
	}

	/**
	 * 检查 License
	 * @return [type] [description]
	 */
	function checkLicense() {
		$license = $this->call('license');
		return ($license === true);
	}


	function call( $method, $data=[] ) {
		$url = XPMSE_API;
		try {
			$resp = Utils::Request('POST', $url, [
				"query" => ['method'=>$method],
				"data" => array_merge($data, ['_host'=>$_SERVER['HTTP_HOST'], '_mac'=>'']),
				"type" => 'json'
			]);
		} catch( Excp $e) {
			return $e->toArray();
		}

		return $resp;
	}


}
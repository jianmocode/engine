<?php
namespace Xpmse\Loader;

use \Xpmse\Utils as Utils;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;

/**
 * 应用控制器基类
 */
 class Controller {
 	
 	protected $user;
 	protected $injections=[];
 	protected $headers =[];
 	protected $route = [];

 	protected $data = null;
 	protected $query = null;

 	// Extra Info
 	protected $browser = [];
 	protected $track = [];
 	protected $isajax  = null;
 	protected $datatype  = null;


 	function __construct() {
 	}

 	
	public function init( $user, $injections, $headers=[] ) {

		$this->user = $user;
 		$this->injections = $injections; // 废弃
 		$this->headers = $headers;
 		$this->route = [
			'controller' => (isset($this->headers['Xpmse-Controller'])) ? $this->headers['Xpmse-Controller'] : "default",
			'action' => (isset($this->headers['Xpmse-Action'])) ? $this->headers['Xpmse-Action']: "index"
		];

		// 即将废弃 (废弃-新的 API 机制)
		if ( $this->headers['Content-Type'] == 'application/api' ) {
			$this->data = json_decode($GLOBALS['_PHPINPUT'], true);
			$this->query = $_GET;
		}

		$this->browser = Utils::getBrowser(); 
 		return true;
	}


	protected function _conf( $nocache = false ) {

		$mem = new Mem;
		$cmap = $mem->getJSON("BaaS:CONF");
				
		if ( $cmap == false  || $cmap == null || $nocache === true ) {

			$tab = Utils::getTab('sys_conf', '_baas_');
			$cmap = []; $groups = [];
			$config = $tab->select("", ["name","value", "group", "gname"] );
			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['value'];
				if ( !is_array($groups[$row['gname']])) {
					$groups[$row['gname']] = [];
				}
				$groups[$row['gname']][$row['name']] = $row['value'];
			}


			$tab =  Utils::getTab('sys_cert','_baas_');
			$config = $tab->select("", ["name","path"] );

			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['path'];
			}

			$cmap['_groups'] = $groups;
			$mem->setJSON("BaaS:CONF", $cmap );
		}

		return $cmap;
	}

 }


?>
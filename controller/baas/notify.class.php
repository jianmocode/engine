<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Xpmse\Mem;
use \Xpmse\Wxpay;



class baasNotifyController extends coreController {

	
	function __construct() {
		
		parent::__construct();

		$this->wxconf = $c = $this->loadconf();

		$this->wxapp = new Wxapp([
			'appid'=> $c['wxapp.appid'],
			'secret'=>$c['wxapp.secret'],
		]);


		$this->wxpay = new Wxpay([
			'appid'=> $c['wxapp.appid'],
			'secret'=>$c['wxapp.secret'],
			'mch_id'=> $c['wxpay.mch_id'],  // 商户号
			'key' => $c['wxpay.key'],
			'cert' => $c['pay.cert'],
			'cert.key' => $c['pay.cert.key'],
			'notify_url' => Utils::getHomeLink() . R('baas','pay','notify')
		]);
	}


	/**
	 * 支付回掉通知
	 * @return [type] [description]
	 */
	function payment() {
		
	}


	private function loadconf() {

		$mem = new Mem;
		$cmap = $mem->getJSON("BaaS:CONF");
		$cmap = false;
		
		if ( $cmap == false  || $cmap == null) {

			$tab = M('table', 'sys_conf', ['prefix'=>'_baas_']);
			$cmap = [];
			$config = $tab->select("", ["name","value"] );


			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['value'];
			}


			$tab = M('table', 'sys_cert', ['prefix'=>'_baas_']);
			$config = $tab->select("", ["name","path"] );

			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['path'];
			}

			$mem->setJSON("BaaS:CONF", $cmap );

		}

		return $cmap;

	}
}
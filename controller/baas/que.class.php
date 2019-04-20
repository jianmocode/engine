<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;

class baasQueController extends baasBaseController {

	private $prefix = '_baas_';
	
	function __construct() {
		parent::__construct();
		$this->prefix = empty($this->data['_prefix']) ? '' : '_baas_' . $this->data['_prefix'] . '_';
		
		$this->event = M('Event', [
			'table.prefix' => $this->prefix,
			'wxapp.appid'  => $this->wxconf['wxapp.appid'. $this->cid],
			'wxapp.secret' => $this->wxconf['wxapp.secret'. $this->cid]
		]);
	}



	function run() {
		$events = is_array($this->data['_que']) ? $this->data['_que'] : [];
		$this->event->set($events);
		foreach ($events as $name => $event) {
			$this->event->trigger( $name, [], true, true );
		}

		Utils::out( $this->event->response() );
	}

}
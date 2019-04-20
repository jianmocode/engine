<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Excp as Excp;
use \Endroid\QrCode\QrCode as QrCode;

class baasCardController extends baasBaseController {

	private $prefix = '_baas_';
	
	function __construct() {
		parent::__construct();
		$this->prefix = empty($this->data['_prefix']) ? '' : '_baas_' . $this->data['_prefix'] . '_';
		$this->table = 'sys_card';
		$this->wxconf = $c = $this->loadconf();
		$this->wechat = new Wechat([
			'appid'=> $c['card.appid'],
			'secret'=>$c['card.secret'],
		]);
	}

	// 添加白名单账号
	function test() {
		// $resp = $this->wechat->cardTestwhitelist(["username"=>["okshadow"]]);
		
		// // 
		// $resp = $this->wxapp->getCardExt(['cardId'=>'pnHK_jouT8OiChUN-p1aBtKvWFeY','openid'=>'oJcP50F3aoEfSH6ggdAuVxP7-08U']);

		// Utils::out($resp);
	}

	function getCardsExt() {

		$user = $this->getUserInfo();
		if ( empty($user) ) {
			throw new Excp("请重新登录后使用此方法", 403, ["user"=>$user] );
		}

		$cardList = [];
		$cards = !empty($this->data['data']['cards']) ?$this->data['data']['cards'] : [];
		foreach ($cards as $card) {
			$card['openid'] = $user['openid'];
			$resp = $this->wxapp->getCardExt($card);
			unset($resp['string']);
			unset($resp['cardId']);
			unset($resp['api_ticket']);

			array_push($cardList, ['cardId'=>$card['cardId'], 'cardExt'=>$resp]);
		}

		Utils::out( $cardList );

	}


	function search() {
		$tab = M('table', $this->table, ['prefix'=>$this->prefix]);
		$resp = $tab->select("LIMIT 20");
		Utils::out( $resp['data'] );
	}

}
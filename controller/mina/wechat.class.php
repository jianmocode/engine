<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;


class minaWechatController extends minaBaseController {


	private $wechat = null;
	private $conf = null;
	private $appid = null;
	private $secret = null;


	/**
	 * 微信推送消息处理者
	 */
	private $handlers = [];

	/**
	 * MINA 微信后端请求转发器
	 */
	function __construct() {
		
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		parent::__construct();

		$conf = Utils::getConf();
		$this->appid = $_GET['appid'];
		$this->conf = $conf['_map'][$this->appid];
		if ( empty($this->conf) ) {
			throw new Excp("未知微信公众号配置信息", 404 ,['appid'=>$this->appid]);
		}

		$this->wechat = new Wechat($this->conf);
		$this->log = new \Xpmse\Log("Wechat");
		$this->handlers = Wechat::getHandlers();
	}




	/**
	 * 接收微信推送消息
	 * @return [type] [description]
	 */
	function router() {


		$signature = $_GET['signature'];
		$timestamp = $_GET['timestamp'];
		$nonce = $_GET['nonce'];
		$echostr = $_GET['echostr'];

		// 校验请求来源
		if ( $this->wechat->checkSignature( $signature, $timestamp, $nonce) == false ){
			throw new Excp("非法请求(签名验证失败)", 404 ,['signature'=>$signature, 'timestamp'=>$timestamp, 'notice'=>$nonce ]);
		}

		// 绑定 ECHO
		if ( !empty($_GET['echostr']) ) {
			echo $_GET['echostr'];
			return;
		}

		$message = file_get_contents("php://input");
		// $this->log->info("微信推送消息 {$_GET['nonce']}{$_GET['timestamp']}" , [] );


		// 解密消息
		$message = $this->wechat->decrypt( $_GET,  $message );
		$message = $this->wechat->messageToArray( $message );
				
		// 分发消息到API接口
		$data = M('Data'); 
		$resp = [];
		foreach ($this->handlers as $app=>$apis ) {
			foreach ($apis as $api => $v ) {
				if ( $v ) {
					$_api = "$app/$api";
					$resp[$_api] = $data->query( $_api, ["query"=>$_GET,"message"=>$message],[],[], null, true );
				}
			}
		}


		// 响应结果
		foreach ($resp as $_api=>$r ) {
			if ( $r['code'] !== 0 ) {
				$this->log->error("微信推送消息 {$_GET['nonce']}{$_GET['timestamp']} {$r['result']} @{$_api}" , [] );
			} else {
				$this->log->info("微信推送消息 {$_GET['nonce']}{$_GET['timestamp']} {$r['result']} @{$_api}" , [] );
				if ( $r['message'] != null ) {
					if ( $_GET['encrypt_type'] == 'aes') {
						$r['message'] = $this->wechat->encrypt($r['message']);
					}
					echo $r['message'];
				}
			}
		}


		return;
	}
}
<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;

class baasPayController extends baasBaseController {

	private $table = 'sys_paylog'; 
	private $prefix = '_baas_';
	private $log = null;

	function __construct() {
		
		parent::__construct();
		$this->prefix = empty($this->data['_prefix']) ? '' : $this->data['_prefix'];
		$this->log = $this->paylog();
		$this->event = M('Event', [
			'table.prefix' => $this->prefix,
			'wxapp.appid'  => $this->wxconf['wxapp.appid'. $this->cid],
			'wxapp.secret' => $this->wxconf['wxapp.secret'. $this->cid]
		]);

		if ( !$this->log->tableExists() ) { // 初始化支付记录表
			$schema =[

				["name"=>"sn",  
					"type"=>"string", 
					"option"=>["length"=>128, "unique"=>1, "index"=>true], 
					"acl"=>"r:r:-" ],

				["name"=>"unionid",  
					"type"=>"string", 
					"option"=>["length"=>64, "index"=>true], 
					"acl"=>"r:r:-"  ],

				["name"=>"openid",  
					"type"=>"string", 
					"option"=>["length"=>64, "index"=>true], 
					"acl"=>"r:r:-" ],

				["name"=>"out_trade_no",  
					"type"=>"string", 
					"option"=>["length"=>128, "unique"=>1], 
					"acl"=>"r:r:-"  ],

				["name"=>"prepay_id",  
					"type"=>"string", 
					"option"=>["length"=>128 , "index"=>true], 
					"acl"=>"r:r:-"  ],

				["name"=>"attach",  
					"type"=>"string", 
					"option"=>["length"=>256], 
					"acl"=>"r:r:-"  ],

				["name"=>"return_code",  
					"type"=>"string", 
					"option"=>["length"=>40], 
					"acl"=>"r:r:-"  ],

				["name"=>"detail",  
					"type"=>"text", 
					"option"=>["json"=>true], 
					"acl"=>"r:r:-"  ],

				["name"=>"body",  
					"type"=>"string", 
					"option"=>["length"=>100], 
					"acl"=>"r:r:-"  ],

				["name"=>"total_fee",  
					"type"=>"integer",
					 "option"=>["length"=>256], 
					 "acl"=>"r:r:-"  ],

				["name"=>"status",  
					"type"=>"string",
					 "option"=>["length"=>20],   // PENDING/DONE
					 "acl"=>"r:r:-"  ],
				
				["name"=>"remark",  
					"type"=>"string",
					 "option"=>["length"=>200], 
					 "acl"=>"r:r:-"  ],

				["name"=>"events",  
					"type"=>"text",
					 "option"=>["json"=>true], 
					 "acl"=>"r:r:-"  ],

				["name"=>"events_response",  
					"type"=>"text",
					 "option"=>["json"=>true], 
					 "acl"=>"r:r:-"  ],

				["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
			];


			$table_acl = [
				"fields" =>[ "{default}"=>"r:-:-" ],
				"record"=>"-:-:-",
				"table" =>"-:-:-",
				"user" => 'admin',
				"group" => 'admin'
			];


			$resp = M('Tabacl')->save( $this->table_name(), $table_acl);
			$resp = $this->log->__schema( $schema );

		}

	}

	function index() {

		echo json_encode([
				"server" => "Xpm Server V2",
				"status" => "ok"
			]);
	}

	function test(){
		// $params = [
		// 	"a"=>"a{{sn}}a",
		// 	"mc"=>"{{mc}}",
		// 	"b"=>["b{{sn}}b", "b {{order}} {{sn}} {{m}} b {{m}}MMMM"],
		// 	"c"=>["d"=>"d{{order}}d {{order}}ddd"]
		// ];

		// $this->event->filterParams($params, ['sn'=>111, 'order'=>222, 'mc'=>['mc'=>'isarray']]);
		// utils::out($params);

		echo  Utils::getHomeLink() . R('baas','pay','notify');
	}


	/**
	 * 统一下单接口
	 * @return [type] [description]
	 */
	function unifiedorder() {

		$params = $this->data;
		$loginInfo = empty($_SESSION['_loginInfo']) ? [] : $_SESSION['_loginInfo'];
		$events = is_array($params['_events']) ? $params['_events'] : [];
		if ( isset( $loginInfo['openid']) )  {
			$params['openid'] = $loginInfo['openid'];
		}

		$resp = $this->wxpay->unifiedorder( $params );

		if ( $resp['return_code'] == 'SUCCESS' ) {

			$data = $this->wxpay->requestPayment($resp['prepay_id']);
			$data['out_trade_no'] = $resp['out_trade_no'];
			$data['prepay_id'] = $resp['prepay_id'];
			$data['attach'] = $params['attach'];
			$data['return_code'] = 'SUCCESS';
			$data['sn'] = $this->sn();

			// 添加付款记录
			try{
				$this->log->create( array_merge(
						$params, $data, $this->currUser(),
						['status'=>'PENDING' , 'events'=>$events ]
				));
			} catch( Excp $e ) {} ;

			// 运行 Before 事件
			$this->event->set($events)->trigger('before', $data, true );
			$data['events'] = $this->event->response();

			Utils::out( $data );
			return;
		}

		Utils::out( $resp );
	}


	/**
	 * 支付完毕通知
	 * @return [type] [description]
	 */
	function payreturn() {

		$params = $this->data;
		
		if ( empty($params['sn']) ) {
			throw new Excp('请求数据异常无单号', 404, ['params'=>$params] );
		}

		if ( !$this->wxpay->checkReturnRequest($params) ) {
			throw new Excp('请求签名校验失败', 502, ['params'=>$params] );
		}

		$log = $this->log->getLine("WHERE sn=? LIMIT 1", ['*'], [$params['sn']]);

		if ( empty($log) ) {
			throw new Excp('请求数据异常无单号记录', 404, ['params'=>$params] );
		}

		if ( $log['status'] == 'LOCKED' ) { //  RETURN 
			
			Utils::out([
				'events'=>[], 
				'sn'=>$params['sn'], 
				'status'=>'LOCKED'] );

			return;
		}

		$log = $this->log->updateby('sn',[
			'status'=>'LOCKED', 
			'sn'=>$params['sn']
		]);

		$events = $log['events'];
		$query = array_merge($_GET, ["openid"=>$log['openid'], 'unionid'=>$log['unionid']]);
		$dd = $log; unset($dd['_id'], $dd['deleted_at'],$dd['created_at'],$dd['updated_at'],$dd['events'], $dd['_user'], $dd['_group'], $dd['_acl'], $dd['events_response']);

		foreach ($events as $type=> & $evts  ) {
			foreach ($evts as & $evt ) {
				if ( $evt['cmd'] == 'app' ||  $evt['cmd'] == 'api' ) {
					$evt['params']['api'][2] = empty($evt['params']['api'][2]) ?  $query : array_merge($query, $evt['params']['api'][2]);
					$evt['params']['data'] =  !is_array($evt['params']['data']) ?  $dd : array_merge($dd, $evt['params']['data']);
				}
			}
		}

		unset($log['openid']);
		unset($log['unionid']);
		$this->event->set($events);
		$this->event->trigger('success', $log, true);
		$this->event->trigger('complete', $log, true);
		$events_response = $this->event->response();

		// 更新付款记录
		try{
			$this->log->updateBy('sn', [
				'status'=>'DONE' , 
				'events_response'=>$events_response, 
				'sn'=>$params['sn'] 
			]);
		} catch( Excp $e ) {} ;

		Utils::out([
			'events'=>$events_response, 
			'sn'=>$params['sn'], 
			'status'=>'DONE']);
	}




	function notify() {

		$params = [
			"total_fee"=>5,
			"body" => '服务器开光'
		];

		$data = time() . "\n=========\n" . utils::get( $_REQUEST );
		file_put_contents("/tmp/pay.notify", $data, FILE_APPEND);
		echo "SUCCESS";
		// Utils::out( $this->wxpay->unifiedorder( $params ) );

	}


	protected function sn() {
		return time() . mt_rand(100000, 999999);
	}

	protected function currUser() {

		$user = (isset($_SESSION['_user'])) ? $_SESSION['_user'] : session_id();
		$group = (isset($_SESSION['_group'])) ? $_SESSION['_group'] : 'guest';
		$isadmin= (isset($_SESSION['_isadmin'])) ? $_SESSION['_isadmin'] : 0;
		
		$data = [];
		$data['_user'] = $user;
		$data['_group'] = $group;
		$data['_isadmin'] = $isadmin;
		return $data;
	}


	protected function paylog() {
		return M( 'Table', $this->table, ['prefix'=>$this->prefix]);
	}

	protected function table_name() {
		return $this->prefix . $this->table;
	}
}
<?php
namespace Xpmse;
require_once(__DIR__ . '/Inc.php');
require_once(__DIR__ . '/Conf.php');
require_once(__DIR__ . '/Err.php');
require_once(__DIR__ . '/Excp.php');
require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/wechat-encoder/WXBizMsgCrypt.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;
use \Wechat\Encoder\WXBizMsgCrypt as WXBizMsgCrypt;
use \Wechat\Encoder\ErrorCode as ErrorCode;

/**
 * XpmSE小程序SDK
 */
class Wxpay {

	private $conf = [];

	/**
	 * 应用配置信息
	 * @param array $option [description]
	 */
	function __construct( $conf = [] ) {

		// 微信支付相关配置
		$this->conf['appid'] = isset($conf['appid']) ?  $conf['appid'] : '';
		$this->conf['secret'] = isset($conf['secret']) ?  $conf['secret'] : '';
		$this->conf['mch_id'] = isset($conf['mch_id']) ?  $conf['mch_id'] : '';
		$this->conf['key'] = isset($conf['key']) ?  $conf['key'] : '';
		$this->conf['notify_url'] = isset($conf['notify_url']) ?  $conf['notify_url'] : '';

		// 证书
		$this->conf['cert'] = isset($conf['cert']) ?  $conf['cert'] : '';  // 证书
		// 证书密钥, 如果与证书合并则无需提供
		$this->conf['cert.key'] = isset($conf['cert.key']) ?  $conf['cert.key'] : '';  


		if ( empty($this->conf['appid']) ) {
			throw new Excp("缺少 appid", 400, ["Wxpay::conf"=>$this->conf, "conf"=>$conf] );
		}

		if ( empty($this->conf['secret']) && empty($this->conf['token']) ) {
			throw new Excp("缺少 secret", 400, ["Wxpay::conf"=>$this->conf, "conf"=>$conf] );
		}
		

		// if ( empty($this->conf['mch_id']) ) {
		// 	throw new Excp("缺少 mch_id", 400, ["Wxpay::conf"=>$this->conf, "conf"=>$conf] );
		// }

		// if ( empty($this->conf['key']) ) {
		// 	throw new Excp("缺少 key", 400, ["Wxpay::conf"=>$this->conf, "conf"=>$conf] );
		// }

		// if ( empty($this->conf['cert']) ) {
		// 	throw new Excp("缺少 cert", 400, ["Wxpay::conf"=>$this->conf, "conf"=>$conf] );
		// }

	}

	/**
	 * 生成微信支付签名
	 * @param  array $params 请求参数表
	 * @return string 签名
	 */
	private function signature( $params ) {
		ksort( $params );

		$params_list = [];
		foreach( $params as $k=>$v ) {
			array_push( $params_list, "$k=$v");
		}
		$stringSign = implode( "&", $params_list);
		$stringSignTemp="{$stringSign}&key=" . $this->conf['key'];
		return strtoupper(MD5($stringSignTemp));
	}


	/**
	 * 统一下单接口
	 * 
	 * @param  array $params 参数表
	 * @see https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
	 * @return $resp
	 */
	function unifiedorder( $params ) {

		$api = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		// $api = "https://wxcloud.xpmse.cn/default/api";

		$allow_keys = [
			"appid","mch_id","device_info","nonce_str","sign","sign_type",
			"body","detail","attach","out_trade_no","fee_type","total_fee",
			"spbill_create_ip","time_start","time_expire","goods_tag",
			"notify_url","trade_type","limit_pay","openid" ];
			
		// 参数默认值
		$params['appid'] = empty($params['appid']) ? $this->conf['appid'] : $params['appid'];
		$params['mch_id'] = empty($params['mch_id']) ? $this->conf['mch_id'] : $params['mch_id'];
		$params['notify_url'] = empty($params['notify_url']) ? $this->conf['notify_url'] : $params['notify_url'];

		// $params['notify_url'] = 'https://wss.xpmjs.com/test.php'

		$params['device_info'] = empty($params['mch_id']) ? 'WEB' : $params['mch_id'];
		$params['trade_type'] =  empty($params['trade_type']) ? 'JSAPI' : $params['trade_type'];
		$params['spbill_create_ip'] =  empty($params['spbill_create_ip']) ? Utils::getClientIP() : $params['spbill_create_ip'];

		$params['total_fee'] = intval( $params['total_fee'] );
		$params['out_trade_no'] = empty($params['out_trade_no']) ? $this->gen_out_trade_no() : $params['total_fee'];

		$params['nonce_str'] =  empty($params['nonce_str']) ? Utils::genString() : $params['nonce_str'];
		$params['sign_type']  = 'MD5';


		// 检查 notify_url
		if ( empty($params['notify_url']) ) {
			throw new Excp("缺少 notify_url", 400, ["Wxpay::conf"=>$this->conf, "params"=>$params] );
		}

		// 检查 total_fee
		if ( $params['total_fee'] <= 0  ) {
			throw new Excp("缺少 total_fee", 400, ["Wxpay::conf"=>$this->conf, "params"=>$params] );
		}

		// 检查 body
		if ( empty($params['body']) ) {
			throw new Excp("缺少 body", 400, ["Wxpay::conf"=>$this->conf, "params"=>$params] );
		}

		// 过滤无用参数
		foreach ($params as $key => $val) {
			if ( !in_array($key, $allow_keys) ) {
				unset( $params[$key]);
			}
		}	


		// 签名
		$params['sign'] = $this->signature($params);

		$resp = Utils::request( 'POST', $api,
			[
				// "debug"=>true,
				"datatype"=>'xml',
				"type" => "text",
				"data" => $this->params_to_xml($params, ['detail']),
				'cert' => $this->conf['cert'],
				'cert.key' => $this->conf['cert.key'],
				'rootca' => $this->conf['rootca']
			]	
		);

		$resp['out_trade_no'] = $params['out_trade_no'];	
		return $resp;
	}


	/**
	 * 生成 requestPayment JS 接口需要的签名数据
	 * @param  string $prepay_id 统一下单接口，返回数值
	 * @return array requestPayment 所需数据
	 */
	function requestPayment( $prepay_id ) {

		$params = [
			"appId" => $this->conf['appid'],
			"timeStamp"=>time(),
			"nonceStr" => Utils::genString(),
			"package" => "prepay_id=$prepay_id",
			"signType"=>"MD5"
		];

		$params['paySign'] = $this->signature($params);
		return $params;
	}


	/**
	 * 校验请求签名
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	function checkReturnRequest( $params ) {
		$data = [
			"appId" => $this->conf['appid'],
			"timeStamp"=>$params['timeStamp'],
			"nonceStr" => $params['nonceStr'],
			"package" => "prepay_id={$params['prepay_id']}",
			"signType"=>"MD5"
		];

		return ($params['paySign'] = $this->signature($data));
	}


	function params_to_xml( $params, $cdata_fields=[] ) {
		$xml = "<xml>\n";

		foreach ($params as $key => $value) {
			if ( in_array($key, $cdata_fields) ) {
				$value  = '<![CDATA[' .$value. ']]>';
			}
			$xml = $xml  . "<$key>$value</$key>\n";
		}

		$xml = $xml . "</xml>\n";

		return $xml;
	}


	/**
	 * 生成订单号
	 * @return [type] [description]
	 */
	function gen_out_trade_no() {
		return date('YmdHis') . floor(microtime()). rand(10000,99999);
	}

}
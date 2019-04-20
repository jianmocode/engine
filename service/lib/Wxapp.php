<?php
namespace Xpmse;
require_once(__DIR__ . '/Inc.php');
require_once(__DIR__ . '/Conf.php');
require_once(__DIR__ . '/Err.php');
require_once(__DIR__ . '/Excp.php');
require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/wechat-encoder/WXBizMsgCrypt.php');
require_once(__DIR__ . '/wechat-encoder/WXBizDataCrypt.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;
use \Wechat\Encoder\WXBizMsgCrypt as WXBizMsgCrypt;
use \WeChat\Encoder\WXBizDataCrypt as WXBizDataCrypt;
use \Wechat\Encoder\ErrorCode as ErrorCode;

/**
 * XpmSE小程序SDK
 */
class Wxapp {

	private $conf = [];


	/**
	 * 应用配置信息
	 * @param array $option [description]
	 */
	function __construct( $conf = [] ) {

		$this->conf['appid'] = isset($conf['appid']) ?  $conf['appid'] : '';
		$this->conf['secret'] = isset($conf['secret']) ?  $conf['secret'] : '';
		$this->conf['token'] = isset($conf['token']) ?  $conf['token'] : '';
		$this->conf['EncodingAESKey'] = isset($conf['EncodingAESKey']) ?  $conf['EncodingAESKey'] : '';

		// 微信支付相关配置
		$this->conf['mch_id'] = isset($conf['mch_id']) ?  $conf['mch_id'] : '';

		if ( empty($this->conf['appid']) ) {
			throw new Excp("缺少 appid", 400, ["Wxapp::conf"=>$this->conf, "conf"=>$conf] );
		}

		if ( empty($this->conf['secret']) && empty($this->conf['token']) ) {
			throw new Excp("缺少应用配置信息", 400, ["Wxapp::conf"=>$this->conf, "conf"=>$conf] );
		}

	}



	/**
	 * 使用 Appid Secret 换取 Session Key
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function getSessionKey( $code,  $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;

		if ( empty($secret) ) {
			throw new Excp("缺少应用配置信息", 400, [
				"Wxapp::conf"=>$this->conf, 
				'appid'=>$appid, 
				'secret'=>$secret ]);
		}

		if ( empty($appid) ) {
			throw new Excp("缺少应用配置信息", 400, [
				"Wxapp::conf"=>$this->conf, 
				'appid'=>$appid, 
				'secret'=>$secret ]);
		}


		$api = "https://api.weixin.qq.com/sns/jscode2session";
		$resp = Utils::Req('GET', $api, [
				'type' => 'json',
				'query' => [
					'appid'=>$appid,
					'secret'=>$secret,
					'js_code'=>$code,
					'grant_type'=> 'authorization_code'
				]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'code'=>$code, 'resp'=>$resp] 
			);
		}


		return $resp;
	}


	/**
	 * 读取用户资料 (已订阅/已授权的用户)
	 * @param  [type] $openid 用户 OpenID
	 * @param  [type] $appid  微信应用 AppID 默认为NULL, 从配置文件中读取
	 * @param  [type] $secret 微信应用 AppSecret  默认为NULL, 从配置文件中读取
	 * @return 成功返回 array $user 失败返回 Err 对象
	 */
	function getUserByOpenid( $openid, $appid=null, $secret=null ) {
		
		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/cgi-bin/user/info";
		$resp = Utils::Request('GET', $api,  [
			'query' => [
				'access_token'=>$access_token,
				'openid'=>$openid,
				'lang' => 'zh_CN' ]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token,'resp'=>$resp]);
		}

		return $resp;
	}




	/**
	 * 解密敏感数据
	 * @param  [type] $encryptedData [description]
	 * @param  [type] $iv            [description]
	 * @param  [type] $data          [description]
	 * @return [type]                [description]
	 */
	function decryptData( $encryptedData, $iv, $sessionKey, $appid=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$data = [];
		$pc = new WXBizDataCrypt($appid, $sessionKey);
		$errCode = $pc->decryptData($encryptedData, $iv, $data );

		if ($errCode == 0) { 
			return $data;
		} else {
			throw new Excp( 
				'数据解密失败', 
				$errCode,
				['appid'=>$appid, 'secret'=>$sessionKey, 'encryptedData'=>$encryptedData, 'iv'=>$iv] 
			);
		}
	}




	// 即将废弃
	function userLogin( $code, $rawData, $signature ) {

		$resp = $this->getSessionKey($code );
		$session_key = $resp['session_key'];
		$string = $rawData . $session_key;
		$s = sha1($string);
		if ( $s == $signature )  {
			$_SESSION['_userid'] = $resp['openid'];
			return true;
		}
		
		return false;
	}



	/**
	 * 发送模板消息 
	 * @param  array $option  模板消息数据
	 * @param  string  $appid  [description]
	 * @param  string  $secret [description]
	 * @return array $response 
	 * 
	 */
	function templateMessageSend( $option,  $appid=null, $secret=null ) {

		$token = $this->getAccessToken($appid, $secret);
		$api = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send";

		$resp = Utils::Req('POST', $api, [
				'type' => 'json',
				'datatype'=>'form',
				// 'debug'=> true,
				'query' => ['access_token'=>$token],
				'data'=> $option
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		return $resp;
	}

	// https://api.weixin.qq.com/cgi-bin/message/wxopen/template



	/**
	 * 小程序二维码 
	 * @param  string  $path   页面地址
	 * @param  integer $width  页面宽度
	 * @param  string  $appid  [description]
	 * @param  string  $secret [description]
	 * @return array $response 
	 *         		 $response['type'] 类型
	 *         		 $response['body'] 正文
	 */
	function getQrcode( $path, $width=430, $appid=null, $secret=null ) {

		$token = $this->getAccessToken($appid, $secret);

		$api = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode";
		$resp = Utils::Req('POST', $api, [
				'type' => 'json',
				'datatype'=>'auto',
				// 'debug'=> true,
				'query' => ['access_token'=>$token],
				'data'=>['path' => $path, 'width'=>$width]
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		return $resp;
	}


	function getWxacode( $path, $width=430,  $line_color=['r'=>0,'g'=>0, 'b'=>0],  $appid=null, $secret=null ) {

		$token = $this->getAccessToken($appid, $secret);

		$api = "https://api.weixin.qq.com/wxa/getwxacode";
		$resp = Utils::Req('POST', $api, [
				'type' => 'json',
				'datatype'=>'auto',
				// 'debug'=> true,
				'query' => ['access_token'=>$token],
				'data'=>[
					'path' => $path, 
					'width'=>$width,
					'line_color' => $line_color
				]
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		return $resp;
	}

	


	/**
	 * 使用 appid 和 secret 换取 AccessToken
	 * @param  [type] $appid     企业ID
	 * @param  [type] $secret 应用的凭证密钥
	 * @return 成功返回 token , 失败抛异常
	 */
	function getAccessToken( $appid=null, $secret=null ) {
		

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;

		if ( empty($secret) ) {
			throw new Excp("缺少应用配置信息", 400, [
				"Wxapp::conf"=>$this->conf, 
				'appid'=>$appid, 
				'secret'=>$secret ]);
		}

		$mem = new Mem;
		$cappid = md5( "{$appid}{$secret}" );
		$cache = "Wxapp:{$cappid}:token";
		$token = $mem->get($cache);

		if ( $token  !== false && !empty($token) ) {
			return $token;
		}

		//从缓存中读取
		$api = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential";
		$resp = Utils::Req('GET', $api, [
			'type' => 'json',
			'query' => ['appid'=>$appid, 'secret'=>$secret]
		]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}


		$token = $resp['access_token'];
		$expires = intval($resp['expires_in']) - 100;
		$mem->set($cache, $token, $expires );// 写入缓存
		return $token;
	}



	/**
	 * 使用 appid 和 secret  换取卡券 (wx_card) Ticket
	 * @param  [type] $appid  小程序ID
	 * @param  [type] $secret 应用的凭证密钥
	 * @return 成功返回 Ticket , 失败抛异常
	 */
	function getWxcardTicket(  $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;    // &appid={$this->appid}&secret={$this->secret}
		$mem = new Mem;
		$cappid = md5( $appid . $secret);
		$cache = "Wxapp:{$cappid}:wxcard_ticket";
		$ticket = $mem->get($cache);
		if ( $ticket  !== false ) {
			return $ticket;
		}

		$access_token = $this->getAccessToken( $appid, $secret );

		$api = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
		$resp = Utils::Req('GET', $api, [
				'type' => 'json',
				'query' => ['access_token'=>$access_token,"type"=>"wx_card"]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'access_token'=>$access_token, 'resp'=>$resp] 
			);
		}

		$ticket = $resp['ticket'];
		$expires = intval($resp['expires_in']) - 100;
		$mem->set($cache, $ticket, $expires );// 写入缓存

		return $ticket;
	}


	function getCardExt( $card, $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;  
		$card['api_ticket'] = $this->getWxcardTicket( $appid, $secret );
		$card['timestamp'] = strval(time());
    	$card['nonce_str'] = utils::genStr(16);
		$string = '';
		$vals = [];
		foreach ($card as $v) {
			array_push($vals, $v);
		}
		sort($vals);
		$string = implode('', $vals );

		$signature = sha1($string);
    	$cardExt = $card;
    	$cardExt['signature'] = $signature;
    	$cardExt['string']  = $string;

	    return $cardExt; 
	}




	/**
	 * 使用 appid 和 secret  换取 JSapi Ticket
	 * @param  [type] $appid     企业ID
	 * @param  [type] $secret 应用的凭证密钥
	 * @return 成功返回 Ticket , 失败抛异常
	 */
	function getJSapiTicket(  $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;    // &appid={$this->appid}&secret={$this->secret}
		$mem = new Mem;
		$appid = md5( $appid . $secret);
		$cache = "Wxapp:{$appid}:jsapi_ticket";
		$ticket = $mem->get($cache);
		if ( $ticket  !== false ) {
			return $ticket;
		}

		$access_token = $this->getAccessToken( $appid, $secret );

		$api = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket";
		$resp = Utils::Req('GET', $api, [
				'type' => 'json',
				'query' => ['access_token'=>$access_token]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		$ticket = $resp['ticket'];
		$expires = intval($resp['expires_in']) - 100;
		$mem->set($cache, $ticket, $expires );// 写入缓存

		return $ticket;
	}


	
	/**
	 * 获取 JSAPI 需要的签名数据
	 * 
	 * @param  [type] $url        企业地址
	 * @param  [type] $appid     企业ID
	 * @param  [type] $secret 应用的凭证密钥
	 * @return  Array 签名信息数组 { appid: "appid", noncestr: "noncestr", timestamp:timestamp ,url: "地址",signature: "签名",rawstring:签名原始串"}
	 */
	function getJSapiSignature( $url=null , $appid=null, $secret=null ) {
		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;  
		$jsapiTicket = $this->getJSapiTicket( $appid, $secret );
		
		if ( $url === null ){

			if ( isset($_SERVER['HTTP_TUANDUIMAO_LOCATION']) ) {
				$url = strtolower($_SERVER['HTTP_TUANDUIMAO_LOCATION']);
			} else if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] )) {

				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
				$url = "{$protocol}{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
			}

		} else {
			$url = strtolower($url);
		}

		$timestamp = time();
    	$nonceStr = utils::genStr(16);
   
    	$string = "jsapi_ticket={$jsapiTicket}&noncestr=$nonceStr&timestamp=$timestamp&url=$url"; // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    	$signature = sha1($string);

    	$signPackage = [
	      "appid"     => $appid,
	      "noncestr"  => $nonceStr,
	      "timestamp" => $timestamp,
	      "url"       => $url,
	      "signature" => trim($signature),
	      "rawstring" => trim($string),
	    ];


	    return $signPackage; 
	}





	/**
	 * 获取 OAuth 授权地址
	 * @param  [type] $redirect_uri [description]
	 * @param  [type] $appid        [description]
	 * @return [type]               [description]
	 */
	function getAuthUrl( $redirect_uri, $appid=null ) {	

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;

		// $redirect_uri = urlencode("{$redirect_uri}");
		@session_start();
		$state = md5( time() . mt_rand(0,100) );
		$_SESSION["Wxapp:auth:$appid:state"] = $state;


		// Auto Set URL
		if ( strpos($redirect_uri, "http")  !== 0 ) {

			$protocol = "http://";

			if ( isset($_SERVER['HTTP_TUANDUIMAO_LOCATION']) ) {
				$url = strtolower($_SERVER['HTTP_TUANDUIMAO_LOCATION']);
			} else if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] )) {

				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
				$url = "{$protocol}{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
			}

			$urlr = parse_url($url);

			if ( strpos($redirect_uri, "/")  !== 0 ) {
				$redirect_uri = "{$url}{$redirect_uri}";
			} else {
				$redirect_uri = "{$protocol}{$urlr['host']}{$redirect_uri}";
			}

		}

		
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize";
		$url .= "?appid={$appid}";
		$url .= "&redirect_uri=" . urlencode($redirect_uri);
		$url .= "&response_type=code";
		$url .= "&scope=snsapi_base";
		$url .= "&state=$state";
		$url .= '#wechat_redirect';

		return $url;
	}


	/**
	 * 使用 oAuth2 返回的 code 换取用户信息
	 * @param  [type] $code       [description]
	 * @param  [type] $state      [description]
	 * @param  [type] $appid     [description]
	 * @param  [type] $secret [description]
	 * @return [type]             [description]
	 */
	function getUserByCode($code, $state, $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->conf['appid'] : $appid;
		$secret = ( $secret == null ) ? $this->conf['secret'] : $secret;

		@session_start();

		if ( $_SESSION["Wxapp:auth:$appid:state"] != $state ) {
			throw new Excp( 
				"非法请求 state 信息错误", 
				502,
				[
					'appid'=>$appid, 
					'secret'=>$secret, 
					'state'=>$state, 
					'_SESSION'=>$_SESSION
				] 
			);
		}


		$access_token = $this->getAccessToken($appid, $secret);
		$resp = Utils::Req("GET","https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo",
			[
				"query" => [
					"access_token" => $access_token,
					"code" => $code
				]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		return $this->getUser( $resp['UserId'], $appid, $secret );

	} 


	/**
	 *  获取用户资料
	 * 
	 * @param  [type] $userid [description]
	 * @return [type]         [description]
	 */
	function getUser( $userid, $appid=null , $secret =null ) {

		$access_token = $this->getAccessToken($appid, $secret);
		
		$resp = Utils::Req("GET","https://qyapi.weixin.qq.com/cgi-bin/user/get",
			[
				"query" => [
					"access_token" => $access_token,
					"userid" => $userid
				]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'],
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		unset($resp['errcode']);unset($resp['errmsg']);
		return $resp;

	}

	/**
	 *  读取部门详情列表
	 *  
	 * @return [type] [description]
	 */
	function getDepartment( $department_id = null , $appid=null , $secret =null )  {

		$access_token = $this->getAccessToken($appid, $secret);
		
		$resp = Utils::Req("GET","https://qyapi.weixin.qq.com/cgi-bin/department/list",
			[
				"query" => [
					"access_token" => $access_token,
					"id" => $department_id
				]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'],
				$resp['errcode'],
				['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp] 
			);
		}

		unset($resp['errcode']);unset($resp['errmsg']);
		return $resp["department"];

	}


	/**
	 * 读取用户详情列表
	 * 
	 * @return [type] [description]
	 */
	function getUserList( $department_id ) {

	}


	/**
	 * 通过微信media id读取文件
	 * @param  [type] $mediaid [description]
	 * @param  [type] $appid   [description]
	 * @param  [type] $secret  [description]
	 * @return [type]          [description]
	 */
	function getMedia($media_id, $appid=null, $secret=null){

		$access_token = $this->getAccessToken( $appid, $secret );
		$api = "https://qyapi.weixin.qq.com/cgi-bin/media/get";
		$resp = Utils::Req('GET', $api,  [
			'datatype'=>'auto',
			'query' => [
				'access_token'=>$access_token,
				'media_id'=>$media_id
			]

		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['mediaid'=>$mediaid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp['body'];
	}



	// 回调模式 API 列表
	// ================================================

	/**
	 * 回调模式地址验证
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	function verifyURL( $query ) {

		
		$wxcpt = new WXBizMsgCrypt($this->conf['token'], $this->conf['EncodingAESKey'], $this->conf['appid']);

		$returnStr = '';
		$error = $wxcpt->VerifyURL(
			$query['msg_signature'], 
			$query['timestamp'],
			$query['nonce'],
			$query['echostr'], $returnStr );

		if ( $error == 0 ) {

			return $returnStr;
		}

		throw new Excp( 
			ErrorCode::getMessage($error), 
			abs($error),
			["query"=>$query, "conf"=>$this->conf] 
		);
	}
	
}


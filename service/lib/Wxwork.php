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
 * XpmSE企业微信
 */
class Wxwork {

	private $conf = [];


	/**
	 * 应用配置信息
	 * @param array $option [description]
	 */
	function __construct( $conf = [] ) {

		$this->conf['corpid'] = isset($conf['corpid']) ?  $conf['corpid'] : '';
		$this->conf['secret'] = isset($conf['secret']) ?  $conf['secret'] : '';
		$this->conf['token'] = isset($conf['token']) ?  $conf['token'] : '';
		$this->conf['EncodingAESKey'] = isset($conf['EncodingAESKey']) ?  $conf['EncodingAESKey'] : '';

		if ( empty($this->conf['corpid']) ) {
			throw new Excp("缺少 corpid", 400, ["Wxwork::conf"=>$this->conf, "conf"=>$conf] );
		}

		if ( empty($this->conf['secret']) && empty($this->conf['token']) ) {
			throw new Excp("缺少应用配置信息", 400, ["Wxwork::conf"=>$this->conf, "conf"=>$conf] );
		}

	}



	/**
	 * 使用 Corpid 和 Corpsecret 换取 AccessToken
	 * @param  [type] $corpid     企业ID
	 * @param  [type] $corpsecret 应用的凭证密钥
	 * @return 成功返回 token , 失败抛异常
	 */
	function getAccessToken( $corpid=null, $corpsecret=null ) {

		$corpid = ( $corpid == null ) ? $this->conf['corpid'] : $corpid;
		$corpsecret = ( $corpsecret == null ) ? $this->conf['secret'] : $corpsecret;    

		if ( empty($corpsecret) ) {
			throw new Excp("缺少应用配置信息", 400, [
				"Wxwork::conf"=>$this->conf, 
				'corpid'=>$corpid, 
				'corpsecret'=>$corpsecret ]);
		}

		$mem = new Mem;
		$appid = md5( "{$corpid}{$corpsecret}" );
		$cache = "wxwork:{$appid}:token";
		$token = $mem->get($cache);
		if ( $token  !== false ) {
			return $token;
		}

		//从缓存中读取
		$api = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
		$resp = Utils::Req('GET', $api, [
				'type' => 'json',
				'query' => ['corpid'=>$corpid, 'corpsecret'=>$corpsecret]
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp] 
			);
		}


		$token = $resp['access_token'];
		$expires = intval($resp['expires_in']) - 100;
		$mem->set($cache, $token, $expires );// 写入缓存
		return $token;
	}



	/**
	 * 使用 Corpid 和 Corpsecret  换取 JSapi Ticket
	 * @param  [type] $corpid     企业ID
	 * @param  [type] $corpsecret 应用的凭证密钥
	 * @return 成功返回 Ticket , 失败抛异常
	 */
	function getJSapiTicket(  $corpid=null, $corpsecret=null ) {

		$corpid = ( $corpid == null ) ? $this->conf['corpid'] : $corpid;
		$corpsecret = ( $corpsecret == null ) ? $this->conf['secret'] : $corpsecret;    // &appid={$this->appid}&secret={$this->secret}
		$mem = new Mem;
		$appid = md5( $corpid . $corpsecret);
		$cache = "wxwork:{$appid}:jsapi_ticket";
		$ticket = $mem->get($cache);
		if ( $ticket  !== false ) {
			return $ticket;
		}

		$access_token = $this->getAccessToken( $corpid, $corpsecret );

		$api = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket";
		$resp = Utils::Req('GET', $api, [
				'type' => 'json',
				'query' => ['access_token'=>$access_token]
			]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			throw new Excp( 
				$resp['errmsg'], 
				$resp['errcode'],
				['corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp] 
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
	 * @param  [type] $corpid     企业ID
	 * @param  [type] $corpsecret 应用的凭证密钥
	 * @return  Array 签名信息数组 { appid: "corpid", noncestr: "noncestr", timestamp:timestamp ,url: "地址",signature: "签名",rawstring:签名原始串"}
	 */
	function getJSapiSignature( $url=null , $corpid=null, $corpsecret=null ) {
		$corpid = ( $corpid == null ) ? $this->conf['corpid'] : $corpid;
		$corpsecret = ( $corpsecret == null ) ? $this->conf['secret'] : $corpsecret;  
		$jsapiTicket = $this->getJSapiTicket( $corpid, $corpsecret );
		
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
	      "appid"     => $corpid,
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
	function getAuthUrl( $redirect_uri, $corpid=null ) {	

		$corpid = ( $corpid == null ) ? $this->conf['corpid'] : $corpid;

		// $redirect_uri = urlencode("{$redirect_uri}");
		@session_start();
		$state = md5( time() . mt_rand(0,100) );
		$_SESSION["wxwork:auth:$corpid:state"] = $state;


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
		$url .= "?appid={$corpid}";
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
	 * @param  [type] $corpid     [description]
	 * @param  [type] $corpsecret [description]
	 * @return [type]             [description]
	 */
	function getUserByCode($code, $state, $corpid=null, $corpsecret=null ) {

		$corpid = ( $corpid == null ) ? $this->conf['corpid'] : $corpid;
		$corpsecret = ( $corpsecret == null ) ? $this->conf['secret'] : $corpsecret;

		@session_start();

		if ( $_SESSION["wxwork:auth:$corpid:state"] != $state ) {
			throw new Excp( 
				"非法请求 state 信息错误", 
				502,
				[
					'corpid'=>$corpid, 
					'corpsecret'=>$corpsecret, 
					'state'=>$state, 
					'_SESSION'=>$_SESSION
				] 
			);
		}


		$access_token = $this->getAccessToken($corpid, $corpsecret);
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
				['corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp] 
			);
		}

		return $this->getUser( $resp['UserId'], $corpid, $corpsecret );

	} 


	/**
	 *  获取用户资料
	 * 
	 * @param  [type] $userid [description]
	 * @return [type]         [description]
	 */
	function getUser( $userid, $corpid=null , $corpsecret =null ) {

		$access_token = $this->getAccessToken($corpid, $corpsecret);
		
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
				['corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp] 
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
	function getDepartment( $department_id = null , $corpid=null , $corpsecret =null )  {

		$access_token = $this->getAccessToken($corpid, $corpsecret);
		
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
				['corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp] 
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
	function getMedia($media_id, $corpid=null, $corpsecret=null){

		$access_token = $this->getAccessToken( $corpid, $corpsecret );
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

		
		$wxcpt = new WXBizMsgCrypt($this->conf['token'], $this->conf['EncodingAESKey'], $this->conf['corpid']);

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


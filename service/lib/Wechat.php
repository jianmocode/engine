<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/wechat-encoder/WXBizMsgCrypt.php');

use \Exception as Exception;
use \DOMDocument as DOMDocument;

use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;

use \Wechat\Encoder\WXBizMsgCrypt as WXBizMsgCrypt;
use \Wechat\Encoder\ErrorCode as ErrorCode;


/**
 * XpmSE微信公众号SDK
 */
class Wechat {

	private $appid=null;
	private $secret=null;
	private $encrypt_type=1;  // 消息加密方式 1 明文 2 兼容 3 安全 (微信公众号后台设定)
	private $aes=null;        // EncodingAESKey (微信公众号后台设定)
	private $token=null;      // Token (微信公众号后台设定)
	private $c = [];
	private $ut = null;

	/**
	 * 微信SD
	 * @param array $conf [description]
	 */
	function __construct( $conf = [] ) {

		if ( is_array($conf) ) {
			if ( !isset($conf['appid']) || !isset($conf['secret']) ) {
				$conf = Conf::G('wechat');
				if ( !is_array($conf) ) {
					throw new Excp('缺少配置信息', '404');
				}
				$conf = end($conf);
				if ( !isset($conf['appid']) || !isset($conf['secret']) ) {
					throw new Excp('缺少配置信息', '404');
				}
			}

		} else if ( is_string( $conf) ) {
			$conf = Conf::G("wechat/$conf");
			if ( !is_array($conf) &&  !isset($conf['appid']) || !isset($conf['secret'])  ) {
				throw new Excp('缺少配置信息', '404');
			}
		}

		$this->ut = new Utils;
		$this->c = $conf;
		$this->appid = $conf['appid'];
		$this->secret = $conf['secret'];

		if ( !empty($conf['encrypt_type']) ) {
			$this->encrypt_type = $conf['encrypt_type'];
		}

		if ( !empty($conf['token']) ) {
			$this->token = $conf['token'];
		}

		if ( !empty($conf['aes']) ) {
			$this->aes = $conf['aes'];
		}

	}





	/**
	 * 读取当前配置信息
	 * @return [type] [description]
	 */
	function info() {
		return $this->c;
	}


	/**
	 *  换取 AccessToken ( 使用 AppID 和 AppSecret )
	 *  
	 * @param  [type] $appid  微信应用 AppID 默认为NULL, 从配置文件中读取
	 * @param  [type] $secret 微信应用 AppSecret  默认为NULL, 从配置文件中读取
	 * @return 成功返回 string $token 失败返回 Err 对象
	 */
	function getAccessToken( $appid=null, $secret=null, $nocache = false ) {

		$appid = ( $appid == null ) ? $this->appid : $appid;
		$secret = ( $secret == null ) ? $this->secret : $secret;    // &appid={$this->appid}&secret={$this->secret}
		$mem = new Mem;
		$cache = "wechat:$appid:token";
		$token = $mem->get($cache);
		if ( $token  !== false && $nocache === false) {
			return $token;
		}

		//从缓存中读取
		$api = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential";
		$resp = $this->ut->Request('GET', $api, [
				'type' => 'json',
				'query' => ['appid'=>$appid, 'secret'=>$secret]
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		$token = $resp['access_token'];
		$expires = intval($resp['expires_in']) - 1000;
		$mem->set($cache, $token, $expires );// 写入缓存

		return $token;
	}


	/**
	 *  换取 Ticket ( 使用 AppID 和 AppSecret )
	 *
	 * @param  [type] $type   Ticket类型（有效值 jsapi,wx_card ... ) 参考微信文档
	 * @param  [type] $appid  微信应用 AppID 默认为NULL, 从配置文件中读取
	 * @param  [type] $secret 微信应用 AppSecret  默认为NULL, 从配置文件中读取
	 * @return 成功返回 string $token 失败返回 Err 对象
	 */
	function getTicket( $type="jsapi", $appid=null, $secret=null ) {

		$appid = ( $appid == null ) ? $this->appid : $appid;
		$secret = ( $secret == null ) ? $this->secret : $secret;    // &appid={$this->appid}&secret={$this->secret}
		$mem = new Mem;
		$cache = "wechat:{$appid}:ticket:{$type}";
		$ticket = $mem->get($cache);
		if ( $ticket  !== false ) {
			return $ticket;
		}

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
		$resp = $this->ut->Request('GET', $api, [
				'type' => 'json',
				'query' => ['access_token'=>$access_token, 'type'=>$type]
			]);


		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		$ticket = $resp['ticket'];
		$expires = intval($resp['expires_in']) - 100;

		$mem->set($cache, $ticket, $expires );// 写入缓存
		return $ticket;
	}



	/**
	 * 读取网页授权 AccessToken 
	 * @param  [type] $code  [description]
	 * @param  [type] $state [description]
	 * @return [type]        [description]
	 */
	public function getAuthAccessToken( $code, $state, $sid=null, $appid=null ) {
		
		$appid = ( $appid == null ) ? $this->appid : $appid;
		if ( $sid == null) {
			@session_start();
			$sid = session_id();
		}
		$mem = new Mem;
		$cache = "wechat:{$appid}:auth:{$sid}";

		// if ( $state != $mem->get( "$cache:state") ) {
		// 	throw new Excp("微信服务接口:错误的状态码 state={$state}", "503", [ 'sid'=>$sid, 'code'=>$code, 'state'=>$state, 'appid'=>$appid, 'session'=>$_SESSION] );	
		// }

		$access_token = $mem->getJSON( "$cache:access_token");
		if ( $access_token !== false ) {
			return $access_token;
		}

		$api = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		$resp = $this->ut->Request('GET', $api, [
			'query'=> [
				'appid'=>$this->appid,
				'secret'=>$this->secret,
				'code' => $code,
				'grant_type' => 'authorization_code' ]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['code'=>$code, 'state'=>$state, 'appid'=>$appid , 'resp'=>$resp]);
		}

		// $_SESSION["wehcat/auth/$appid/token"] = $resp;
		$mem->setJSON( "$cache:access_token", $resp, intval($resp['expires_in']) -100 );
		return $resp;
	}



	function authUrl( $redirect_uri,  $sid=null, $appid = null ) {	

		$appid = ( $appid == null ) ? $this->appid : $appid;
		
		if ( $sid == null) {
			@session_start();
			$sid = session_id();
		}

		$state = md5( time() . mt_rand(0,100) );

		$mem = new Mem;
		$cache = "wechat:{$appid}:auth:{$sid}";
		$mem->set( "$cache:state", $state);

		$url = "https://open.weixin.qq.com/connect/oauth2/authorize";
		$url .= "?appid={$appid}";
		$url .= "&redirect_uri=". urlencode( $redirect_uri );
		$url .= "&response_type=code";
		$url .= "&scope=snsapi_userinfo";
		$url .= "&state=$state";
		$url .= '#wechat_redirect';

		return $url;
	}


	/**
	 * 获得公众号授权链接
	 * @param  [type] $goto [description]
	 * @return [type]       [description]
	 */
	function getAuthUrl( $goto, $redirect_uri=null, $appid=null ) {	

		$appid = ( $appid == null ) ? $this->appid : $appid;
		$goto = urlencode($goto);
		$redirect_uri = ( isset($this->c['redirect_uri']) ) ? $this->c['redirect_uri'] : "";
		$redirect_uri = "{$redirect_uri}{$goto}";
		@session_start();
		$state = md5( time() . mt_rand(0,100) );
		$_SESSION["wehcat/auth/$appid/state"] = $state;		

		$url = "https://open.weixin.qq.com/connect/oauth2/authorize";
		$url .= "?appid={$appid}";
		$url .= "&redirect_uri=". urlencode( $redirect_uri );
		$url .= "&response_type=code";
		$url .= "&scope=snsapi_userinfo";
		$url .= "&state=$state";
		$url .= '#wechat_redirect';

		return $url;
	}


	/**
	 * 获得网站登录授权链接
	 */
	function getWebAuthUrl( $goto, $redirect_uri=null, $appid=null ) {
		
		$appid = ( $appid == null ) ? $this->appid : $appid;
		$goto = urlencode($goto);
		$redirect_uri = ( isset($this->c['redirect_uri']) ) ? $this->c['redirect_uri'] : "/";
		$redirect_uri = "{$redirect_uri}{$goto}";

		@session_start();
		$state = md5( time() . mt_rand(0,100) );
		$_SESSION["wehcat/auth/$appid/state"] = $state;
		$url = urlencode($redirect_uri);
		return $url;
	}

	/**
	 * 读取Auth授权 state
	 * @param  [type] $appid [description]
	 * @return [type]        [description]
	 */
	function getAuthState( $appid=null , $sid=null ) {

		$appid = ( $appid == null ) ? $this->appid : $appid;
		if ( $sid == null) {
			@session_start();
			$sid = session_id();
		}

		$mem = new Mem;
		$cache = "wechat:{$appid}:auth:{$sid}";
		return $mem->get("$cache:state");
	}



	/**
	 * 读取当前会话的用户资料 (网页授权)
	 * @return [type] [description]
	 */
	function getAuthUser( $code=null, $state=null, $sid=null, $appid=null ) {

		$appid = ( $appid == null ) ? $this->appid : $appid;
		if ( $sid == null) {
			@session_start();
			$sid = session_id();
		}

		$mem = new Mem;
		$cache = "wechat:{$appid}:auth:{$sid}";

		if ( $code != null && $state != null ) {
			$this->getAuthAccessToken( $code, $state, $sid, $appid );
		}

		$access_token = $mem->getJSON( "$cache:access_token");
		if ( $access_token === false ) {
			throw new Excp('用户授权认证:未授权无法读取OAuthToken', '503', ['appid'=>$appid, 'session'=>$_SESSION]);	
		}

		$user = $mem->getJSON( "$cache:user");
		if ( $user !== false ) {
			return $user;
		}

		// 用户缓存Token信息
		$token =  $mem->getJSON( "$cache:access_token");
		$access_token = $token['access_token'];
		$openid = $token['openid'];

		$api = 'https://api.weixin.qq.com/sns/userinfo';
		$resp = $this->ut->Request('GET', $api,['query'=>[
			'access_token'=>$access_token,
			'openid'=>$openid,
			'lang' => 'zh_CN']
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['appid'=>$appid , 'resp'=>$resp, 'session'=>$_SESSION]);
		}

		$mem->setJSON( "$cache:user", $resp );
		return $resp;
	}


	/**
	 * 清空Wechat登录信息
	 */
	function cleanAuthSession( $appid = null , $sid=null) {

		$appid = ( $appid == null ) ? $this->appid : $appid;
		if ( $sid == null) {
			@session_start();
			$sid = session_id();
		}

		$mem = new Mem;
		$cache = "wechat:{$appid}:auth:{$sid}";
		return $mem->delete( $cache );
	}



	/**
	 * 读取带参二维码链接
	 */
	function getQrcodeURL( $option, $appid=null, $secret=null) {
		$access_token = $this->getAccessToken( $appid, $secret, true );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		// 参数默认值
		// 有效期默认值 30 秒
		$option['expire_seconds'] = !empty($option['expire_seconds']) ? $option['expire_seconds'] : 30;

		// 二维码类型，QR_SCENE 为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
		$option['action_name'] = !empty($option['action_name']) ? $option['action_name'] : 'QR_STR_SCENE';

		// 二维码详细信息
		$option['action_info'] = !empty($option['action_info']) ? $option['action_info'] : null;

		// 场景值ID，临时二维码时为32位非0整型，永久二维码时最大值为100000（目前参数只支持1--100000）
		// $option['scene_id'] = !empty($option['scene_id']) ? $option['scene_id'] : 1;

		// 场景值ID（字符串形式的ID），字符串类型，长度限制为1到64  
		// $option['scene_str'] = !empty($option['scene_str']) ? $option['scene_str'] : null;


		$api = "https://api.weixin.qq.com/cgi-bin/qrcode/create";
		$resp = $this->ut->Request('POST', $api,  [
			'query' => [
				'access_token'=>$access_token,
			],
			"type" => "json",
			'data' => $option
		]);

		if ( !isset($resp['ticket'])  ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['option'=>$option,'resp'=>$resp]);
		}

		$resp['showqrcode'] = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . $resp['ticket'];

		return $resp;

	}




	/**
	 * 读取用户资料 (已订阅/已授权的用户)
	 * @param  [type] $openid 用户 OpenID
	 * @param  [type] $appid  微信应用 AppID 默认为NULL, 从配置文件中读取
	 * @param  [type] $secret 微信应用 AppSecret  默认为NULL, 从配置文件中读取
	 * @return 成功返回 array $user 失败返回 Err 对象
	 */
	function getUser( $openid, $appid=null, $secret=null ) {
		
		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/cgi-bin/user/info";
		$resp = $this->ut->Request('GET', $api,  [
			'query' => [
				'access_token'=>$access_token,
				'openid'=>$openid,
				'lang' => 'zh_CN' ]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
            throw new Excp($resp['errmsg'], $resp['errcode'],  ['openid'=>$openid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
			// return new Err($resp['errcode'], $resp['errmsg'], ['openid'=>$openid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp;
	}



	/**
	 * 设定公众号菜单
	 * 
	 * @param [type] $menu [description]
	 */
	function setMenu( $menu, $appid=null, $secret=null){
		
		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$data = is_string($menu) ? json_decode($menu, true) : $menu;


		$api = "https://api.weixin.qq.com/cgi-bin/menu/create";
		$resp = $this->ut->Request('POST', $api,  [
			'type'=>'raw',
			'datatype'=>'json',
			'query' => [
				'access_token'=>$access_token,
			],
			'data' => $data

		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['mediaid'=>$mediaid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp;

	}


	/**
	 * 发送模板消息 
	 * @param  array $data  模板消息数据
	 * @param  string  $appid  [description]
	 * @param  string  $secret [description]
	 * @return array $response 
	 * 
	 */
	function templateMessageSend( $data,  $appid=null, $secret=null ) {

		$token = $this->getAccessToken($appid, $secret);
		$api = "https://api.weixin.qq.com/cgi-bin/message/template/send";
		$resp = Utils::Req('POST', $api, [
				'type' => 'json',
				'datatype'=>'json',
				// 'debug'=> true,
				'query' => ['access_token'=>$token],
				'data'=> $data
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
	 * 通过微信id读取文件
	 * @param  [type] $mediaid [description]
	 * @param  [type] $appid   [description]
	 * @param  [type] $secret  [description]
	 * @return [type]          [description]
	 */
	
	function getMedia($mediaid,$appid=null,$secret=null){


		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		
		$api = "https://api.weixin.qq.com/cgi-bin/media/get";
		$resp = $this->ut->Request('GET', $api,  [
			'datatype'=>'auto',
			'query' => [
				'access_token'=>$access_token,
				'media_id'=>$mediaid
			]

		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['mediaid'=>$mediaid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp['body'];
	}



	/**
	 * 读取素材列表
	 * @param  integer $offset 从全部素材的该偏移位置开始返回，0表示从第一个素材 默认 0
	 * @param  integer $count  返回素材的数量，取值在1到20之间 默认 20
	 * @param  string  $type   素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news） 默认 news
	 * @return [type]          [description]
	 */
	function searchMedia( $offset=0, $count=20, $type='news', $appid=null, $secret=null ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}
		
		$api = "https://api.weixin.qq.com/cgi-bin/material/batchget_material";
		$resp = $this->ut->Request('POST', $api,  [
			'datatype'=>'json',
			'type' => 'json',
			'query' => [
				'access_token'=>$access_token
			],
			'data' => [
				"type"=>$type,
				"count"=>$count,
				"offset"=>$offset
			]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['mediaid'=>$mediaid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp;
	}


	/**
	 * 读取素材总数
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function countMedia( $appid=null, $secret=null ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}
		
		$api = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount";
		$resp = $this->ut->Request('GET', $api,  [
			'datatype'=>'json',
			'type' => 'json',
			'query' => [
				'access_token'=>$access_token
			]
		]);

		if ( isset($resp['errcode']) &&  $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['mediaid'=>$mediaid, 'appid'=>$appid, 'secret'=>$secret, 'resp'=>$resp]);
		}

		return $resp;
	}




	/**
	 * 上传一张图片到微信媒体服务器
	 * @param  binary $buffer 二进制文件
	 * @param  array  $opt   $opt['filename']: 文件名 ， $opt['mimetype'] mimetype;
	 * @param  string $appid  [description]
	 * @param  string $secret [description]
	 * @return array  微信 API 返回结果
	 */
	function uploadImage( $buffer, $opt = [], $appid=null, $secret=null ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$opt = is_array($opt) ? $opt : [];
		$opt['filename'] = !empty($opt['filename']) ? $opt['filename'] : "filename.jpg";
		$opt['mimetype'] = !empty($opt['mimetype']) ? $opt['mimetype'] : "image/jpeg";


		$api = "https://api.weixin.qq.com/cgi-bin/media/uploadimg";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'media',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> [
				"__files" => [
					[
						"name"=>"media",
						"mimetype"=>$opt['mimetype'],
						"filename"=>$opt['filename'],
						"data" => $buffer,
					]
				]
			]
		]);

		return $resp;
	}


	/**
	 * 创建卡券
	 * @param  [type] $data   [description]
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function cardCreate( $data, $appid=null, $secret=null  ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/card/create";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'raw',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> $data
		]);

		return $resp;
	}



	/**
	 * 更新卡券
	 * @param  [type] $data   [description]
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function cardUpdate( $data, $appid=null, $secret=null  ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/card/update";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'raw',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> $data
		]);

		return $resp;
	}


	/**
	 * 添加白名单用户
	 * @param  [type] $userlist [description]
	 * @return [type]           [description]
	 */
	function cardTestwhitelist( $userlist, $appid=null, $secret=null ) {

		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/card/testwhitelist/set";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'raw',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> $userlist
		]);

		return $resp;


	}



	// 此函数有异常 (暂时无法使用)
	function cardUpdateFiliter( $data ) {
		$allow = ["logo_url","notice","description","service_phone","color","location_id_list","center_title","center_sub_title","center_url","location_id_list","custom_url_name","custom_url","custom_url_sub_title","promotion_url_name","promotion_url","_sub_title","code_type","get_limit","can_share","can_give_friend","date_info","type","begin_timestamp","end_timestamp","bonus_cleared","bonus_rules","balance_rules","prerogative","custom_field1","custom_field2","custom_field3","name_type","url","custom_cell","detail","departure_time","landing_time","gate","boarding_time","guide_url","map_url"];

		foreach ($data as $key => $val) {
			if ( !is_array($val)  && !in_array($key, $allow) ) {
				unset( $data[$key] );
			}
		}

		return $data;

	}



	/**
	 * 拉取卡券详情信息
	 * @param  [type] $card_id [description]
	 * @return [type]          [description]
	 */
	function cardGet( $card_id ) {
		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}

		$api = "https://api.weixin.qq.com/card/get";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'raw',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> ["card_id"=>$card_id]
		]);

		return $resp;
	}


	/**
	 * 批量拉取卡券信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	function cardBatchget( $option ) {
		$access_token = $this->getAccessToken( $appid, $secret );
		if ( is_a($access_token, '\Xpmse\Err') ) {
			return $access_token;
		}
		$api = "https://api.weixin.qq.com/card/batchget";
		$resp = $this->ut->Request('POST', $api,  [
			// 'debug' => true,
			'datatype'=>'json',
			'type' => 'raw',
			'query' => [
				'access_token'=>$access_token
			],
			"data"=> $option
		]);

		return $resp;
	}



	/**
	 *  获取签名信息 ( 使用 AppID 和 AppSecret )
	 *  
	 * @param  [type] $appid  微信应用 AppID 默认为NULL, 从配置文件中读取
	 * @param  [type] $secret 微信应用 AppSecret  默认为NULL, 从配置文件中读取
	 * @return 成功返回 string $token 失败返回 Err 对象
	 */		
	function getSignature( $url=null , $appid=null, $secret=null ) {

		$ut = new Utils;
		$jsapiTicket = $this->getTicket('jsapi', $appid, $secret );
		if( Err::isError($jsapiTicket)) {
			throw new Excp('Get jsapiTicket Error', 500, ['jsapiTicket'=>$jsapiTicket] );
		}

		if ( $url === null ){
			$url = Utils::getLocation();
		} else {
			$url = strtolower($url);
		}
		$timestamp = time();
    	$nonceStr = $ut->genString(16);
    	// $url = urlencode( $url );
   
    	$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url"; // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    	$signature = sha1($string);

  // 	  	echo "<pre>";
  // 	  	Utils::out("url=", $url, "\n");
  // 	  	Utils::out("jsapiTicket=", $jsapiTicket, "\n");
  // 	  	Utils::out("timestamp=", $timestamp, "\n");
  // 	  	Utils::out("nonceStr=", $nonceStr, "\n");
  // 	  	Utils::out("string=", $string, "\n");
  // 	  	Utils::out("signature=", $signature, "\n");
  // 	  	Utils::out("_SERVER=\n", $_SERVER, "\n");
		// echo "</pre>";


    	$signPackage = [
	      "appid"     => $this->appid,
	      "noncestr"  => $nonceStr,
	      "timestamp" => $timestamp,
	      "url"       => $url,
	      "signature" => trim($signature),
	      // "rawstring" => trim($string),
	    ];
	    return $signPackage; 
	}




	static public function getHandlers() {
		$option = new \Xpmse\Option();
		$handlers = $option->get("wechat/handlers");
		if ( $handlers === null ){
			$option->register(
			    "微信消息处理器",
			    "wechat/handlers", 
			    []
			);

			return [];
		}

		return $handlers;
	}



	/**
	 * 微信推送消息-增加消息处理器
	 */
	static public function bind( $app, $api ) {
		$option = new \Xpmse\Option();
		$handlers = $option->get("wechat/handlers");

		if ( $handlers === null ){
			$handlers[$app][$api] = true;
			$option->register(
			    "微信消息处理器",
			    "wechat/handlers", 
			    $handlers
			);

			return;
		}
		

		$handlers[$app][$api] = true;
		$option->set("wechat/handlers", $handlers);
	}


	/**
	 * 微信推送消息-删除消息处理器
	 * @param  [type] $app [description]
	 * @param  [type] $api [description]
	 * @return [type]      [description]
	 */
	static public function unbind( $app, $api=null ) {
		$option = new \Xpmse\Option();
		$handlers = $option->get("wechat/handlers");

		if ( $handlers === null ){
			$option->register(
			    "微信消息处理器",
			    "wechat/handlers", 
			    []
			);
			return;
		}

		if ( $api == null) {
			unset($handlers[$app]);
		} else {
			unset($handlers[$app][$api]);
		}

		$option->set("wechat/handlers", $handlers);
	}


	/**
	 * 微信推送消息-签名验证
	 * @return [type] [description]
	 */
	function checkSignature( $signature, $timestamp, $nonce, $token=null ) {

		$token = ( $token == null ) ? $this->token : $token;

		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}



	



	/**
	 * 微信推送消息-解密消息
	 * @param  [type] $query   [description]
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	function decrypt( $query, $message, $aes=null,  $token=null, $appid=null ) {
		
		$appid = ( $appid == null ) ? $this->appid : $appid;
		$token = ( $token == null ) ? $this->token : $token;
		$encodingAesKey = ( $aes == null ) ? $this->aes : $aes;

		if ( $query['encrypt_type'] != "aes" ) {
			return $message;
		}

		$pc = new WXBizMsgCrypt($token, $encodingAesKey, $appid);
		$msg_signature = $query['msg_signature'];
		$timestamp = intval($query['timestamp']);
		$nonce =$query['nonce'];

		if ( empty($msg_signature) ) {
			throw new Excp("未知消息签名信息", 402, ['query'=>$query]);
		}

		if ( empty($nonce) ) {
			throw new Excp("未知随机数信息", 402, ['query'=>$query]);
		}

		if ( empty($timestamp) ) {
			throw new Excp("未知时间戳", 402, ['query'=>$query]);
		}

		if ( (time() - $timestamp)  >  5 ) {
			throw new Excp("消息已过期", 402, ['query'=>$query]);
		}

		$msg = "";
		$errcode = $pc->DecryptMsg($msg_signature, $timestamp, $nonce, $message, $msg);
		if ($errcode != 0) {
			throw new Excp("消息解密失败", 402, ['errcode'=>$errcode, 'query'=>$query, 'message'=>$message]);
		}

		return $msg;
	}


	/**
	 * 微信推送消息-加密消息
	 * @param  [type] $message [description]
	 * @param  [type] $aes     [description]
	 * @param  [type] $token   [description]
	 * @param  [type] $appid   [description]
	 * @param  [type] $secret  [description]
	 * @return [type]          [description]
	 */
	function encrypt( $message, $aes=null,  $token=null, $appid=null ) {
		
		$appid = ( $appid == null ) ? $this->appid : $appid;
		$token = ( $token == null ) ? $this->token : $token;
		$encodingAesKey = ( $aes == null ) ? $this->aes : $aes;

		$pc = new WXBizMsgCrypt($token, $encodingAesKey, $appid);
		$timestamp = time();
		$nonce =Utils::genNum(10);

		$encryptmsg = '';
		$errcode = $pc->EncryptMsg($message, $timestamp, $nonce, $encryptmsg);

		if ($errcode != 0) {
			throw new Excp("消息加密失败", 402, ['errcode'=>$errcode, 'timestamp'=>$timestamp, 'nonce'=>$nonce, 'message'=>$message]);
		}

		return $encryptmsg;
	}



	function replyText( $from, $to, $text ) {

		$message = $this->arrayToMessage([
			"ToUserName" => $to,
			"FromUserName" => $from,
			"CreateTime" => time(),
			"MsgType" =>"text",
			"Content" => $text
		]);


		return $message;
	}




	/**
	 * 微信推送消息-解出消息信息
	 * @param  string $message 
	 * @return  $array
	 */
	function messageToArray( $xmltext ) {

		$node = new DOMDocument();
		$node->loadXML($xmltext);
		$node = $node->firstChild;
		$resp = [];
		if ( $node != null ) {
			foreach ($node->childNodes as $childNode) {
				$name = $childNode->nodeName;
				$value = trim($childNode->nodeValue);
				if ( $name == '#text') {
					continue;
				}
				$resp[$name] = $value;
			}
		}
		return $resp;
	}


	/**
	 * 微信推送消息-生成推送消息
	 * @param  [type] $array [description]
	 * @return [type]        [description]
	 */
	function arrayToMessage( $array ) {

		$xml = "<xml>\n";
		foreach ($array as $tag => $value) {
			if ( is_string($value) ){
				$xml .= "<{$tag}><![CDATA[{$value}]]></${tag}>\n";
			} else {
				$xml .= "<{$tag}>{$value}</${tag}>\n";
			}
		}
		$xml .= '</xml>';
		return $xml;
	}

}











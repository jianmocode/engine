<?php
/**
 * Class Wechat
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \Yao\Http;
use \Yao\Redis;
use \Yao\Str;
use \Yao\Route\Request;

/**
 * 微信接口
 */
class Wechat {

    /**
     * 微信接口配置
     * 
     * @var array
     * 
     */
    private $config = [];


    /**
     * 将微信地区编码转化为标准编码
     * @param int $province 微信省份名称
     * @param int $city 微信城市编码
     * 
     * @return array [:province_code, :city_code, :town_code]
     */
    public static function area( $province, $city=null ) {
        return [null, null, null];
    }

    /**
     * 读取微信头像
     * @param string $url 头像地址
     * @param int $size 有效值 0、46、64、96、132数值可选，0代表640*640正方形头像
     */
    public static function headimg( $url, $size=0 ) {
        $uri = explode("/", $url);
        array_pop($uri);
        array_push($uri, $size );
        return implode("/", $uri );
    }


    /**
     * 微信接口配置
     */
    public function __construct( $config ) {
        $this->config = $config;
    }


    /**
     * 读取 JS-SDK Config
     * 
     * @param  string $url 引用JS-SDK的页面地址. 默认读取 Request URL
     * @param  string $appid  微信应用 appid 默认为NULL, 从配置文件中读取
	 * @param  string $appsecret 微信应用 appsecret  默认为NULL, 从配置文件中读取
     * 
	 * @return array  微信JS-SDK Config接口注入权限验证配置
     * @throws Excp 
     */
    function jssdkConfig( $url=null, $appid=null, $appsecret=null ) {
        
        $appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        if ( empty($url) ) {
            $url = Request::url();
        }
        $jsapi_ticket = $this->jsapiTicket( false, $appid, $appsecret );
        $timestamp = time();
    	$nonce_str = Str::uniqid();
    	$origin_str = "jsapi_ticket={$jsapi_ticket}&noncestr={$nonce_str}&timestamp={$timestamp}&url={$url}"; // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    	$signature = sha1($origin_str);
        $config = [
            "appid"     => $appid,
            "noncestr"  => $nonce_str,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => trim($signature),
            // "rawstring" => trim($string),
        ];
        return $config; 
    }


    /**
	 * 读取 JSAPI Ticket 
     * 
     * Redis cache key: wechat:jsapi_ticket:[:appid]
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115
	 *
	 * @param  bool   $refresh 是否强制刷新, true=强制刷新, false=优先从缓存读取。默认值为 false
	 * @param  string $appid  微信应用 appid 默认为NULL, 从配置文件中读取
	 * @param  string $appsecret 微信应用 appsecret  默认为NULL, 从配置文件中读取
     * 
	 * @return string 成功返回 Access Token 
     * @throws Excp 
	 */
	function jsapiTicket( $refresh = false,  $appid=null, $appsecret=null ) {

		$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
		$appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        $appsecret = ( $appsecret == null ) ?  Arr::get($this->config, "appsecret") : $appsecret;
        
        $cache = "wechat:jsapi_ticket:{$appid}";
        
        //从缓存中读取
        if ( $refresh === false ){
            $jsapi_ticket = Redis::get($cache);
            if ( $jsapi_ticket ) {
                return $jsapi_ticket;
            }
        }

        $response = Http::get($url, [
            'query' => [
                "access_token" => $this->accessToken( false, $appid, $appsecret),
                "type" => "jsapi",
                "appid" => $appid,
                "secret" => $appsecret
            ]
        ]);

        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("读取微信 JSAPI Ticket 错误", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }
        
        $data = Http::json( $response );
		$jsapi_ticket = Arr::get($data, 'ticket');
        $ttl = intval(Arr::get($data,'expires_in', 0)) - 1000;
        
        if ( empty($jsapi_ticket) ) {
            throw Excp::create("读取微信 JSAPI Ticket, 微信服务器返回错误", 500, $data);
        }

		Redis::set($cache, $jsapi_ticket, $ttl );// 写入缓存
		return $jsapi_ticket;
	}



    /**
	 * 读取 Access Token
     * 
     * Redis cache key: wechat:access_token:[:appid]
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183
     * 
     * @param  bool   $refresh 是否强制刷新, true=强制刷新, false=优先从缓存读取。默认值为 false
	 * @param  string $appid  微信应用 appid 默认为NULL, 从配置文件中读取
	 * @param  string $appsecret 微信应用 appsecret  默认为NULL, 从配置文件中读取
     * 
	 * @return string 成功返回 Access Token 
     * @throws Excp 
	 */
	public function accessToken( $refresh = false,  $appid=null, $appsecret=null ) {

        $url = "https://api.weixin.qq.com/cgi-bin/token";
		$appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        $appsecret = ( $appsecret == null ) ?  Arr::get($this->config, "appsecret") : $appsecret;
        
        $cache = "wechat:access_token:{$appid}";
        
        //从缓存中读取
        if ( $refresh === false ){
            $access_token = Redis::get($cache);
            if ( $access_token ) {
                return $access_token;
            }
        }

        $response = Http::get($url, [
            'query' => [
                "grant_type" => "client_credential",
                "appid" => $appid,
                "secret" => $appsecret
            ]
        ]);

        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("读取微信 Access Token 错误", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }
        

        $data = Http::json( $response );
        $access_token = Arr::get($data, 'access_token');
        $ttl = intval(Arr::get($data,'expires_in', 0)) - 1000;
        if ( empty($access_token) ) {
            throw Excp::create("读取微信 Access Token, 微信服务器返回错误", 500, $data);
        }

		Redis::set($cache, $access_token, $ttl );// 写入缓存
		return $access_token;
	}
    


    /**
     * 读取Oauth2.0授权地址
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * 
     * 请求参数 `$params` :
     * 
     *  - :appid            string  公众号的唯一标识
     *  - :scope            string  应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且， 即使在未关注的情况下，只要用户授权，也能获取其信息 ）
     *  - :state            string  重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
     *  - :query            array   附加在 redirect_uri 上的查询参数     
     * 
     * @param array $params GET 请求参数
     * 
     * @return string 微博授权地址
     * 
     */
    public function authUrl( array $params=[] ) {

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize";

        // 处理数据转向
        $redirect_uri = Arr::get($this->config, "authback");
        $query = Arr::get($params, "query", []);
        array_map("urlencode", $query);
        $qs = http_build_query($query);
        if ( false === strpos($redirect_uri, "?")) {
            $redirect_uri = "{$redirect_uri}?{$qs}";
        } else {
            $redirect_uri = "{$redirect_uri}&{$qs}";
        }
        
        Arr::forget($params, "query");
        Arr::defaults( $params, [
            "appid" => $this->config["appid"],
            "redirect_uri" =>  $redirect_uri,  //urlencode($redirect_uri),
            "response_type" => "code",
            "state" => "VPIN",
            "scope" =>  Arr::get($this->config, "scope", "snsapi_userinfo")
        ]);
        
        $qs = http_build_query( $params );
        return "{$url}?{$qs}#wechat_redirect";
    }

    
    /**
     * 读取 Oauth2.0 Access Token
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     *  
     * 请求参数 `$params` :
     * 
     *  - :appid 申请应用时分配的AppKey。默认从 config 中读取。
     *  - :secret 申请应用时分配的AppSecret。 默认从 config 中读取。
     *  - :code 调用authorize获得的code值。
     * 
     * 返回数据结构 :
     * 
     *  - :access_token     微信 Access Token 
     *  - :expires_in       Token 过期时间
     *  - :refresh_token    用户刷新access_token
     *  - :openid           用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID
     *  - :scope            用户授权的作用域，使用逗号（,）分隔
     *  - :unionid          用户的唯一标识(微信全平台范围), 请注意只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
     * 
     * @param array $params 调用参数
     * 
     * @return array 微博用户唯一ID、Access Token、Token有效器等
     * 
     */
    public function oauthAccessToken( array $params ) {

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token";

        Arr::defaults( $params, [
            "appid" => $this->config["appid"],
            "secret" => $this->config["appsecret"],
            "grant_type" => "authorization_code"
        ]);

        $response = Http::post($url, ["form_params"=> $params]);
        $code = $response->getStatusCode();
        
        if ( $code != 200 ) {
            throw Excp::create("读取微信 OAuth Access Token 错误", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }
        
        return Http::json( $response );
    }

    /**
     * 读取微信用户资料
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * 
     * 返回数据关键字段
     * 
     *  - openid            用户的唯一标识(公众号范围)
     *  - unionid           用户的唯一标识(微信全平台范围), 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
     *  - nickname          微信用户昵称
     *  - sex               用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
     *  - province          用户个人资料填写的省份
     *  - city              普通用户个人资料填写的城市
     *  - country           国家，如中国为CN
     *  - headimgurl        用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
     *  - privilege         用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）
     *
     * @param string $wx_openid 用户的唯一标识(公众号范围)
     * @param string $access_token 微信OAuth2.0授权 access_token
     * 
     * @return array 微信用户资料
     */
    public function getUser( $wx_openid, $access_token ) {

        $url = "https://api.weixin.qq.com/sns/userinfo";
        $response = Http::get( $url, [
            'query' => [
                "openid" => $wx_openid,
                "access_token" => $access_token,
                "lang" => "zh_CN"
            ]
        ]);
        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("读取微信用户资料错误", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        return Http::json( $response );
    }

}
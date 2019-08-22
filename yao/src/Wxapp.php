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
 * 小程序接口
 */
class Wxapp {

    /**
     * 小程序接口配置
     * 
     * @var array
     * 
     */
    private $config = [];


    /**
     * 将小程序地区编码转化为标准编码
     * @param int $province 小程序省份名称
     * @param int $city 小程序城市编码
     * 
     * @return array [:province_code, :city_code, :town_code]
     */
    public static function area( $province, $city=null ) {
        return [null, null, null];
    }

    
    /**
     * 读取小程序头像
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
     * 小程序接口配置
     */
    public function __construct( $config ) {
        $this->config = $config;
    }



    /**
	 * 使用 Code 换取 Session Key
     *
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183
     * 
     * @param  string $code   wx.login 接口返回 code 
	 * @param  string $appid  小程序应用 appid 默认为NULL, 从配置文件中读取
	 * @param  string $appsecret 小程序应用 appsecret  默认为NULL, 从配置文件中读取
     * 
	 * @return string 成功返回 Session Key
     * @throws Excp 
	 */
	public function sessionKey( string $code,  $appid=null, $appsecret=null ) {

        $url = "https://api.weixin.qq.com/sns/jscode2session";
		$appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        $appsecret = ( $appsecret == null ) ?  Arr::get($this->config, "appsecret") : $appsecret;
    

        $response = Http::get($url, [
            'query' => [
                "js_code" => $code,
                "appid" => $appid,
                "secret" => $appsecret,
                'grant_type'=> 'authorization_code'
            ]
        ]);

        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("读取小程序 Session Key 请求异常", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }
        

        $data = Http::json( $response );
        $openid = Arr::get($data, 'openid');
        if ( empty($openid) ) {
            throw Excp::create("读取小程序Session Key, 微信服务器返回错误", 500, $data);
        }

		return Arr::only($data, ["openid", "unionid", "session_key"]);
    }


    /**
     * 读取并校验用户资料 
     * - :code string wx.login 接口返回 code 
     * - :rawData  string wx.getUserInfo 接口返回值数据 rawData
     * - :signature string wx.getUserInfo 接口返回值数据 signature
     * 
     * @param array $param 小程序读取用户资料接口返回数据
     */
    public function userInfo( $param, $appid=null, $appsecret=null ) {
        
        // 校验必填数据
        $appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        $appsecret = ( $appsecret == null ) ?  Arr::get($this->config, "appsecret") : $appsecret;
        
        if ( empty(Arr::get($param,"code")) ) {
            throw Excp::create("请提供wx.login接口返回code数据", 402)
                ->addField("code", "请提供wx.login接口返回code数据")
            ;
        }

        if ( empty(Arr::get($param,"rawData")) ) {
            throw Excp::create("请提供wx.getUserInfo接口返回rawData数据", 402)
                ->addField("rawData", "请提供wx.getUserInfo接口返回rawData数据")
            ;
        }

        if ( empty(Arr::get($param,"signature")) ) {
            throw Excp::create("请提供wx.getUserInfo接口返回signature数据", 402)
                ->addField("signature", "请提供wx.getUserInfo接口返回signature数据")
            ;
        }

        // 读取会话资料
        $wxLoginData = $this->sessionKey( Arr::get($param,"code"), $appid, $appsecret );
        
        // 校验签名
        $originString = Arr::get($param,"rawData") . Arr::get($wxLoginData, "session_key");
        $signature = sha1( $originString );
        if ( $signature != Arr::get($param,"signature") ) {
            throw Excp::create("传入数据签名错误", 402)
                ->addField("signature", "传入数据签名错误")
            ;
        }

        $user = json_decode(Arr::get($param,"rawData"), true);
        $user["session_key"] = Arr::get($wxLoginData, "session_key");
        $user["openid"] = Arr::get($wxLoginData, "openid");
        $user["unionid"] = Arr::get($wxLoginData, "unionid");
        return $user;
    }


   
    /**
	 * 读取 Access Token
     * 
     * Redis cache key: wxapp:access_token:[:appid]
     * 
     * see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183
     * 
     * @param  bool   $refresh 是否强制刷新, true=强制刷新, false=优先从缓存读取。默认值为 false
	 * @param  string $appid  小程序应用 appid 默认为NULL, 从配置文件中读取
	 * @param  string $appsecret 小程序应用 appsecret  默认为NULL, 从配置文件中读取
     * 
	 * @return string 成功返回 Access Token 
     * @throws Excp 
	 */
	public function accessToken( $refresh = false,  $appid=null, $appsecret=null ) {

        $url = "https://api.weixin.qq.com/cgi-bin/token";
		$appid = ( $appid == null ) ? Arr::get($this->config, "appid") : $appid;
        $appsecret = ( $appsecret == null ) ?  Arr::get($this->config, "appsecret") : $appsecret;
        
        $cache = "wxapp:access_token:{$appid}";
        
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
            throw Excp::create("读取小程序 Access Token 错误", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }
        

        $data = Http::json( $response );
        $access_token = Arr::get($data, 'access_token');
        $ttl = intval(Arr::get($data,'expires_in', 0)) - 1000;
        if ( empty($access_token) ) {
            throw Excp::create("读取小程序 Access Token, 小程序服务器返回错误", 500, $data);
        }

		Redis::set($cache, $access_token, $ttl );// 写入缓存
		return $access_token;
    }

}
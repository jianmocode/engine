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
     * 微信接口配置
     */
    public function __construct( $config ) {
        $this->config = $config;
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
        // $qs = http_build_query($query);
        // if ( false !== strpos($redirect_uri, "?")) {
        //     $redirect_uri = "{$redirect_uri}?{$qs}";
        // } else {
        //     $redirect_uri = "{$redirect_uri}&{$qs}";
        // }
        
        Arr::forget($params, "query");
        Arr::defaults( $params, [
            "appid" => $this->config["appid"],
            "redirect_uri" => urlencode($redirect_uri),
            "response_type" => "code",
            "state" => "VPIN",
            "scope" =>  Arr::get($this->config, "scope", "snsapi_userinfo")
        ]);
        
        $qs = http_build_query( $params );
        return "{$url}?{$qs}#wechat_redirect";
    }

}
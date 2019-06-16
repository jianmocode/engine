<?php
/**
 * Class Weibo
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \FastRoute\simpleDispatcher;
use \Yao\Route\Request;

/**
 * 微博接口
 */
class Weibo {

    /**
     * 微博API接口地址
     */
    private $api = "https://api.weibo.com/";


    /**
     * 微博接口配置
     */
    private $config = [];

    /**
     * 微博接口配置
     */
    public function __construct( $config ) {
        $this->config = $config;
    }


    /**
     * 读取Oauth2.0授权地址
     * 
     * see https://open.weibo.com/wiki/Oauth2/authorize
     * 
     * @param array $params GET 请求参数
     *  - :client_id        申请应用时分配的AppKey。默认从 config 中读取。
     *  - :redirect_uri     string  授权回调地址，站外应用需与设置的回调地址一致，站内应用需填写canvas page的地址。
     *  - :scope            string  申请scope权限所需参数，可一次申请多个scope权限. see https://open.weibo.com/wiki/Scope
     *  - :state            string  用于保持请求和回调的状态，在回调时，会在Query Parameter中回传该参数。开发者可以用这个参数验证请求有效性，也可以记录用户请求授权页前的位置。这个参数可用于防止跨站请求伪造（CSRF）攻击
     *  - :display          string  授权页面的终端类型，取值见下面的说明。
     *      - default       默认的授权页面，适用于web浏览器。
     *      - mobile        移动终端的授权页面，适用于支持html5的手机。注：使用此版授权页请用 https://open.weibo.cn/oauth2/authorize 授权接口
     *      - wap           wap版授权页面，适用于非智能手机。
     *      - client        客户端版本授权页面，适用于PC桌面应用。
     *      - apponweibo    默认的站内应用授权页，授权后不返回access_token，只刷新站内应用父框架。
     *  - :forcelogin       boolen   是否强制用户重新登录，true：是，false：否。默认false。
     *  - :language         string   授权页语言，缺省为中文简体版，en为英文版。英文版测试中，开发者任何意见可反馈至 @微博API
     * 
     * @return string 微博授权地址
     * 
     */
    public function authUrl( array $params=[] ) {

        $url = "https://api.weibo.com/oauth2/authorize";

        Arr::defaults( $params, [
            "client_id" => $this->config["appkey"],
            "redirect_uri" => $this->config["authback"],
            "display" => "moible",
            "scope" => "follow_app_official_microblog"
        ]);

        $qs = http_build_query( $params );
        return "{$url}?$qs";
    }


}
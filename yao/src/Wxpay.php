<?php
/**
 * Class Wxpay
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
use \Yao\Arr;

/**
 * 微信支付接口
 */
class Wxpay {

    /**
     * 返回错误码定义
     * 
     * @var array
     */
    public static $errorCodes = [
        "NOAUTH"                    => "商户无此接口权限",
        "NOTENOUGH"                 => "用户帐号余额不足",
        "ORDERPAID"                 => "商户订单已支付，无需重复操作",
        "ORDERCLOSED"               => "当前订单已关闭，无法支付",
        "SYSTEMERROR"               => "系统超时",
        "APPID_NOT_EXIST"           => "参数中缺少APPID",
        "MCHID_NOT_EXIST"           => "参数中缺少MCHID",
        "APPID_MCHID_NOT_MATCH"     => "appid和mch_id不匹配",
        "LACK_PARAMS"               => "缺少必要的请求参数",
        "OUT_TRADE_NO_USED"         => "同一笔交易不能多次提交",
        "SIGNERROR"                 => "参数签名结果不正确",
        "XML_FORMAT_ERROR"          => "XML格式错误",
        "REQUIRE_POST_METHOD"       => "未使用post传递参数",
        "POST_DATA_EMPTY"           => "post数据不能为空",
        "NOT_UTF8"                  => "未使用指定编码格式"
    ];



    /**
     * 微信支付配置
     */
    public function __construct( $config ) {
        $this->config = $config;
    }


    /**
     * 统一下单接口
     * 
     * see https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_20&index=1
     * 
     * 主要请求参数 `$params` :
     *  
     *  :appid              string(32)      微信分配的公众账号ID/默认从配置文件中读取
     *  :mch_id             string(32)      微信支付分配的商户号(默认从配置文件中读取)
     *  :notify_url         string(256)     接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。(默认从配置文件中读取)
     *  :scene_info         string(256)     场景信息.(默认从配置文件中读取) 该字段用于上报支付的场景信息 ( 1，IOS移动应用 2，安卓移动应用 3，WAP网站应用 )  {"h5_info": {"type":"Wap","wap_url": "https://pay.qq.com","wap_name": "腾讯充值"}}
     *  :body               string(128)     商品简单描述
     *  :attach             string(127)     附加数据，在查询API和支付通知中原样返回
     *  :out_trade_no       string(32)      商户系统内部的订单号,32个字符内、可包含字母
     *  :total_fee          int             订单总金额，单位为分
     *  :product_id         string(32)      trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
     *  :openid             string(128)     trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。
     * 
     */
    public function unifiedorder( array $params = [] ) {
        
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

        Arr::defaults( $params, [
            "appid" => Arr::get($this->config, "appid"),
            "mch_id" => Arr::get($this->config, "mch_id"),
            "notify_url" => Arr::get($this->config, "notify_url"),
            "scene_info" => Arr::get($this->config, "scene_info.browser"),
        ]);

        $params["nonce_str"] = Str::uniqid();
        $params["sign_type"] = "MD5";
        $params["spbill_create_ip"] = self::getRealIpAddr();
        $params['sign'] = $this->signature($params);

        // 发送请求
        $response = Http::post( $url, [
            "body" => self::paramsToXml( $params )
        ]);

        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("统一下单接口调用失败", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        $body = $response->getBody();
        return $body;

    }


    /**
     * 读取客户端IP地址
     */
    public static function getRealIpAddr() {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  //check ip from share internet
            $ip=$_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];

        } else {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }


    /**
	 * 校验请求签名
     * 
	 * @param  array $params 微信服务器发送的请求数据
	 * @return bool
	 */
	public function checkReturnRequest( $params ) {
		$data = [
			"appId" => $this->conf['appid'],
			"timeStamp"=>$params['timeStamp'],
			"nonceStr" => $params['nonceStr'],
			"package" => "prepay_id={$params['prepay_id']}",
			"signType"=>"MD5"
		];

		return ($params['paySign'] = $this->signature($data));
	}


    /**
     * 转换为Xml格式
     * 
     * @param array $params  请求数据
     * @param array $cdata_fields  不需要解析的XML数据
     * 
     * @return string Xml格式Body字符串
     */
    public static function paramsToXml( $params, $cdata_fields=[] ) {
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
	 * 生成微信支付签名
     * 
	 * @param  array $params 请求参数表
     * 
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


}
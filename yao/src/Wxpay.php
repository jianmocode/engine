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
use \Yao\Str;

/**
 * 微信支付接口
 */
class Wxpay {

    /**
     * 微信支付配置选项
     * 
     * @var array
     * 
     */
    private $config = [];


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
     * 记录错误日志
     * 
     * @param string $method 记录方法( 有效值 debug/info/notice/warning/error/critical )
     * @param string $message 日志内容
     * @param array  $context  上下文信息
     * 
     * @return void
     */
    public function log( string $method, $message, $context=[] ) {
        $name = Arr::get($this->config, "log", null );   
        if ( empty($name) ){
            return false;
        }

        $log = Log::write($name);
        $log->$method( $message, $context);
    }



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
     *  - :appid              string(32)      微信分配的公众账号ID/默认从配置文件中读取
     *  - :mch_id             string(32)      微信支付分配的商户号(默认从配置文件中读取)
     *  - :notify_url         string(256)     接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。(默认从配置文件中读取)
     *  - :scene_info         string(256)     场景信息.(默认从配置文件中读取) 该字段用于上报支付的场景信息 ( 1，IOS移动应用 2，安卓移动应用 3，WAP网站应用 )  {"h5_info": {"type":"Wap","wap_url": "https://pay.qq.com","wap_name": "腾讯充值"}}
     *  - :body               string(128)     商品简单描述
     *  - :attach             string(127)     附加数据，在查询API和支付通知中原样返回
     *  - :out_trade_no       string(32)      商户系统内部的订单号,32个字符内、可包含字母
     *  - :total_fee          int             订单总金额，单位为分
     *  - :product_id         string(32)      trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
     *  - :openid             string(128)     trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。
     * 
     * 
     * 成功返回数据结构: 
     *  
     *  - :return_code        string          SUCCESS
     *  - :return_msg         string          OK
     *  - :appid              string          微信分配的公众账号ID
     *  - :mch_id             string          微信支付分配的商户号
     *  - :nonce_str          string          微信返回的随机字符串
     *  - :sign               string          请求签名
     *  - :result_code        string          业务结果 SUCCESS/FAIL
     *  - :prepay_id          string          微信生成的预支付回话标识，用于后续接口调用中使用，该值有效期为2小时,针对H5支付此参数无特殊用途
     *  - :trade_type         string          调用接口提交的交易类型，取值如下：JSAPI，NATIVE，APP，,H5支付固定传MWEB
     *  - :mweb_url           string          mweb_url为拉起微信支付收银台的中间页面，可通过访问该url来拉起微信客户端，完成支付, mweb_url的有效期为5分钟。
     * 
     * @return array 统一下单接口数据
     * @throws Excp 抛出异常
     * 
     */
    public function unifiedorder( array $params ) {
        
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";


        Arr::defaults( $params, [
            "appid" => Arr::get($this->config, "appid"),
            "mch_id" => Arr::get($this->config, "mch_id"),
            "notify_url" => Arr::get($this->config, "notify_url"),
            "scene_info" => Arr::get($this->config, "scene_info.browser"),
        ]);
        
        $params["nonce_str"] = Str::uniqid();
        $params["sign_type"] = "MD5";
        $params["spbill_create_ip"] = self::getRealIP();
        ksort($params);

        $params['sign'] = $this->signature($params);
        $body = trim(self::paramsToXml( $params, ["scene_info"] ));

        // 发送请求
        $response = Http::post( $url, [
            "headers" => ["Content-Type: text/xml"],
            "body" => $body
        ]);

        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("统一下单接口调用失败", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        $response_body = $response->getBody();
        $data = self::json($response_body);
        if( Arr::get($data, "return_code")  === "SUCCESS" ) {
            $this->checkSignature( $data );
        }
        
        // 返回数据异常
        if ( Arr::get($data, "return_code")  !== "SUCCESS" ) {
        
            $return_msg = Arr::get($data, "return_msg");
            $this->log("error","[MAKE] 统一下单接口返回失败({$return_msg}) #{$params["out_trade_no"]} {$params["appid"]} {$params["mch_id"]} {$params["attach"]}", [
                "notify_url" => Arr::get( $params, "notify_url"),
                "trade_type" => Arr::get( $params, "trade_type"),
            ]);

            throw Excp::create("统一下单接口返回失败({$return_msg})", 500, ["return_data"=> $data, "error_codes"=>self::$errorCodes]);
        }

        // 记录日志
        $return_code = Arr::get( $data, "return_code");
        $this->log("notice","[MAKE] 统一下单接口调用成功($return_code) #{$params["out_trade_no"]} {$params["appid"]} {$params["mch_id"]} {$params["attach"]} ", [
            "notify_url" => Arr::get( $params, "notify_url"),
            "trade_type" => Arr::get( $params, "trade_type"),
            "return_msg" => Arr::get( $data, "return_msg"),
        ]);

        return $data;
    }

    /**
     * 生成JS参数签名
     */
    public function jssdkParams( array $response ) {
        $prepay_id = Arr::get($response, "prepay_id");
        $params  = [
            "appid" => Arr::get($response, "appid"),
            "timeStamp" => time(),
            "nonceStr" => Str::uniqid(),
            "package" => "prepay_id={$prepay_id}",
            "signType" => "MD5"
        ];
        $sign = $this->signature($params);
        $params["paySign"] = $sign;
        return $params;
    }


    /**
     * 查询订单
     * see https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=9_2&index=2
     * 
     * 主要请求参数 `$params` :
     *  
     *  - :appid              string(32)      微信分配的公众账号ID/默认从配置文件中读取
     *  - :mch_id             string(32)      微信支付分配的商户号(默认从配置文件中读取)
     *  - :transaction_id     string(32)      微信的订单号，建议优先使用 (微信订单号和商户订单号必填一项)
     *  - :out_trade_no       string(32)      商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。(微信订单号和商户订单号必填一项)
     * 
     * 
     * 成功返回数据结构: 
     * 
     *  - :return_code        string          SUCCESS
     *  - :return_msg         string          OK
     *  - :appid              string          微信分配的公众账号ID
     *  - :mch_id             string          微信支付分配的商户号
     *  - :nonce_str          string          微信返回的随机字符串
     *  - :sign               string          请求签名
     *  - :result_code        string          业务结果 SUCCESS/FAIL
     *  - :err_code           string          当result_code为FAIL时返回错误代码，详细参见 $errorCodes
     *  - :err_code_des       string          当result_code为FAIL时返回错误描述，详细参见下文错误列表
     * 
     *  其他字段参见微信文档
     * 
     * @return array 订单信息
     * 
     */
    public function orderquery( array $params ){

        $url = "https://api.mch.weixin.qq.com/pay/orderquery";

        Arr::defaults( $params, [
            "appid" => Arr::get($this->config, "appid"),
            "mch_id" => Arr::get($this->config, "mch_id"),
        ]);

        $params["nonce_str"] = Str::uniqid();
        $params["sign_type"] = "MD5";
        ksort($params);

        $params['sign'] = $this->signature($params);
        $body = trim(self::paramsToXml( $params) );

        // 发送查询请求
        $response = Http::post( $url, [
            "headers" => ["Content-Type: text/xml"],
            "body" => $body
        ]);
        
        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            throw Excp::create("查询订单接口调用失败", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        $response_body = $response->getBody();
        $data = self::json($response_body);
        $this->checkSignature( $data );

        // 返回数据异常
        if ( Arr::get($data, "return_code")  !== "SUCCESS" ) {
            $return_msg = Arr::get($data, "return_msg");
            throw Excp::create("查询订单接口返回失败({$return_msg})", 500, ["return_data"=> $data, "error_codes"=>self::$errorCodes]);
        }

        return $data;
    }



    /**
     * 读取微信支付通知数据
     * 
     * @param string $body 微信通知响应结果
     * 
     * @return array 通知数据
     * 
     */
    public function notifyGet( $body ) {
        
        $params = self::json( $body );
        $out_trade_no = Arr::get( $params, "out_trade_no", "UNKNOWN");
        $attach = Arr::get( $params, "attach", "");
        $appid = Arr::get( $params, "appid", "");
        $mch_id = Arr::get( $params, "mch_id", "");
        $return_code = Arr::get( $params, "return_code");

        $this->log("notice", "[RESP] 接收微信支付通知请求($return_code) #{$out_trade_no} {$appid} {$mch_id} {$attach} ", [
            "trade_type" => Arr::get( $params, "trade_type"),
        ]);

        if ( !$this->checkSignature( $params, true ) ) {
            $this->notifyResponse( $params );
        }

        return $params;
    }

    /**
     * 回应微信支付通知
     * 
     * @return void;
     */
    public function notifyResponse( $params = [] ) {
        
        $out_trade_no = Arr::get( $params, "out_trade_no", "UNKNOWN");
        $attach = Arr::get( $params, "attach", "");
        $appid = Arr::get( $params, "appid", "");
        $mch_id = Arr::get( $params, "mch_id", "");
        $return_code = Arr::get( $params, "return_code", "FAIL");
        $method = ( $return_code === "SUCCESS") ? "info" : "error";
        $return_code = Arr::get( $params, "return_code");

        $this->log($method, "[DONE] 微信支付通知处理完毕({$return_code}). #{$out_trade_no} {$appid} {$mch_id} {$attach} ", [
            "trade_type" => Arr::get( $params, "trade_type")
        ]);

        echo '<xml>
                <return_code><![CDATA[SUCCESS]]></return_code>
                <return_msg><![CDATA[OK]]></return_msg>
             </xml>';
        exit;
    }


    /**
     * 将结果转换为数组
     * 
     * @return array 返回结果
     */
    public static function json( $body ) {

        try {
            $xml = new \SimpleXMLElement($body, LIBXML_NOCDATA); 
        } catch ( \Exception $e ) {
            return [];
        }
        $response =  json_decode( json_encode($xml), true);
        if ( $response === false ) {
            return [];
        }
        return $response;
    }



    /**
     * 读取客户端IP地址
     * 
     * @return string Client IP address
     */
    public static function getRealIP() {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  //check ip from share internet
            $ip=$_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];

        } elseif ( !empty(Arr::get($_SERVER,'REMOTE_ADDR')) ) {
            $ip=Arr::get($_SERVER,'REMOTE_ADDR');
        } else {
            $ip = "127.0.0.1";
        }
        return $ip;
    }


    /**
	 * 校验数据签名
     * 
	 * @param  array $return_data 微信服务器发送的请求数据
     * @param  bool  $return 是否返回校验结果，默认为 false, 失败抛出异常
     * 
	 * @return bool 成功返回true 
     * @throws Excp 
	 */
	private function checkSignature( $return_data, $return = false  ) {
        
        foreach( $return_data as $k => & $v ) {
            if ( is_array($v) && empty($v) ) {
                unset($return_data[$k]);
            }
        }

        $sign = Arr::get($return_data, "sign");
        $return_data = Arr::except($return_data, "sign");

        if ( $sign != $this->signature($return_data) ) {
            
            if ( $return ) {
                return false;
            }

            throw Excp::create("微信支付返回数据签名错误", 500);
        }

        return true;
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
        $stringSignTemp="{$stringSign}&key=" . Arr::get($this->config, "key");        
		return strtoupper(MD5($stringSignTemp));
	}


}
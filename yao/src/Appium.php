<?php
/**
 * Class Appium
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;



/**
 * Appium Client 
 * see https://github.com/guzzle/guzzle
 */
class Appium {

    /**
     * 配置信息
     */
    private $config = [];

    /**
     * Appium Client
     */
    public function __construct( array $config ) {
        
        Arr::defaults( $config, [
            "protocol" => "http",
            "host" => "127.0.0.1",
            "port" => 4723,
            "user" => null,
            "password" => null
        ]);

        $this->config = $config;
    }

    public function url( $api ) {
        return "{$this->config["protocol"]}://{$this->config["host"]}:{$this->config["port"]}/wd/hub{$api}";
    }

    /**
     * GET 方法调用
     * @param string $api API 名称
     * @param array $params 查询参数
     * @return array API 返回结果
     */
    public function get( string $api, array $params = [], array $body=null) {

        $url = $this->url( $api );
        $response = Http::get( $url, [
            'query' => $params,
            'body' => is_null($body) ? $body : json_encode( $body ),
        ]);
        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            if ( $response->getBody() ) {
                $error = Http::json( $response );
                $message = Arr::get( $error, "value.message");
                $status = Arr::get( $error, "status", 0);
                $session_id = Arr::get( $error, "sessionId", null);
                throw Excp::create("接口调用失败($api)", 500, ["message" => $message, "session_id"=>$session_id, "status"=>$status]);
            }
            throw Excp::create("接口调用失败($api)", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        return Http::json( $response );
    }


    /**
     * POST 方法调用
     * @param string $api API 名称
     * @param array $data 请求参数
     * @param array $params 查询参数
     * @return array API 返回结果
     */
    public function post( string $api, array $data = [], array $params=[] ) {

        $url = $this->url( $api );
        $response = Http::post( $url, [
            'query' => $params,
            'body'  => json_encode($data)
        ]);
        $code = $response->getStatusCode();
        if ( $code != 200 ) {

            if ( $response->getBody() ) {
                $error = Http::json( $response );
                $message = Arr::get( $error, "value.message");
                $status = Arr::get( $error, "status", 0);
                $session_id = Arr::get( $error, "sessionId", null);
                throw Excp::create("接口调用失败($api)", 500, ["message" => $message, "session_id"=>$session_id, "status"=>$status]);
            }

            throw Excp::create("接口调用失败($api)", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        return Http::json( $response );
    }


}
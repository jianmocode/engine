<?php
/**
 * Class Http
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \GuzzleHttp\Client;
use \Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;



/**
 * Http Client 
 * see https://github.com/guzzle/guzzle
 */
class Http {

    /**
     * predis 实例
     */
    public static $client = null;


    /**
     * 返回JSON数据
     * 
     * @param \Psr\Http\Message\ResponseInterface $response  PSR Http Response Struct
     * 
     * @return mix json_data
     * 
     */
    public static function json( ResponseInterface $response ) {
        $body = $response->getBody();
        $json_data = json_decode($body, true );
        if  ($json_data === false ) {
            throw Excp::create("解析JSON数据失败", 500, ["body"=>$body]);
        }
        return $json_data;
    }

    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        if ( empty(self::$client) ) {
            self::$client = new Client();
        }
        try {
            return self::$client->{$method}(...$parameters);

        // 发送远程请求异常
        } catch (RequestException $e) {

            // 有数据返回
            if ($e->hasResponse()) {
                return $e->getResponse();
            }

            $excp = Excp::create("发送远程请求错误", 500, ["method"=>$method, "parameters"=>$parameters]);
            $excp->log();
            throw $excp;

        // 客户端发送数据异常
        } catch (ClientException $e) {
            return $e->getResponse();
        }
    }

}
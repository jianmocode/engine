<?php
/**
 * Class Request
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao\Route;

/**
 * 路由器(Base on FastRoute)
 */
class Request {

    public $hostName = "";

    public $host = "";

    public $method = "";

    public $requestURI = "";

    public $headers = [];

    public $contentType = "";

    public $payloads = [];

    public $params = [];

    public $files = [];

    public $uri = [];

    public $responseHeader = [];

    /**
     * 构造函数
     */
    public function __construct() {

        $this->getHost();
        $this->getRequestURI();
        $this->getHeaders();
        $this->getMethod();
        $this->getRequestData();

    }

    public function setURI( $uri ) {
        $this->uri = $uri;
    }

    public function addHeader( $name, $value ) {
        $this->responseHeader[$name] = $value;
    }

    private function getRequestData() {

        if ( $this->method == "GET" ) {
            $this->params = $_GET;
            return;
        }

        if ( $contentType == 'application/json' ) {
            $payloads = file_get_contents("php://input");
            if ( !empty($payloads) ) {
                $this->payloads = \json_decode($payloads, true);
            }

        } else {
            $this->payloads = $_POST;
            $this->files = $_FILES;
        }
    }

    private function getMethod() {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }
    

    private function getHeaders() {

        $headers = [];
        if ( function_exists('apache_request_headers') ) {
            $headers = apache_request_headers();
        } else {

            foreach( $_SERVER as $key => $value ) {
                if ( substr($key,0,5)=="HTTP_" ) {
                    $key = str_replace( " " , "-" , ucwords( strtolower( str_replace( "_" , " " , substr( $key , 5 ) ))));
                    $headers[$key]=$value;
                }
                else {
                    $headers[$key]=$value;
                }
            }
        }

        $this->headers = $headers;
        $this->contentType = $this->headers["CONTENT_TYPE"] ?: 'text/plain';
    }

    private function getRequestURI() {

        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $this->requestURI = $uri;
    }

    private function getHost() {
        
        // GET HOST
        $host = $_SERVER["HTTP_HOST"];
        $host_names = explode(".", $host);
        $host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        
        // 绑定的独立域名解析
        if ( !in_array($host_name, ["vpin.biz", "vpin.ink"])){
            $cname = dns_get_record($host, DNS_CNAME);
            $host_names = explode(".", $host);
            $host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
        }

        $this->hostName = $host_name;
        $this->host = $host;

    }

}
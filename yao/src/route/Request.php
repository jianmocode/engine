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

    /**
     * 域名 xxx.com 
     * 
     * @var string
     */
    public $hostName = "";
    
    /**
     * 二级域名 xxxx
     * 
     * @var string
     */
    public $hostSubname = "";

    /**
     * 完整域名 xxx.yyy.com
     * 
     * @var string
     */
    public $host = "";

    /**
     * HTTP Request 请求方法
     * 
     * GET / POST / PUT /...
     * 
     * @var string
     */
    public $method = "";

    /**
     * 请求路由
     * 
     * @var string
     */
    public $requestURI = "";

    /**
     *  HTTP Request Headers
     * 
     *  @var string
     */
    public $headers = [];

    /**
     *  HTTP Request Content-Type
     * 
     *  @var string
     */
    public $contentType = "";

    /**
     *  HTTP Request 提交数据
     * 
     *  @var array
     */
    public $payloads = [];

    /**
     *  HTTP Request Query Params 
     * 
     *  @var array
     */
    public $params = [];

    /**
     *  HTTP Request file upload string
     * 
     *  @var array
     */
    public $files = [];

    /**
     * HTTP Request 解析后的路由变量
     * 
     * see https://github.com/nikic/FastRoute
     * 
     * @var array
     */
    public $uri = [];


    /**
     *  HTTP Response Headers
     * 
     *  @var array
     */
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

    /**
     *  设定路由变量
     * 
     *  @param array $uri 路由变量
     *  @return void
     */
    public function setURI( $uri ) {
        $this->uri = $uri;
    }

    /**
     *  添加 HTTP Response Header
     * 
     *  @param $name header name
     *  @param $value header value
     *  @return void
     */
    public function addHeader( $name, $value ) {
        $this->responseHeader[$name] = $value;
    }

    /**
     * 读取 Request 数据
     */
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

    /**
     * 读取请求方法
     */
    private function getMethod() {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }
    

    /**
     * 读取请求Header
     */
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

    /**
     * 读取请求路由
     */
    private function getRequestURI() {

        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $this->requestURI = $uri;
    }

    /**
     * 读取域名信息
     */
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

        if ( count($host_names) >= 3 ) {
            $host_subname = $host_names[count($host_names)-3];
        }
        $this->hostName = $host_name;
        $this->hostSubname = $host_subname;
        $this->host = $host;
    }

}
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
use \Yao\Arr;


/**
 * 路由器(Base on FastRoute)
 */
class Request {

    /**
     * 请求代理 weibo/wechat/wxapp/null
     * @var string
     */
    public $agent = null;

    /**
     * 请求平台 android / ios / desktop etc
     */
    public $platform = null;

    /**
     * 是否为手机端
     */
    public $isMobile = false;

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
        $this->getOrigin();
        $this->getRequestURI();
        $this->getHeaders();
        $this->getMethod();
        $this->getRequestData();
        $this->setGlobal();

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

        if ( $this->contentType == 'application/json' ||  $this->contentType == 'application/xml' ) {
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


    /**
     * 读取请求来源
     */
    private function getOrigin() {

        $useragent = Arr::get($_SERVER, 'HTTP_USER_AGENT', "unknown") ;

        // 微信
        if ( strpos($useragent, 'MicroMessenger') ) {
            $this->agent = 'wechat';

        // 微信小程序
        } else if ( strpos($useragent, 'miniProgram') ) {
            $this->agent = 'wxapp';
        }

        // 检查来源系统
        $match = null;
        if( preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent, $matches)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent,0,4), $matches) ) {
            $this->platform = $matches[1];
            $this->isMobile = true;
        } else {
            $this->platform = "browser";
            $this->isMobile = false;
        }
    }


    /**
     * 设定全局变量
     */
    private function setGlobal() {

        $GLOBALS["YAO"]["REQUEST_ORIGIN"] = [
            "HOST" => $_SERVER["HTTP_HOST"],
            "DOMAIN" => $this->hostName,
            "SUBDOMAIN" => $this->hostSubname,
            "AGENT" => $this->agent,
            "PLATFORM" => $this->platform,
            "ISMOBILE" => $this->isMobile
        ];
    }

}
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
 * HTTP Reqeust 数据控制器
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
    public static $responseHeaders = [];

    /**
     * 读取当前访问者完整地址
     * 
     * @return string 
     */
    public static function url() {
        $host = Arr::get( $_SERVER, "HTTP_HOST");
        $uri  = Arr::get( $_SERVER, "REQUEST_URI");
        return self::isHttps() ?  "https://" : "http://" . "{$host}{$uri}";
    }

    /**
     * 读取当前访问者根地址
     * 
     * @return string 
     */
    public static function home() {
        $host = Arr::get( $_SERVER, "HTTP_HOST");
        $uri  = Arr::get( $_SERVER, "REQUEST_URI");
        return self::isHttps() ?  "https://" : "http://" . "{$host}";
    }


    /**
     * 检测当前访问是否通过HTTPS访问
     * 
     * @return bool 
     */
    public static function isHttps(){

        $https = Arr::get($_SERVER, "HTTPS");
        if ( $https && strtolower($https) != "off" ) {
            return true;
        }

        $https = Arr::get($_SERVER, "HTTP_X_FORWARDED_PROTO");
        if ( $https && strtolower($https) == "https" ) {
            return true;
        }

        $https = Arr::get($_SERVER, "HTTP_FRONT_END_HTTPS");
        if ( $https && strtolower($https) != "off" ) {
            return true;
        }

        return false;
    }

    /**
     * 读取平台请求来源
     * 
     * 返回值结构
     * 
     *  - agent string 请求代理 
     *      - "browser" 浏览器
     *      - "wechat" 微信
     *      - "weibo"  微博
     *      - "wxapp"  小程序
     *  - platform  string 系统平台  windows/android/ios/browser
     *  - mobile bool 是否为移动端请求 1=移动端 0 非移动端
     *  - origin 用户来源原始字符串
     *  
     * @return array
     */
    public static function origin() {

        $agent = 'browser';
        $platform = null;
        $mobile = false;

        $userAgent = Arr::get($_SERVER, 'HTTP_USER_AGENT', "unknown") ;

        // 微信
        if ( strpos($userAgent, 'MicroMessenger') ) {
            $agent = 'wechat';

        // 微信小程序
        } else if ( strpos($userAgent, 'miniProgram') ) {
            $agent = 'wxapp';

        // 新浪微博
        } else if ( strpos($userAgent, 'weibo') ) {
            $agent = 'weibo';
        }

        // 检查来源系统
        $match = null;
        if( preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent, $matches)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent,0,4), $matches) ) {
            $platform = $matches[1];
            $mobile = 1;
        } else {
            $mobile = 0;
        }

        if ( empty($platform) ) {
            $platform = self::getOS($userAgent);
        }

        // 返回
        return [
            "agent" => $agent,
            "platform" => $platform,
            "mobile" => $mobile,
            "origin" => $userAgent,
        ];
    }


    /**
     * Get the user's operating system
     *
     * @param   string  $userAgent  The user's user agent
     *
     * @return  string  Returns the user's operating system as human readable string,
     *  if it cannot be determined 'n/a' is returned.
     */
    public static function getOS($userAgent) {
        // Create list of operating systems with operating system name as array key 
        $oses = array (
            'Windows3.11'   => 'Win16',
            'Windows95'     => '(Windows 95)|(Win95)|(Windows_95)',
            'Windows98'     => '(Windows 98)|(Win98)',
            'Windows2000'   => '(Windows NT 5.0)|(Windows 2000)',
            'WindowsXP'     => '(Windows NT 5.1)|(Windows XP)',
            'Windows2003'   => '(Windows NT 5.2)',
            'WindowsVista'  => '(Windows NT 6.0)|(Windows Vista)',
            'Windows7'      => '(Windows NT 6.1)|(Windows 7)',
            'Windows10'     => '(Windows 10)',
            'WindowsNT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
            'WindowsME'     => 'Windows ME',
            'Windows'       => 'Windows',
            'OpenBSD'       => 'OpenBSD',
            'SunOS'         => 'SunOS',
            'Android'       => '(Android)|(android)',
            'Linux'         => '(Linux)|(X11)',
            'iOS'           => '(iOS)|(iphone)|(iPhone)|(iPad)',
            'MacOS'         => '(Mac_PowerPC)|(Macintosh)',
            'QNX'           => 'QNX',
            'BeOS'          => 'BeOS',
            'OS/2'          => 'OS/2',
            'Search Bot'    => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)'
        );
        
        // Loop through $oses array
        foreach($oses as $os => $preg_pattern) {
            // Use regular expressions to check operating system type
            if ( preg_match('@' . $preg_pattern . '@', $userAgent) ) {
                // Operating system was matched so return $oses key
                return $os;
            }
        }
        
        // Cannot find operating system so return Unknown
        
        return 'unknown';
    }

    /**
     *  添加 HTTP Response Header 
     * 
     *  @param $name header name
     *  @param $value header value
     *  @return void
     */
    public static function addHeader( $name, $value ) {
        self::$responseHeaders[$name] = $value;
    }


    /**
     * 发送 Response Header
     * 
     * @return void
     */
    public static function sendHeader(){
        foreach( self::$responseHeaders as $name=>$value ) {
            $name = ucfirst( $name );
            Header("{$name}: {$value}");
        }
    }


    /**
     * 构造函数
     */
    public function __construct() {
        $this->setHost();
        $this->setOrigin();
        $this->setRequestURI();
        $this->setHeaders();
        $this->setMethod();
        $this->setRequestData();
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
     * 读取 Request 数据
     */
    private function setRequestData() {

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
    private function setMethod() {
        $this->method = Arr::get($_SERVER, 'REQUEST_METHOD');
    }
    

    /**
     * 读取请求Header
     */
    private function setHeaders() {

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
        $this->contentType = Arr::get($this->headers, "CONTENT_TYPE") ?: 'text/plain';
    }

    /**
     * 读取请求路由
     * 
     * @return void
     */
    private function setRequestURI() {

        $uri = Arr::get($_SERVER, 'REQUEST_URI');
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $this->requestURI = $uri;
    }

    /**
     * 读取域名信息
     * 
     * @todo 从配置中读取绑定独立域名列表
     * 
     * @return void
     */
    private function setHost() {
        
        // GET HOST
        $host = Arr::get($_SERVER, "HTTP_HOST");
        $host_names = explode(".", $host);
        $host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
       
        // 绑定的独立域名解析(下一版: 从配置文件读取)
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
     * 
     * @return void
     */
    private function setOrigin() {

        $origin = self::origin();
        $this->agent = $origin["agent"];
        $this->platform = $origin["platform"];
        $this->isMobile = $origin["mobile"];
    }


    /**
     * 设定全局变量
     * 
     * @return void
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
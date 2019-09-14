<?php
/**
 * Class Splash
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;

/**
 * Splash Client 
 * see https://splash.readthedocs.io/en/stable/api.html
 */
class Splash {
    
    /**
     * Splash 服务器参数
     * - :host      string  Splash 服务地址
     * - :port      int     Splash 服务端口
     * - :user      string  [选填]用户名(basic authentication)
     * - :password  string  [选填]密码(basic authentication)
     * 
     * @var array
     */
    private static $config = [];


    /**
     * Splash API地址
     * 
     * @var string
     */
    private static $api = "http://127.0.0.1:8050";

  
    public function __construct() {

        self::$config = [
            "protocol" => "http",
            "host" => "127.0.0.1",
            "port" => 8050
        ];
    }

    /**
     * 设定 Splash 服务器参数配置
     * 
     * @param array $config 服务器参数
     * @return void
     */
    public static function setting( array $config ) {

        Arr::defaults($config, [
            "protocol" => "http",
            "host" => "127.0.0.1",
            "port" => 8050
        ]);

        self::$config = $config;
        $protocol = Arr::get(self::$config, "protocol", "http");
        $host     = Arr::get(self::$config, "host", "127.0.0.1");
        $port     = Arr::get(self::$config, "port", 8050);
        self::$api ="{$protocol}://{$host}:{$port}";
    }


    /**
     * 抓取并渲染HTML网页
     * 
     * see https://splash.readthedocs.io/en/stable/api.html#render-html
     * 
     * 配置选项:
     *  - :timeout                  Float     A timeout (in seconds) for the render (defaults to 30).
     *  - :resource_timeout         Float     A timeout (in seconds) for individual network requests.
     *  - :wait                     Float     Time (in seconds) to wait for updates after page is loaded (defaults to 0). Increase this value if you expect pages to contain setInterval/setTimeout javascript calls, because with wait=0 callbacks of setInterval/setTimeout won’t be executed. Non-zero wait is also required for PNG and JPEG rendering when doing full-page rendering
     *  - :proxy                    String    A proxy URL should have the following format: [protocol://][user:password@]proxyhost[:port]
     *  - :viewport                 String    View width and height (in pixels) of the browser viewport to render the web page. Format is “<width>x<height>”, e.g. 800x600. Default value is 1024x768.
     *  - :user_agent               String    Change User-Agent header used for requests;
     *  - :images                   String    Whether to download images. Possible values are 1 (download images) and 0 (don’t download images). Default is 1.
     *  - :js_source                String    JavaScript code to be executed in page context. See https://splash.readthedocs.io/en/stable/api.html#execute-javascript
     *  - :allowed_content_types    String    Comma-separated list of allowed content types. If present, Splash will abort any request if the response’s content type doesn’t match any of the content types in this list. Wildcards are supported using the fnmatch syntax.
     *  - :forbidden_content_types  String    Comma-separated list of forbidden content types. If present, Splash will abort any request if the response’s content type matches any of the content types in this list. Wildcards are supported using the fnmatch syntax.
     *  - :html5_media              Integer   Whether to enable HTML5 media (e.g. <video> tags playback). Possible values are 1 (enable) and 0 (disable). Default is 0. HTML5 media is currently disabled by default because it may cause instability.
     * 
     * @param string $url 页面地址
     * @param array $options 配置选项
     * @return string HTML页面源码
     */
    public static function renderHtml( string $url, array $options=[] ) {

        // user_agent
        if ( array_key_exists("user_agent", $options) ) {
            $options["headers"] = [
               "User-Agent" => Arr::get($options, "user_agent")
            ];
            unset($options["user_agent"]);
        }

        $options["url"] = $url;
        $response = Http::post( self::$api . "/render.html", [
            "json" => $options,
            'headers' => [
                "Content-Type" => "application/json"
            ]
        ]);
        
        $code = $response->getStatusCode();
        if ( $code != 200 ) {
            $message = $response->getReasonPhrase();
            throw Excp::create("调用Splash接口错误({$message})", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
        }

        return $response->getBody();
    }


    /**
     * [异步]抓取并渲染HTML网页
     * 
     * @param callable $callback 回调函数 function($content, $excp=null){}
     * @param string $url 页面地址
     * @param array $options 配置选项
     * @return void
     */
    public static function renderHtmlAsync( callable $callback, string $url, array $options=[] ) {
        
        // user_agent
        if ( array_key_exists("user_agent", $options) ) {
            $options["headers"] = [
               "User-Agent" => Arr::get($options, "user_agent")
            ];
            unset($options["user_agent"]);
        }

        $options["url"] = $url;
        $response = \Yao\Async\Http::post( self::$api . "/render.html", $options, [
            "retry_time" => 3,
            'headers' => [
                "Content-Type" => "application/json"
            ],
            "after" => function( $response ) use( $callback) {
                $code = $response->getStatusCode();
                if ( $code != 200 ) {
                    $message = $response->getReasonPhrase();
                    $excp = Excp::create("调用Splash接口错误({$message})", 500, ["reason" => $response->getReasonPhrase(), "status_code"=>$code]);
                    $callback($response->getBody(), null);
                }

                $callback($response->getBody(), null);
            }
        ]);
    }

}

if ( Arr::get($GLOBALS, "YAO.splash") ) {
    Splash::setting($GLOBALS["YAO"]["splash"]);
} 
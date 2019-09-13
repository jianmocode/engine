Yao\Splash
===============

Splash Client
see https://splash.readthedocs.io/en/stable/api.html




* Class name: Splash
* Namespace: Yao





Properties
----------


### $config

    private array $config = array()

Splash 服务器参数
- :host      string  Splash 服务地址
- :port      int     Splash 服务端口
- :user      string  [选填]用户名(basic authentication)
- :password  string  [选填]密码(basic authentication)



* Visibility: **private**
* This property is **static**.


### $api

    private string $api = "http://127.0.0.1:8050"

Splash API地址



* Visibility: **private**
* This property is **static**.


Methods
-------


### __construct

    mixed Yao\Splash::__construct()





* Visibility: **public**




### setting

    void Yao\Splash::setting(array $config)

设定 Splash 服务器参数配置



* Visibility: **public**
* This method is **static**.


#### Arguments
* $config **array** - &lt;p&gt;服务器参数&lt;/p&gt;



### renderHtml

    string Yao\Splash::renderHtml(string $url, array $options)

抓取并渲染HTML网页

see https://splash.readthedocs.io/en/stable/api.html#render-html

配置选项:
 - :timeout                  Float     A timeout (in seconds) for the render (defaults to 30).
 - :resource_timeout         Float     A timeout (in seconds) for individual network requests.
 - :wait                     Float     Time (in seconds) to wait for updates after page is loaded (defaults to 0). Increase this value if you expect pages to contain setInterval/setTimeout javascript calls, because with wait=0 callbacks of setInterval/setTimeout won’t be executed. Non-zero wait is also required for PNG and JPEG rendering when doing full-page rendering
 - :proxy                    String    A proxy URL should have the following format: [protocol://][user:password@]proxyhost[:port]
 - :viewport                 String    View width and height (in pixels) of the browser viewport to render the web page. Format is “<width>x<height>”, e.g. 800x600. Default value is 1024x768.
 - :user_agent               String    Change User-Agent header used for requests;
 - :images                   String    Whether to download images. Possible values are 1 (download images) and 0 (don’t download images). Default is 1.
 - :js_source                String    JavaScript code to be executed in page context. See https://splash.readthedocs.io/en/stable/api.html#execute-javascript
 - :allowed_content_types    String    Comma-separated list of allowed content types. If present, Splash will abort any request if the response’s content type doesn’t match any of the content types in this list. Wildcards are supported using the fnmatch syntax.
 - :forbidden_content_types  String    Comma-separated list of forbidden content types. If present, Splash will abort any request if the response’s content type matches any of the content types in this list. Wildcards are supported using the fnmatch syntax.
 - :html5_media              Integer   Whether to enable HTML5 media (e.g. <video> tags playback). Possible values are 1 (enable) and 0 (disable). Default is 0. HTML5 media is currently disabled by default because it may cause instability.

* Visibility: **public**
* This method is **static**.


#### Arguments
* $url **string** - &lt;p&gt;页面地址&lt;/p&gt;
* $options **array** - &lt;p&gt;配置选项&lt;/p&gt;



### renderHtmlAsync

    void Yao\Splash::renderHtmlAsync(callable $callback, string $url, array $options)

[异步]抓取并渲染HTML网页



* Visibility: **public**
* This method is **static**.


#### Arguments
* $callback **callable** - &lt;p&gt;回调函数 function($content, $excp=null){}&lt;/p&gt;
* $url **string** - &lt;p&gt;页面地址&lt;/p&gt;
* $options **array** - &lt;p&gt;配置选项&lt;/p&gt;



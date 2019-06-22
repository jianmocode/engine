Yao\Wechat
===============

微信接口




* Class name: Wechat
* Namespace: Yao





Properties
----------


### $config

    private array $config = array()

微信接口配置



* Visibility: **private**


Methods
-------


### area

    array Yao\Wechat::area(integer $province, integer $city)

将微信地区编码转化为标准编码



* Visibility: **public**
* This method is **static**.


#### Arguments
* $province **integer** - &lt;p&gt;微信省份名称&lt;/p&gt;
* $city **integer** - &lt;p&gt;微信城市编码&lt;/p&gt;



### headimg

    mixed Yao\Wechat::headimg(string $url, integer $size)

读取微信头像



* Visibility: **public**
* This method is **static**.


#### Arguments
* $url **string** - &lt;p&gt;头像地址&lt;/p&gt;
* $size **integer** - &lt;p&gt;有效值 0、46、64、96、132数值可选，0代表640*640正方形头像&lt;/p&gt;



### __construct

    mixed Yao\Wechat::__construct($config)

微信接口配置



* Visibility: **public**


#### Arguments
* $config **mixed**



### jssdkConfig

    array Yao\Wechat::jssdkConfig(string $url, string $appid, string $appsecret)

读取 JS-SDK Config



* Visibility: **public**


#### Arguments
* $url **string** - &lt;p&gt;引用JS-SDK的页面地址. 默认读取 Request URL&lt;/p&gt;
* $appid **string** - &lt;p&gt;微信应用 appid 默认为NULL, 从配置文件中读取&lt;/p&gt;
* $appsecret **string** - &lt;p&gt;微信应用 appsecret  默认为NULL, 从配置文件中读取&lt;/p&gt;



### jsapiTicket

    string Yao\Wechat::jsapiTicket(boolean $refresh, string $appid, string $appsecret)

读取 JSAPI Ticket

Redis cache key: wechat:jsapi_ticket:[:appid]

see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115

* Visibility: **public**


#### Arguments
* $refresh **boolean** - &lt;p&gt;是否强制刷新, true=强制刷新, false=优先从缓存读取。默认值为 false&lt;/p&gt;
* $appid **string** - &lt;p&gt;微信应用 appid 默认为NULL, 从配置文件中读取&lt;/p&gt;
* $appsecret **string** - &lt;p&gt;微信应用 appsecret  默认为NULL, 从配置文件中读取&lt;/p&gt;



### accessToken

    string Yao\Wechat::accessToken(boolean $refresh, string $appid, string $appsecret)

读取 Access Token

Redis cache key: wechat:access_token:[:appid]

see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183

* Visibility: **public**


#### Arguments
* $refresh **boolean** - &lt;p&gt;是否强制刷新, true=强制刷新, false=优先从缓存读取。默认值为 false&lt;/p&gt;
* $appid **string** - &lt;p&gt;微信应用 appid 默认为NULL, 从配置文件中读取&lt;/p&gt;
* $appsecret **string** - &lt;p&gt;微信应用 appsecret  默认为NULL, 从配置文件中读取&lt;/p&gt;



### authUrl

    string Yao\Wechat::authUrl(array $params)

读取Oauth2.0授权地址

see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842

请求参数 `$params` :

 - :appid            string  公众号的唯一标识
 - :scope            string  应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且， 即使在未关注的情况下，只要用户授权，也能获取其信息 ）
 - :state            string  重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
 - :query            array   附加在 redirect_uri 上的查询参数

* Visibility: **public**


#### Arguments
* $params **array** - &lt;p&gt;GET 请求参数&lt;/p&gt;



### oauthAccessToken

    array Yao\Wechat::oauthAccessToken(array $params)

读取 Oauth2.0 Access Token

see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842

请求参数 `$params` :

 - :appid 申请应用时分配的AppKey。默认从 config 中读取。
 - :secret 申请应用时分配的AppSecret。 默认从 config 中读取。
 - :code 调用authorize获得的code值。

返回数据结构 :

 - :access_token     微信 Access Token
 - :expires_in       Token 过期时间
 - :refresh_token    用户刷新access_token
 - :openid           用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID
 - :scope            用户授权的作用域，使用逗号（,）分隔
 - :unionid          用户的唯一标识(微信全平台范围), 请注意只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。

* Visibility: **public**


#### Arguments
* $params **array** - &lt;p&gt;调用参数&lt;/p&gt;



### getUser

    array Yao\Wechat::getUser(string $wx_openid, string $access_token)

读取微信用户资料

see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842

返回数据关键字段

 - openid            用户的唯一标识(公众号范围)
 - unionid           用户的唯一标识(微信全平台范围), 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
 - nickname          微信用户昵称
 - sex               用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
 - province          用户个人资料填写的省份
 - city              普通用户个人资料填写的城市
 - country           国家，如中国为CN
 - headimgurl        用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
 - privilege         用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）

* Visibility: **public**


#### Arguments
* $wx_openid **string** - &lt;p&gt;用户的唯一标识(公众号范围)&lt;/p&gt;
* $access_token **string** - &lt;p&gt;微信OAuth2.0授权 access_token&lt;/p&gt;



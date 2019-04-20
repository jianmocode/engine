系统环境要求
================================

## 一、云资源

### 1. 最低配置

**云资源清单**

| 云资源 | 配置 | 数量  |
| --- | --- | --- |
|  云主机 |  CPU 1核/ 内存 1G/ 磁盘10G | 1实例 |
|  域名 | 支持二级域名  | 1个 |
|  HTTPS证书 | 启用HTTP2和小程序客户端必须  | 1个 |

### 2. 推荐配置

**云资源清单**

| 云资源 | 配置 | 数量  |
| --- | --- | --- |
|  云主机 |  CPU 2核/ 内存 2G/ 磁盘40G | 1实例  |
|  云数据库 |  MySQL 5.5+ |  1实例 |
|  REDIS缓存 |   |  1实例 |
|  域名 | 支持二级域名  | 1个 |
|  HTTPS证书 | 启用HTTP2和小程序客户端必须 | 1个 |


### 3. 操作系统

**支持操作系统**

| 操作系统 | Docker  | 推荐版本 |
| --- | --- |  --- |
|  CentOS [荐] | Docker CE/Docker EE  x86_64 | 7.3 |
|  Ubuntu [荐]	| Docker CE/Docker EE  x86_64 | 16.04 |
|  Fedora | Docker CE/Docker EE x86_64 | |
|  Debian | Docker CE/Docker EE x86_64 | |
|  MacOS | Docker for Mac (macOS) |  |
|  Win10 | Docker for Windows  |   |


## 二、平台账号

| 平台账号| 说明 | 申请地址  |
| --- | --- | --- |
|  微信小程序 | 如需启用小程序客户端必须申请；如使用付款功能，还需申请微信支付权限。 | https://mp.weixin.qq.com/wxopen/waregister?action=step1 |
|  微信订阅号 | 如需要同步已发布图文信息，必须申请API访问权限 | https://mp.weixin.qq.com/cgi-bin/readtemplate?t=register/step1_tmpl&lang=zh_CN |
|  微信服务号 | 如需要使用微信扫码登录等功能，必须申请API访问权限； 如使用付款功能，还需要申请微信支付权限。 | https://mp.weixin.qq.com/cgi-bin/readtemplate?t=register/step1_tmpl&lang=zh_CN |
|  微信开放平台 | 如需要打通微信H5和小程序间用户数据，需要在微信开放平台绑定小程序、微信服务号。 | https://open.weixin.qq.com/cgi-bin/readtemplate?t=regist/regist_tmpl&lang=zh_CN |
|  HTTPS证书 | 腾讯云提供HTTPS证书申请 | https://console.cloud.tencent.com/ssl |


## 三、账号清单

系统安装部署，需要用到资源账号清单。

**云服务**

| 账号 | 说明  | 示例 |
| --- | --- |  --- |
|  云主机远程登录账号 | Linux系统需要用到SSH账号名密码和root密码。Windows操作系统，需要提供远程登录管理员账号。 | 主机:110.119.112.189  用户名: ubuntu  密码: helloWorld+168  Root: sudo  |
|  HTTPS证书 | 启用小程序必须提供 |  |
|  云数据库账号 [选] | 提供 MySQL 账号密码。单台主机部署方案无需提供 | Host: 10.202.10.99  用户名: xpmse  密码:xpmse 数据库名: xpmse  |
|  Redis 账号 [选] | 提供 Redis 账号密码。单台主机部署方案无需提供 | Host: 10.201.10.98 密码: h3292kd |


**云平台账号**

| 账号 | 说明  | 示例 |
| --- | --- |  --- |
|  小程序 Appid | 启用小程序客户端必须提供  |  wxa71a15fe272173c1  |
|  小程序 Secret | 启用小程序客户端必须提供  |  3aee42629b49491b55391492b5111253  |
|  服务号 Appid | 使用微信扫码登录等功能必须提供  |  wxa71a15fe272173c2  |
|  服务号 Secret | 使用微信扫码登录等功能必须提供  |  5aee42629b49491b55391492b5111255  |
|  订阅号 Appid | 使同步已发布图文信息功能必须提供  |  wxa71a15fe272173c3  |
|  订阅号 Secret | 使同步已发布图文信息功能必须提供  |  6aee42629b49491b55391492b5111255  |
|  微信支付商户号 | 使付款相关功能必须提供  |  2563534402  |
|  微信支付 API KEY | 使付款相关功能必须提供  | bF3e6nnkoFva3HVzPQDAZLC  |



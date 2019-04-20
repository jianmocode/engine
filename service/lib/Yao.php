<?php
namespace Xpmse;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Excp;
use \Xpmse\Secret;
use \Xpmse\Utils;



class Yao {

    /**
     * YaoJS Backend
     * @param array $options 配置文件
     */
    function __construct( $options=[]) {

        $this->cache = new Cache( [
            "prefix" => "yao::",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

    }


    /**
     * 使用 Appid & Secret 换取 Token
     * @param string $appid API Appid
     * @param string $secret API Secret 
     * @return array 成功返回Token数据, 失败抛出异常
     */
    public function getToken( $appid, $secret ) {

        $realSecret =  (new Secret())->getSecret( $appid );
        if ( empty($realSecret) ) {
            throw new Excp("App ID is incorrect", 403, [
                "fields"=>["appid"],
                "messages" => ["appid"=>"Appid is incorrect"]
            ]);
        }

        if ( $realSecret != $secret ) {
            throw new Excp("Secret is incorrect", 403, [
                "fields"=>["secret"],
                "messages" => ["secret"=>"Secret is incorrect"]
            ]);
        }

        $token = $this->genToken();
        $ip = Utils::clientIP();
        $result = [
            "token" => $token,
            "ip" => $ip,
            "expires_in" => time() + 7200
        ];

        $resp = $this->cache->setJSON( $appid, [
            "token" => $token,
            "ip" => $ip
        ], 7200);

        if ( $resp === false ) {
            throw new Excp("Save token error", 500);
        }

        return $result;
    }

    /**
     * 退出登录
     * @param string $appid API Appid
     * @return 成功返回 true, 失败返回 false
     */
    public function exit( $appid ) {
        return $this->cache->del( $appid );
    }


    /**
     * 生成 Token
     * @param int $length 字符串长度
     * @return string 唯一token
     */
    private function genToken( $length = 32 ) {
        if(!isset($length) || intval($length) <= 8 ){
            $length = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        } 
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }

        throw new Excp("Generate token error", 500);
    }




}
<?php
/**
 * Class Yao 
 * 后端API接口
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 01:16:05
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmse\Xpmse\Api;
                                                                                                                                                                                                                                                            use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Openapi;

class Yao extends Openapi {

	/**
	 * YaoJS 后端API接口
	 * @param array $param [description]
	 */
	function __construct(  $option = []  ) {
		parent::__construct( $option );
    }

    /**
     * [GET] 检查服务器状态
     * @return string "pong"
     */
    protected function ping( $params=[], $payload=[] ) {
        return "pong";
    }


    /**
     * [POST] 验证登录请求, 并返回配置信息
     * @return array 成功返回Token数据，失败抛出异常.
     */
    protected function token( $params=[], $payload=[] ) {

        $yao = new \Xpmse\Yao();
        if ( empty($params["appid"]) ) {
            throw new Excp("Appid is incorrect", 403, [
                "fields"=>["appid"],
                "messages" => ["appid"=>"Appid is incorrect"]
            ]);
        }

        if ( empty($params["secret"]) ) {
            throw new Excp("Secret is incorrect", 403, [
                "fields"=>["secret"],
                "messages" => ["secret"=>"Secret is incorrect"]
            ]);
        }

        return $yao->getToken( $params["appid"], $params["secret"]);
    }


    /**
     * [POST] 退出登录, 清空登录数据
     * @return null 成功返回null，失败抛出异常.
     */
    protected function exit( $params, $payload ) {

        $yao = new \Xpmse\Yao();
        if ( empty($payload["appid"]) ) {
            throw new Excp("Appid is incorrect", 403, [
                "fields"=>["appid"],
                "messages" => "Appid is incorrect"
            ]);
        }

        $resp = $yao->exit( $payload["appid"] );
        if ( $resp === false ) {
            throw new Excp("Exit error", 500, [
                "fields"=>["appid"],
                "messages" => ["appid"=>"Exit error"]
            ]);
        }
    }


    protected function upload( $param, $payload  ){
        return $_FILES;
    }

}
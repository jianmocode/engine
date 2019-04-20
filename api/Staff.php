<?php
/**
 * Class Staff 
 * 后端管理员接口
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 01:16:05
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmse\Xpmse\Api;
                                                                                                                                                                                                                                                            use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Staff extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct(  $option = []  ) {
		parent::__construct( $option );
	}


    /**
     * 管理员登录
     */
    function login( $query, $data ) {

        if( empty($data["mobile"]) ) {
            throw new Excp( "请输入管理员手机号", 402 , ['_FIELD'=>'mobile', 'errorlist'=>[['mobile'=>'请输入管理员手机号']], 'mobile'=>$data["mobile"]]);
        }

        if( empty($data["password"]) ) {
            throw new Excp( "请输入管理员登录密码", 402 , ['_FIELD'=>'password', 'errorlist'=>[['password'=>'请输入管理员登录密码']],  'mobile'=>$data["mobile"]]);
        }

        $u = new \Xpmse\User;

        // 登录计数
        $loginTimes = $u->getLoginTimes();
        $u->incrLoginTimes();

        // 校验验证码
        if( $loginTimes > 3 ) {
            $this->authVcode();
        }

        $u->login($data["mobile"], $data["password"]);

        return ["code"=>0, "message"=>"登录成功"];
    }


    /**
     * 退出登录
     */
    function logout(){
        $u = new \Xpmse\User();
        $u->logout();
        return ["code"=>0, "message"=>"退出成功"]; 
    }


    /**
     * 读取会话信息
     */
    function getLoginInfo() {
        $u = new \Xpmse\User();
        $info =  $u->getLoginInfo();
        if ( $info === false ) {
            return [];
        }
        unset( $info["password"], $info["payPassword"] );
        return $info;
    }


    function search() {
        throw new Excp("本接口暂未上线", 404, []);
        $u = new \Xpmse\User();
        $list = $u->getListByIds(["6d30c45987b9fe6004a9ab21a9aa8bec"]);
        print_r( $list );
    }


    /**
	 * 上传图片文件
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function upload( $query, $data ) {

		// 读取用户资料
        $staff = \Xpmse\User::info();
        $user_id = $staff["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("管理员尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

		$resp = $this->__savefile([
			"host" => Utils::getHome(),
			"image"=>["image/png", "image/jpeg", "image/gif"]
		]);

		return $resp;
    }

}
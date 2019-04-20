<?php
namespace Xpmse;

/**
 * 员工模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *      \Xpmse\User
 *
 * USEAGE:
 *
 */


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Wxwork as Wxwork;


class User extends Model {

	/**
	 * 公司员工数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
        $this->table('user');
        $this->media = new Media(['host'=>Utils::getHome(), "root"=>"{nope}"]);
	}

	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {

		// 数据结构
		try {

			// 通用字段
			
				// 员工唯一标识ID
			$this->putColumn( 'userid', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

				// 手机号
				->putColumn( 'mobile', $this->type('string', ['unique'=>1, "null"=>false, 'length'=>80] ) )

				// 邮箱
				->putColumn( 'email', $this->type('string', ['unique'=>1, 'length'=>255] ) )

				// 姓名
				->putColumn( 'name', $this->type('string', [ "null"=>false,'length'=>80] ) )

				// 分机号
				->putColumn( 'tel', $this->type('string', ['length'=>40] ) )

				// 办公地点
				->putColumn( 'workPlace', $this->type('string', ['length'=>200] ) )

				// 备注
				->putColumn( 'remark', $this->type('string', ['length'=>200] ) )

				// 工号
				->putColumn( 'jobnumber', $this->type('string', ['unique'=>1, 'length'=>80] ) )

				// 职位
				->putColumn( 'position', $this->type('string', ['length'=>80] ) )

				// 头像
				->putColumn( 'avatar', $this->type('string', ['length'=>200] ) )

				// 扩展属性
				->putColumn( 'extattr', $this->type('text', ['json'=>true] ) )

				// 是否已经激活
				->putColumn( 'active', $this->type('boolean', ['default'=>true] ) )

				// 是否是企业管理员
				->putColumn( 'isAdmin', $this->type('boolean', ['default'=>false] ) )

				// 是否是企业主
				->putColumn( 'isBoss', $this->type('boolean', ['default'=>false] ) )

				// 是否隐藏号码
				->putColumn( 'isHide', $this->type('boolean', ['default'=>false] ) )
				

			// XpmSE平台
				
				// 登录密码
				->putColumn( 'password', $this->type('string', ['length'=>128] ) )

				// 支付密码
				->putColumn( 'payPassword', $this->type('string', ['length'=>128] ) )

				// 身份证
				->putColumn( 'idcard', $this->type('string', ['length'=>80] ) )

				// 性别
				->putColumn( 'sex', $this->type('tinyInteger', ['default'=>0] ) )

				// 生日
				->putColumn( 'birthday', $this->type('timestamp') )

				// 手机已验证
				->putColumn( 'isMobileChecked', $this->type('boolean', ['default'=>false] ) )

				// 邮箱已验证
				->putColumn( 'isEmailChecked', $this->type('boolean', ['default'=>false] ) )

				// 国家
				->putColumn( 'country', $this->type('string', ['length'=>200] ) )

				// 省份
				->putColumn( 'province', $this->type('string', ['length'=>200] ) )

				// 城市
				->putColumn( 'city', $this->type('string', ['length'=>200] ) )

				// 上次登录时间
				->putColumn( 'lastLoginAt', $this->type('timestamp') )

				// 上次登录地点
				->putColumn( 'lastLoginLocation', $this->type('string', ['length'=>200] ) )


			// 多平台账号绑定
				
				// 企业微信
				->putColumn( 'wxworkId', $this->type('string', ['length'=>200] ) )
				
				// 钉钉ID
				->putColumn( 'dingId', $this->type('string', ['length'=>200] ) )
				
				// 微信网页应用 OpenID
				->putColumn( 'wechatWebId', $this->type('string', ['length'=>200] ) )	

				// 微信服务号 OpenID
				->putColumn( 'wechatPubId', $this->type('string', ['length'=>200] ) )				

				// 微信 UniqueID
				->putColumn( 'wechatUniId', $this->type('string', ['length'=>200] ) )				

			
			// 部门信息
				
				// 部门ID列表
				->putColumn( 'department', $this->type('text', ['json'=>true] ) )

				// 所在部门排序
				->putColumn( 'orderInDepts', $this->type('text', ['json'=>true] ) )
				
				// 是否为部门主管
				->putColumn( 'isLeaderInDepts', $this->type('text', ['json'=>true] ) )
			

			// 权限信息
				
				// 功能权限
				->putColumn( 'acl', $this->type('text', ['json'=>true] ) )

			// 账户类型
				->putColumn( 'isRobot', $this->type('boolean', ['default'=>false] ) )


			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
		
	}

	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 

		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"userid"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `userid`))", 
				"email"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `email`))",
				"mobile" => "DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `mobile`))",
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}

			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
	}


	/**
	 * 创建初始化管理员 (即将废弃)
	 * @return 
	 */
	function userInit( $conf = null ) {
		return $this;
	}

	/**
	 * 是否需要初始化部门信息 (即将废弃)
	 * @return [type] [description]
	 */
	function userNeedInit() {
		return false;
	}


	/**
	 * 根据用户ID，读取用户信息
	 * @param  [type] $ids [description]
	 * @return [type]      [description]
	 */
	function getListByIds( $ids  ) {

		$result = [];
		$idstr = ( is_array($ids) ) ?  implode("','", $ids) : '';
		$resp = $this->select(" where userid in ('$idstr')" );

		if (isset($resp['data']) && is_array($resp['data'])) {
			foreach ($resp['data'] as $user ) {
				$userid = $user['userid'];
				$result[$userid] = $this->format($user);
			}
		}

		return $result;
	}


	/**
	 * 格式化用户数据
	 */
	function format( & $userData ) {

		if ( isset($userData['avatar']) ) {
			$userData['avatarUrl'] = Stor::url($userData['avatar']);
			if ( Err::isError($userData['avatarUrl']) ) {
				$userData['avatarUrl'] = null;
            }
    
            $userData["avatarinfo"] = Stor::path($userData['avatar']);
            if ( !empty($userData["avatarinfo"]) ) {
                $this->__fileFields( $userData, ["avatarinfo"]);
            }
        }

        // 升级用户数据字段
        if ( isset( $userData["_id"]) ) {
            $userData["staff_id"] = $userData["_id"];
            $userData["user_id"] = $userData["_id"];
        }
        
		return $userData;
	}


	function wxworkData( & $userData  ) {

		$userData['isAdmin'] = $userData['isleader'];
		$userData['tel'] = $userData['telephone'];
		$userData['sex'] = $userData['gender'];
		$userData['active'] = $userData['enable'];
		$userData['active'] = $userData['isHide'];
		$userData['wxworkId'] = $userData['userid'];
		
		if ( isset($userData['avatar']) ) {
			$stor = new Stor;
			$name = $userData['name'];
			$name = '/u/'. md5( $name ) . ".jpg";
			$bucket = ( Conf::G('defaults/storage/public') !== null) ? Conf::G('defaults/storage/public') : 'local://public';
			$url = $stor->put("$bucket::{$name}", $userData['avatar'] );			
			$userData['avatar'] = "$bucket::{$name}";
		}


		return $userData;

	}


	/**
	 * 根据名称生成一个用户头像
	 */
	function genAvatar( $name ) {
		
		$ut = new Utils();
		mb_internal_encoding("UTF-8");

		$colors = [
			'dark' => ['#666699', '#0099CC', '#99CC00', '#009933', '#CCCC33','#336699','#003399'],
			'light' => ['#FFFFFF', '#FEFEFE'],
		];

		$dark_idx = rand(0, count($colors['dark']) -1 );
		$light_idx =  rand(0, count($colors['light']) -1 );

		$dark = $colors['dark'][$dark_idx];
		$light = $colors['light'][$light_idx];


		$len = mb_strlen($name);
		$pname = $name;

		if ( $len > 2 ) {
			$pname = mb_substr($name, -2);
		}


		$opt = [
			"text" => $pname,
			"font-style" => "黑体",
			"font-color" => $light,
			"background-color" => $dark,
			"font-size" => 48,
			"width"=>150,
			"height"=>150,
			"text-align" => "center",
			"text-valign" => "middle"
		];


		$image = $ut->ImageText($opt);
		$stor = new Stor();
		$bucket = ( Conf::G('defaults/storage/public') !== null) ? Conf::G('defaults/storage/public') : 'local://public';
		$n = $stor->genName('jpg');
		$n['name'] = '/u/'.md5( $name ) . ".jpg";
		$ret = $stor->putData("$bucket::{$n['name']}", $image );
		if ( Err::isError($ret) ) {
			throw new Excp("生成头像出错啦", 500, ["name"=>$name, "file"=>"$bucket::{$n['name']}", "message"=>$ret->message]);
		}

		$url = $stor->getUrl("$bucket::{$n['name']}");
		return ['avatar'=>"$bucket::{$n['name']}", 'avatarUrl'=>$url];

		// header('Content-Type:image/jpeg');
		// echo $image;
	}


	/**
	 * 生成一个唯一的ID
	 * @return [type] [description]
	 */
	function genUserid() {
		$str = uniqid(mt_rand(),1);
		return md5( $str );
	}


	function create( $data ){

		if ( !array_key_exists('userid', $data)) {
			$data['userid'] = $this->genUserid();
		}

		if ( empty($data['department'])) {
			$data['department'] = [1];
		}

		if ( empty($data['avatar']) ) {
			$avatar = $this->genAvatar( $data['name'] );
			$data['avatar'] = $avatar['avatar'];
		}

		return parent::create($data);
	}



	/**
	 * 读取用户登录次数
	 * @param string $ip [description]
	 */
	function getLoginTimes( $ip=null ) {
		$ut = new Utils;
		$mem = new Mem(false, 'user:');

		$ip = ($ip == null) ? $ut->getClientIP() : $ip;
		$cache = "login:{$ip}";
		$times = $mem->get($cache);
		if ( $times === false ) $times = 0;

		return intval($times);
	}


	/**
	 * 读取用户登录出错次数
	 * @param string $ip [description]
	 */
	function getLoginErrorTimes( $mobile ) {
		$mem = new Mem(false, 'user:');
		$ut = new Utils;
		$ip = $ut->getClientIP();

		$cache = "login:{$ip}:{$mobile}:error";
		$times = $mem->get($cache);
		if ( $times === false ) $times = 0;

		return intval($times);
	}


	/**
	 * 增加用户登录次数
	 * @param  [type]  $ip      [description]
	 * @param  integer $expires [description]
	 * @return [type]           [description]
	 */
	function incrLoginTimes( $ip=null, $expires=600 ) {
		$ut = new Utils;
		$mem = new Mem(false, 'user:');

		$ip = ($ip == null) ? $ut->getClientIP() : $ip;
		$cache = "login:{$ip}";
		$times = $mem->get($cache);

		if ( $times === false ) {
			$times = 0;
		}
		

		$newTimes = intval($times) + 1 ;
		return $mem->set( $cache, $newTimes,  $expires );

	}


	/**
	 * 清空登录次数记录
	 * @param  [type] $ip [description]
	 * @return [type]     [description]
	 */
	function cleanLoginTimes( $ip=null ) {
		$ut = new Utils;
		$mem = new Mem(false, 'user:');
		$ip = ($ip == null) ? $ut->getClientIP() : $ip;
		$cache = "login:{$ip}";
		return $mem->del( $cache );
	}



	/**
	 * 增加用户登录出错次数
	 * @param  [type]  $ip      [description]
	 * @param  integer $expires [description]
	 * @return [type]           [description]
	 */
	function incrLoginErrorTimes( $mobile, $expires=1800 ) {

		$mem = new Mem(false, 'user:');
		$ut = new Utils;
		$ip = $ut->getClientIP();

		$cache = "login:{$ip}:{$mobile}:error";
		$times = $mem->get($cache);

		if ( $times === false ) {
			$times = 0;
		}

		$newTimes = intval($times) + 1 ;
		return $mem->set( $cache, $newTimes,  $expires );
	}


	/**
	 * 清空用户登录出错次数
	 * @param  [type] $ip [description]
	 * @return [type]     [description]
	 */
	function cleanLoginErrorTimes( $mobile  ) {
		$mem = new Mem(false, 'user:');
		$ut = new Utils;
		$ip = $ut->getClientIP();
		$cache = "login:{$ip}:{$mobile}:error";
		return $mem->del( $cache );
	}


	/**
	 * 校验用户密码是否正确 (自动读取Mobile数据 )
	 * @param  [type] $mobile   手机号码
	 * @param  [type] $password 用户密码
	 * @return [type]           用户密码
	 */
	function checkPasswordByMobile( $mobile, $password ) {

		$resp = $this->select("WHERE mobile='$mobile' LIMIT 1", ['password']);
		
		if ( $resp === false ) { //数据库查询异常
			throw new Excp('数据查询错误', '500', ['resp'=>$resp, 'sql'=>"WHERE mobile='$mobile' LIMIT 1"] );
		}

		if ( intval($resp['total']) == 0 ) { // 用户不存在
			return null;
		}

		$row = end($resp);
		$hash = $row['password'];
		return $this->checkPassword($password, $hash);
	}


	/**
	 * 校验用户密码是否正确 
	 * @param  [type] $password [description]
	 * @param  [type] $hash     [description]
	 * @return [type]           [description]
	 */
	function checkPassword( $password, $hash ) {
		return password_verify($password, $hash);
	}

	/**
	 * Password Hash
	 * @param  [type] $password [description]
	 * @return [type]           [description]
	 */
	function hashPassword( $password ) {
		return password_hash( $password, PASSWORD_BCRYPT, ['cost'=>12] );
	}

	/**
	 * Password Hash
	 * @param  [type] $password [description]
	 * @return [type]           [description]
	 */
	function hashPassowrd( $password ) {
		return password_hash( $password, PASSWORD_BCRYPT, ['cost'=>12] );
	}

	/**
	 * 设置用户登录信息
	 */
	function setSession( $_id ) {
		@session_start();

		$ut = new Utils;
		$resp = $this->update($_id, [
			'lastLoginAt' => date('Y-m-d h:i:s'),
			'lastLoginLocation' => $ut->getClientIP(),
		]);

		$resp = $this->format( $resp );

		// 读取部门信息
		$depts = (is_array($resp['department'])) ? $resp['department'] : [1];
		$idstr = implode(',', $depts);
		$dept = M('Department');
		$deptData = $dept->select( "where id in ( $idstr ) ");

		if ( is_array($deptData['data']) ) {

			foreach ($deptData['data'] as $idx=>$dp ) {
				$id = $dp['id'];
				$_id = $dp['_id'];
				$dp['isManager'] = $dept->isManager($resp['userid'], $dp);
				$resp['dept_detail']['_id'][$_id] = $dp;
				$resp['dept_detail']['id'][$id] = $dp;
				$resp['dept_detail']['data'][] = $dp;
			}

			// $resp['dept_detail'] =  $deptData['data'];
		} else {
			$resp['dept_detail'] = ['_id'=>[],'id'=>[],'data'=>[]];
		}

		if ($resp === false ) return false;
		$_SESSION['user/login/info'] =  json_encode($resp);

		return true;
	}


	/**
	 * 读取用户登录信息
	 * @return [type] [description]
	 */
	function getLoginInfo() {
		@session_start();
		if ( !isset($_SESSION['user/login/info']) ) {
			return false;
		}

		$resp = json_decode( $_SESSION['user/login/info'], true );

		if( json_last_error() !== JSON_ERROR_NONE) {
			return false;
        }
        
		return $resp;
    }


    /**
     * 读取用户登录信息
     */
    public static function Info() {
        $inst = new Self();
        $info =  $inst->getLoginInfo();
        if ( $info === false ) {
            return [];
        }
        return $info;
    }
    

    /**
     * 用户名密码登录
     * @param string $mobile 管理员手机号码
     * @param string $password 管理员登录密码
     * @return bool 成功返回 true, 失败抛出异常
     */
    public function login( $mobile, $password) {

        // 校验是否开放/用户名密码登录( @setting )

        $mobile = trim($mobile);
        $password = trim($password);

        $loginTimes = $this->getLoginTimes();
		$loginError = $this->getLoginErrorTimes( $mobile );

        // 锁定账号
		if ( intval( $loginError ) > 5  ) {
            throw new Excp( "密码错误超过5次, 账号锁定30分钟。", 403 , ['_FIELD'=>'mobile', 'loginTimes'=>$loginTimes, "loginError"=>$loginError]);
        }
        
        // 读取用户信息
		$user = $this->getLine("WHERE mobile=? and active=1  LIMIT 1", ['_id','password'], [$mobile]);
		if ( empty($user) ) {
            throw new Excp( "管理员账号不存在", 402 , ['_FIELD'=>'mobile',  'errorlist'=>[['mobile'=>'管理员账号不存在']],   'mobile'=>$mobile, 'loginTimes'=>$loginTimes, "loginError"=>$loginError]);
        }

        // 校验用户密码
        if ( $this->checkPassword( $password , $user['password']) === false ) {
            $this->incrLoginErrorTimes( $mobile );
            throw new Excp( "登录密码不正确", 403 , ['_FIELD'=>'password', 'errorlist'=>[['password'=>'登录密码不正确']],  'loginTimes'=>$loginTimes, "loginError"=>$loginError]);
        }

	    // 密码验证成功
		$this->cleanLoginErrorTimes($mobile);
        $this->cleanLoginTimes();

        if ( $this->setSession($user['_id']) === false ) {
            throw new Excp('系统错误(写入会话数据失败)。', 500, ['_FIELD'=>'error', 'loginTimes'=>$loginTimes] );
        }

        return true;
    }



	/**
	 * 退出登录
	 */
	public function logout() {
		@session_start();
		
		if ( Conf::G('wechat/option/encode') === true ) {
			$name = Conf::G('wechat/option/login');
			$wechat = new Wechat($name);
			$wechat->cleanAuthSession();
		}

		unset($_SESSION['user/login/info']);
	}

}

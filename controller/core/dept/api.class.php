<?php
// XpmSE账号管理API

include_once( AROOT . 'controller' . DS . 'private.class.php' );





use \Xpmse\Mem as Mem;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Err as Err;
use \Xpmse\Excp as Excp;


class coreDeptApiController extends privateController {

	
	function __construct() {
		// 载入默认的
		parent::__construct(['UserLogin']);
	}


	/**
	 * 删除一个部门
	 */
	function DeptDelete() {
		
		$dept = M('Department');
		$_id = ( isset($_GET['_id']) ) ? intval($_GET['_id']) : null;

		if ( $_id == null ) {
			$e = new Excp( '未指定用户，请指定部门数据记录ID', '500', ['_id'=>null]);
			$e->log();
			echo $e->error->toJSON();
			return ;
		}

		$resp = $dept->remove( $_id );

		if ( $resp === false ) {
			$extra = [];
			$errors = (is_array($dept->errors)) ? $dept->errors : [];

			foreach ($errors as $cname=>$error ) {
				$error = (is_array($error)) ? end($error) : [];
				$field = (isset($error['field'])) ? $error['field'] : 'error';
				$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
				$extra[] = ['_FIELD'=>$field,'message'=>$message];
			}

			$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
				$e->log();
				echo $e->error->toJSON(); 

			return;
		}

		echo json_encode(['code'=>0, 'message'=>'ok']);
	}



	/**
	 * 保存部门信息
	 */
	function DeptSave() {
		
		$dept = M('Department');
		$_id = ( isset($_POST['_id']) ) ? intval($_POST['_id']) : null;
		$req = $_POST;


		// 用户提交数据处理
		$boolData = ['createDeptGroup','autoAddUser','deptHiding','outerDept'];
		$intData = ['parentid','order'];
		$intArrayData = ['deptPerimits','outerPermitDepts'];

		foreach ($boolData as $name ) {
			(isset($req[$name])) &&  $req[$name] = ($req[$name] == "1" || $req[$name] === true) ? true : false;
		}
		foreach ($intData as $name ) {
			(isset($req[$name])) &&  $req[$name] = intval($req[$name]);
		}
		foreach ($intArrayData as $name ) {
			if (isset($req[$name]) && is_array($req[$name])) {
		    	foreach ($req[$name] as $idx => $dept_id) {
		    		 $req[$name][$idx] = intval($dept_id); 
		    	}
		    }
		}


		// 特殊数值处理
		if ( !isset($req['parentid']) ) {
			$req['parentid'] = 1;
		}


		if ( $_id == 0 || $_id == null ) { // Create Dept

			$req['id'] = $dept->nextid();
			$resp = $dept->create( $req );
			
			// 提交数据异常
			if ( $resp  === false ) { 
				$extra = [];
				$errors = (is_array($dept->errors)) ? $dept->errors : [];

				foreach ($errors as $cname=>$error ) {
					$error = (is_array($error)) ? end($error) : [];
					$field = (isset($error['field'])) ? $error['field'] : 'error';
					$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
					$extra[] = ['_FIELD'=>$field,'message'=>$message];
				}

				$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
					$e->log();
					echo $e->error->toJSON(); 

				return ;
			}

			echo json_encode(['code'=>0, 'message'=>'ok', 'data'=>$resp]);

		} else {  // Update Dept

			$resp = $dept->update( $_id, $req );
			
			// 提交数据异常
			if ( $resp  === false ) { 
				$extra = [];
				$errors = (is_array($dept->errors)) ? $dept->errors : [];

				foreach ($errors as $cname=>$error ) {
					$error = (is_array($error)) ? end($error) : [];
					$field = (isset($error['field'])) ? $error['field'] : 'error';
					$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
					$extra[] = ['_FIELD'=>$field,'message'=>$message];
				}

				$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
					$e->log();
					echo $e->error->toJSON(); 

				return ;
			}

			echo json_encode(['code'=>0, 'message'=>'ok', 'data'=>$resp]);
		}

	}




	/**
	 * 删除一个用户
	 */
	function UserDelete() {
		
		$user = M('User');
		$_id = ( isset($_GET['_id']) ) ? intval($_GET['_id']) : null;

		if ( $_id == null ) {
			$e = new Excp( '未指定用户，请指定用户数据记录ID', '500', ['_id'=>null]);
			$e->log();
			echo $e->error->toJSON();
			return ;
		}

		$resp = $user->remove( $_id );

		if ( $resp === false ) {
			$extra = [];
			$errors = (is_array($user->errors)) ? $user->errors : [];

			foreach ($errors as $cname=>$error ) {
				$error = (is_array($error)) ? end($error) : [];
				$field = (isset($error['field'])) ? $error['field'] : 'error';
				$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
				$extra[] = ['_FIELD'=>$field,'message'=>$message];
			}

			$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
				$e->log();
				echo $e->error->toJSON(); 

			return;
		}

		echo json_encode(['code'=>0, 'message'=>'ok']);
	}



	/**
	 * 保存用户信息API
	 */
	function UserSave() {

		// 验证用户身份
		if ( ! $this->user['isAdmin'] && $this->user['_id'] != $_POST['_id'] ) {
			throw new Excp("没有权限访问", 403);
		}

		
		$user = M('User');
		$_id = ( isset($_POST['_id']) ) ? intval($_POST['_id']) : null;
        $req = $_POST;
        
        // 校验密码
        if ( 
            !empty($req['password']) && 
            $req['password'] != $req['repassword']
        ) {
            throw new Excp("两次输入密码不一致", 402, ["data"=>$req, "_FIELD"=>"password"]);
        }

		// 用户提交数据处理
	    isset($req['active']) && $req['active'] = ($req['active']== "1" || $req['active'] == true  || $req['active'] == 'on') ? true : false;
	    isset($req['isAdmin']) && $req['isAdmin'] = ($req['isAdmin']== "1" || $req['isAdmin'] == true || $req['isAdmin'] == 'on') ? true : false;
	    isset($req['isBoss']) && $req['isBoss'] = ($req['isBoss']== "1" || $req['isBoss'] == true  || $req['isBoss'] == 'on') ? true : false;
	    isset($req['isHide']) && $req['isHide'] = ($req['isHide']== "1" || $req['isHide'] == true  || $req['isHide'] == 'on') ? true : false;


	    (isset($req['isMobileChecked'])) &&  $req['isMobileChecked'] = ($req['isMobileChecked']== "1"  || $req['isMobileChecked'] == true) ? true : false;
	    (isset($req['isEmailChecked'])) &&  $req['isEmailChecked'] = ($req['isEmailChecked']== "1" || $req['isEmailChecked'] == true ) ? true : false;
	    (!empty($req['password'])) &&  $req['password'] = password_hash( $req['password'], PASSWORD_BCRYPT, ['cost'=>12] );
	    (!empty($req['payPassword'])) &&  $req['payPassword'] = password_hash( $req['payPassword'], PASSWORD_BCRYPT, ['cost'=>12] );
	    (isset($req['sex'])) &&  $req['sex'] = intval($req['sex']);
	    (isset($req['birthday'])) &&  $req['birthday'] = "{$req['birthday']} 00:00:00";

		if (isset($req['department']) && is_array($req['department'])) {
	    	foreach ($req['department'] as $idx => $dept) {
	    		 $req['department'][$idx] = intval($dept); 
	    	}
	    }

	    
	    // 用户无法给自身提升权限
	    if ( ! $this->user['isAdmin'] ) {
			unset($req['isAdmin']);
			unset($req['isBoss']);
			unset($req['isHide']);
			unset($req['active']);
			unset($req['isMobileChecked']);
			unset($req['isEmailChecked']);
		}


		if ( $_id == 0 || $_id == null ) { // Create User

			$req['userid'] = $user->genUserid();
			if ( !isset($req['avatar']) ) {
				$avatar = $user->genAvatar($req['name']);
				$req['avatar'] = $avatar['avatar'];
			}

			$resp = $user->create( $req );
			
			// 提交数据异常
			if ( $resp  === false ) { 
				$extra = [];
				$errors = (is_array($user->getErrors())) ? $user->getErrors() : [];

				foreach ($errors as $cname=>$error ) {
					$error = (is_array($error)) ? end($error) : [];
					$field = (isset($error['field'])) ? $error['field'] : 'error';
					$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
					$extra[] = ['_FIELD'=>$field,'message'=>$message];
				}

				$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
					$e->log();
					echo $e->error->toJSON(); 

				return ;
			}

			echo json_encode(['code'=>0, 'message'=>'ok', 'data'=>$resp]);

		} else {  // Update User

			$resp = $user->update( $_id, $req );
			
			// 提交数据异常
			if ( $resp  === false ) { 
				$extra = [];
				$errors = (is_array($user->errors)) ? $user->errors : [];

				foreach ($errors as $cname=>$error ) {
					$error = (is_array($error)) ? end($error) : [];
					$field = (isset($error['field'])) ? $error['field'] : 'error';
					$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
					$extra[] = ['_FIELD'=>$field,'message'=>$message];
				}

				$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
					$e->log();
					echo $e->error->toJSON(); 

				return ;
			}

			echo json_encode(['code'=>0, 'message'=>'ok', 'data'=>$resp]);
		}

	}



	/**
	 * 用户登录API
	 * @return [type] [description]
	 */
	function UserLogin() {

		// header('HTTP/1.1 503 Service Temporarily Unavailable');die();
		@session_start();
		$type = ( isset($_POST['type']) ) ? $_POST['type'] : 'password';
		$vcode = (isset($_POST['vcode']) ) ? $_POST['vcode'] : "";
		$vcodename = (isset($_POST['vcodename']) ) ? "vcode:".session_id().":{$_POST['vcodename']}" : null;
		$mem = new Mem;
		$user =  M('User');

		switch ($type) {

			// 用户名密码登录逻辑
			case 'password':

				if ( $this->login['password'] ===  false ) {  // @see publicController
					$err = new Err('503', '不允许使用用户名密码登录', ['_POST'=>$_POST, 'loginTimes'=>$loginTimes]);
					echo $err->toJSON();
					return;
				}


				$mobile = $_POST['mobile'];
				$password = $_POST['password'];
				$loginTimes = $user->getLoginTimes();
				$loginError = $user->getLoginErrorTimes( $mobile );
				$user->incrLoginTimes();  // 登录计数


				if ( intval($loginTimes) > 3  ) {

					if ( empty($vcodename) ) {
						$err = new Err('302', '验证码不正确重新载入', 
							['_FIELD'=>'vcode', '_POST'=>$_POST, 'session'=>session_id(), 'vcodename'=>$vcodename, 'vcode'=>$mem->get($vcodename), 'loginTimes'=>$loginTimes] );
						echo $err->toJSON();
						return;
					}

					if ($vcode != $mem->get($vcodename) ) {
						$err = new Err('503', '图片验证码不正确', 
							['_FIELD'=>'vcode', '_POST'=>$_POST, 'session'=>session_id(), 'vcodename'=>$vcodename, 'vcode'=>$mem->get($vcodename), 'loginTimes'=>$loginTimes] );
						echo $err->toJSON();
						return;
					} else {
						 $mem->del($vcodename);
					}
				}

				if ( intval( $loginError ) > 3  ) {
					$err = new Err('503', '密码错误超过3次，账号锁定30分钟。', 
							['_FIELD'=>'mobile', '_POST'=>$_POST, 'session'=>session_id(), 'vcodename'=>$vcodename, 'vcode'=>$mem->get($vcodename), 'loginTimes'=>$loginTimes] );
						echo $err->toJSON();
						return;
				}



				// 读取用户信息
				$resp = $user->select("WHERE mobile='$mobile'  LIMIT 1", ['_id','password']);
				if ( $resp === false ) {
					$e = new Excp('数据查询错误', '500', ['resp'=>$resp, 'sql'=>"WHERE mobile='$mobile' LIMIT 1"] );
					$e->log();
					echo $e->toJSON();
					return;
				}

				// 用户不存在
				if ( intval($resp['total']) === 0 ){
					$err = new Err('404', '用户不存在', 
						['_FIELD'=>'mobile', '_POST'=>$_POST, 
						 'session'=>session_id(), 'vcodename'=>$vcodename, 'vcode'=>$mem->get($vcodename), 
						 'loginTimes'=>$loginTimes, 'resp'=>$resp,
						 'sql'=>"WHERE mobile='$mobile'  LIMIT 1"
						 ]);
					echo $err->toJSON();
					return;
				}

				$userData = end($resp['data']);

				// 校验用户名密码
				if (  $user->checkPassword( $password , $userData['password']) === false ){

					$user->incrLoginErrorTimes( $mobile );

					$err = new Err('503', '登录密码不正确', 
						['_FIELD'=>'password', '_POST'=>$_POST, 'session'=>session_id(), 'vcodename'=>$vcodename, 'vcode'=>$mem->get($vcodename), 'loginTimes'=>$loginTimes] );
					
					echo $err->toJSON();

					return;
				}



				// 密码验证成功
				$user->cleanLoginErrorTimes($mobile);
				$user->cleanLoginTimes();


				// 登录系统
				if ( $user->setSession($userData['_id']) === false ) {
					$e = new Excp( '系统错误,请联系管理员。', '500',
						['_FIELD'=>'error', '_POST'=>$_POST, 'session'=>session_id(), 'user'=>$userData] );
					$e->log();
					echo $e->error->toJSON();
					return;
				}


				// 清空菜单缓存
				M('Menu')->cleanCache();

				echo json_encode(['code'=>0, 'message'=>'ok']);
				break;
			
			default:
				
				break;
		}
	}

}
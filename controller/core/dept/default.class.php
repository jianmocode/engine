<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Endroid\QrCode\QrCode;

class coreDeptDefaultController extends privateController  {

	function __construct() {

		// 载入默认的
		parent::__construct(['imagetest'] , ['icon'=>'si-notebook', 'icontype'=>'si', 'cname'=>'通讯录']);
	}


	// 通讯录主页
	function index() {

		// 验证用户身份
		if ( ! $this->user['isAdmin'] ) {
			throw new Excp("没有权限访问", 403);
		}
		
		$dept_id = (isset($_GET['_id']))? intval($_GET['_id']) : 0;
		$page = (isset($_GET['page']))? intval($_GET['page']) : 1;
		
		// 导航
		$this->_crumb('企业通讯录', R('core-dept','default','index') );
		$this->_crumb('联系人');


		$dept = M('Department')->deptInit();
		
		if ( $dept_id > 0 ) {
			$currDept = $dept->get($dept_id);
		} else {
			$currDept = $dept->select('where id=1 LIMIT 1');
			if ( $currDept ===  false ) {
				$currDept = [];
			} else{
				$currDept = $currDept['data'][0];
			}
		}

		$this->_manager($currDept);  // 标记是否为当前部门主管


		$active_id = ( $dept_id == 0 ) ? intval($currDept['_id']) : $dept_id;
		$deptData = $dept->deptJSON( 0, $active_id, R('core-dept','default','index', ['_id'=>'{_ID}','page'=>$page] ), true );
		$userCnt  = $dept->userCount( $currDept['_id'] );
		
		if ( $userCnt > 24 ) {
			$userData = $dept->userList( $currDept['id'] , true, $page, 24 );
		} else {
			$userData = $dept->userList( $currDept['id']  );
		}

		$data = $this->_data(['depts' => $deptData, 'users'=>$userData, 'curr'=>$currDept]);




		// 网页版
		render( $data, 'core/dept/web', 'index');

		// wprint_r($data);

		// $resp = $user->query(['@where'=>'department in (1,3,4)']);
		// $resp = $user->select('where department = 4');
		// echo "<pre>";
		// print_r( $currDept );
		// print_r($userData );
		// print_r( $cnt );
		// echo "</pre>";
	}

	// 添加部门
	function deptpanel() {

		// 验证用户身份
		if ( ! $this->user['isAdmin'] ) {
			throw new Excp("没有权限访问", 403);
		}


		$id = (isset($_GET['_id']))? intval($_GET['_id']) : null;
		$parent_id = (isset($_GET['_dept_id']))? intval($_GET['_dept_id']) : null; 


		// 导航
		$this->_crumb('企业通讯录', R('core-dept','default','index') );

		if ($id == null ) {
			$this->_crumb('添加部门');
			// $data = array_merge($this->_dataInit(), ['form' => 'deptform', 'tab_title'=>'添加部门', '_id'=>null, '_dept_id'=>$parent_id]);
			$data = $this->_data( ['form' => 'deptform', 'tab_title'=>'添加部门', '_id'=>null, '_dept_id'=>$parent_id] );
		} else {
			$this->_crumb('查看部门');
			$this->_manager( $id );

			// $data = array_merge($this->_dataInit(), ['form' => 'deptform', 'tab_title'=>'查看部门', '_id'=>$id, '_dept_id'=>$parent_id]);
			$data = $this->_data( ['form' => 'deptform', 'tab_title'=>'查看部门', '_id'=>$id, '_dept_id'=>$parent_id] );
		}

		// 网页版
		render( $data, 'core/dept/web', 'form');
	}


		// 载入添加/修改部门表单
		function deptform() {

			// 验证用户身份
			if ( ! $this->user['isAdmin'] ) {
				throw new Excp("没有权限访问", 403);
			}

			// 根据数据表ID更新
			$id = (isset($_GET['_id']))? intval($_GET['_id']) : null;
			$parent_id = (isset($_GET['_dept_id']))? intval($_GET['_dept_id']) : null;

			$dept = M('Department')->deptInit();
			$deptData = $dept->deptList();

			$currDept = null;
			$children = null;
			$deptUsers = $dept->userList(); // 所有用户列表
			
			if ( $id != null ) { 
			
				$currDept = $dept->get( $id ); // 当前部门列表
				$deptUsers = $dept->userList($currDept['id']); // 当前部门用户列表

				// 读取子部门列表
				$children = $dept->deptChildrenList($currDept['id']);

				// 读取上级部门列表
				$uplevelData = $dept->deptUpLevelList($currDept['id']);

			}

			$data = $this->_data([
				'depts'=>$deptData, 
				'users'=>$deptUsers, 
				'curr'=>$currDept, 
				'children'=>$children, // 子部门清单
				'uplevel'=>$uplevelData, // 上级部门清单
				'parent_id'=>$parent_id
			]);

			
			// 网页版
			render( $data, 'core/dept/web/form', 'deptform');

			// echo "<pre>";
			// print_r($data['children']);
			// print_r($data['uplevel']);

			// print_r($data['children']);

			// $resp = 'HELLO WORD';
			// $result = $dept->deptEach(function($dept, $resp ) {
			// 	echo "==== $resp ===== \n";
			// 	print_r($dept);
			// 	echo "==== END $resp ===== \n";
			// 	return $dept['name'];
			// }, 0, $resp );
			// print_r($result);

			// print_r($data['depts']);
			// print_r($data['users']);
			// echo "</pre>";

		}

		


	//添加用户
	function userpanel() {

		// 验证用户身份
		if ( ! $this->user['isAdmin'] && $this->user['_id'] != $_GET['_id'] ) {
			throw new Excp("没有权限访问", 403);
		}

		$id = (isset($_GET['_id']))? intval($_GET['_id']) : null;
		$dept_id = (isset($_GET['_dept_id']))? intval($_GET['_dept_id']) : null; 

		
		// 导航
		$this->_crumb('企业通讯录', R('core-dept','default','index') );

		if ($id == null ) {
			$this->_crumb('添加员工');
			$data = $this->_data(['form' => 'userform', 'tab_title'=>'添加员工', '_id'=>null, '_dept_id'=>$dept_id]);
		} else {
			$this->_crumb('查看员工');
			$data = $this->_data(['form' => 'userform', 'tab_title'=>'查看员工', '_id'=>$id]);
		}


		// 网页版
		render( $data, 'core/dept/web', 'form');
	}

		// 载入添加/修改用户表单
		function userform() {

			// 验证用户身份
			if ( ! $this->user['isAdmin']  && $this->user['_id'] != $_GET['_id'] ) {
				throw new Excp("没有权限访问", 403);
			}
			
			// 根据数据表ID更新
			$id = (isset($_GET['_id']))? intval($_GET['_id']) : null;
			$dept_id = (isset($_GET['_dept_id']))? intval($_GET['_dept_id']) : null; 
			
			$dept = M('Department')->deptInit();
			$deptData = $dept->deptList();
			$userData = [];
			

			if( $id != null ) {
				// 读取用户信息
				$user = M('User');
				$userData = $user->get($id, true);

				$user->format( $userData );
			}


			$data = $this->_data( ['depts'=>$deptData, 'user'=>$userData, '_dept_id'=>$dept_id]);


			
			// 网页版
			render( $data, 'core/dept/web/form', 'userform');
		}




	function user() {

        Utils::cliOnly();

			// 验证用户身份
		if ( ! $this->user['isAdmin'] ) {
			throw new Excp("没有权限访问", 403);
		}

		$usr = M('User');
		try {
			$resp = $usr->create(['userid'=>'92832','name'=>'刘志强',
						'department'=>[1,3,4],
						'orderInDepts'=>["1"=>10, "3"=>3, "4"=>1],
						'deptManagerUseridList'=>["3"=>false, "1"=>false, "4"=>true],
					]);

			$resp = $usr->create(['userid'=>'92831','name'=>'薛志东',
						'department'=>[1,3,4],
						'orderInDepts'=>["1"=>10, "3"=>3, "4"=>1],
						'deptManagerUseridList'=>["3"=>false, "1"=>false, "4"=>true],
					]);

			$resp = $usr->create(['userid'=>'92830','name'=>'李华',
						'department'=>[1,3,4],
						'orderInDepts'=>["1"=>10, "3"=>3, "4"=>1],
						'deptManagerUseridList'=>["3"=>false, "1"=>false, "4"=>true],
					]);


			$resp = $usr->create(['userid'=>'92830','name'=>'王志敏',
						'department'=>[1,2],
						'orderInDepts'=>["1"=>10, "2"=>1],
						'deptManagerUseridList'=>["1"=>false, "2"=>true],
					]);

		} catch( Exception $e ) {
			Excp::elog( $e );
		} catch ( Excp $e ) {
			$e->log();
		}

		if ( $resp === false ) {
			echo "<pre>";
			print_r( $usr->errors );
			echo "</pre>";
		}

		var_dump( $resp );

	}

	/**
	 * 账号资料信息
	 */
	function userprofile(){

		$this->_app(['icon'=>'si-info', 'icontype'=>'si', 'cname'=>'账号安全']);
		$this->_crumb('账号资料', R('core-dept','default','userprofile') );
	    $this->_crumb('修改资料');

		// 获取部门信息
		$dept = M('Department')->deptInit();
		$deptData = $dept->deptList();

		// 当前用户的ID
		$id  =  $this->user["_id"];
		$userData = [];
		if( $id != null ) {
			// 读取用户信息
			$user = M('User');
			$userData = $user->get($id, true);
			$user->format( $userData );
        }
        
        $data = $this->_data( ['depts'=>$deptData, 'user'=>$userData,'form' => 'deptform','tab_title'=>'我的资料']);
        
        if ($_GET["debug"]) {
            echo "<!-- \n";
            Utils::out( $data );
            echo "\n -->";
            exit;
        }

		render( $data,'core/dept/web', 'userprofile');

	}

	

	function profilesave(){
		
		$user = M('User');

		$_id  =  $this->user["_id"];
		$req = $_POST;

		
		if (!empty($req['birthday'])) {
		  $req['birthday'] = $req['birthday']." "."00:00:00";
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




}
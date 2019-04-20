<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );





use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Acl as Acl;
use \Xpmse\Tuan as Tuan;

class CoreSystemAclmanagerController extends privateController {

	function __construct() {
		// 载入默认的
		parent::__construct([],'system','aclmanager');
	}

	
	/**
	 * 权限管理入口页
	 */
	function index() {

		$appid = isset( $_GET['appid'] ) ? trim($_GET['appid']) : "";
		$app = M('App');
		$apps = $app->getInstalled( true );
		$data = $this->_data(['apps'=>$apps['data'], 'current'=>$appid],'权限管理', '权限管理');
		render( $data, 'core/system/web', 'aclmanager');
	}

		/**
		 * 菜单权限表单
		 */
		function tabsMenu() {

			// 读取菜单清单
			$appid = ( isset( $_GET['appid'] )  && !empty($_GET['appid']) )? trim($_GET['appid']) : "0";
			$menu = M('Menu');
			$menuData = $menu->flatMenu();
			$acl = new Acl();
			

			$data =[];
			if ( $appid == "0" ) {
				$data['menulist'] = $menuData['sys'];
			} else {
				$data['menulist'] = isset( $menuData['app'][$appid] ) ? $menuData['app'][$appid] : [];
			}

			// 权限清单
			$aclList = $acl->getByApp( $appid, true );

			// 读取菜单权限
			foreach ($data['menulist'] as $idx=>$menu ) {
				if ( $acl->isRegister($menu['acl']['key'], $aclList ) === false ) {
					$data['menulist'][$idx]['acl'] = $acl->register( $menu['acl'] );
				} else {
					$data['menulist'][$idx]['acl'] = $aclList['map'][$menu['acl']['key']];
				}
			}

			// 从数据库中，读取菜单权限表
			
			// echo "<pre>";
			// echo "Appid=$appid\n";
			// echo "</pre>";

			render( $data, 'core/system/web/aclmanager', 'menu');
		}

		/**
		 * 功能权限
		 * @return [type] [description]
		 */
		function tabsFunc() {
			$data = [];	
			$acl = new Acl();
			// $resp = $acl->getDepts([1,178,179,180,18], true);
			$tuan = new Tuan();
			$resp = $tuan->call('/dept/get',[],['ids'=>[1,178,179,180,18]]);

			echo "<pre>";
			print_r($resp);
			echo "</pre>";
		}

		/**
		 * API 权限
		 * @return [type] [description]
		 */
		function tabsApi() {

			$data = [];
		}

	/**
	 * 权限选择器
	 */
	function aclSelector() {
		
		$acl = new Acl();
		$dept = M('Department');

		$data = [];
		$data['roles'] = $acl->getRoles();  // 角色清单
		$data['depts'] = $dept->deptTreeList();  // 部门清单 ['data'=>[], 'map'=>[], 'total'=>0]
		$data['users'] = $dept->userList();// 用户清单 ['data'=>[],  'total'=>0 ]
		$data['title'] = (isset($_POST['title']))? $_POST['title'] : '权限管理器';
		$data['label'] = (isset($_POST['label']))? $_POST['label'] : '权限';

		$data['key'] = trim($_POST['key']);
		$data['elm'] = trim($_POST['elm']);

		// 非法请求 （ 无必要的参数表 ）
		if ( empty($data['elm']) || empty($data['key']) ) {
			throw new Excp('非法请求，缺少请求参数。', 500, ['data'=>$data, '_POST'=>$_POST] );
		}

		// 读取已选中权限
		if ( isset( $_POST['tagsvalue']) && !empty( $_POST['tagsvalue'])) {
			$data['acl']= $acl->getByTags( $_POST['tagsvalue'] );
		}

		render( $data, 'core/system/web/aclmanager', 'aclselector');
	}


	/**
	 * 保存权限
	 * @return [type] [description]
	 */
	function aclUpdate(){

		$value = (isset($_POST['value'])) ? $_POST['value'] : null;
		$key =  (isset($_POST['key'])) ? $_POST['key'] : null;

		if ( empty($key) ){
			echo json_encode(['code'=>403, 'message'=>"非法请求，错误的输入参数"]);
			return ;
		}

		if ( !is_array($value) ){
			echo json_encode(['code'=>403, 'message'=>"非法请求，错误的输入参数"]);
			return ;
		}


		$acl = new Acl();
		$resp = $acl->updateBy('key', ['value'=>$value, 'key'=>$key]);
		if ( $resp === false ) {
			echo json_encode(['code'=>500, 'message'=>"更新失败，数据保存失败"]);
			return ;
		}

		echo json_encode(['code'=>0, 'message'=>"success"]);
	}



	function updateorder() {
		sleep(1);
		echo json_encode('ok');
	}



	// /**
	//  * 本地应用列表
	//  * @return [type] [description]
	//  */
	// function local() {

		
	// 	$app = M('App');  // 不再扫描应用
		
	// 	// 扫描应用
	// 	// $resp = $app->scan();
	// 	// $create = $resp['create'];
	// 	// $app->update(158, ['status'=>'uninstalled']);

	// 	try {
	// 		$appsdata = $app->select('order by _create_at,_id');
	// 	} catch( Exception $e ){
	// 		$appsdata = ['data'=>[], 'total'=>0];	
	// 	}
		
	// 	$data = $this->_data(["apps"=>$appsdata['data']], '应用管理', '应用管理');
	// 	render( $data, 'core/system/web/appmanager', 'local');
	// }


	// /**
	//  * 应用商店列表
	//  * @return [type] [description]
	//  */
	// function store() {
	// 	echo '<p class="text-muted" style="font-size:16px;"> 
	// 				<i class="iconfont icon-xpmse push-20-l" style="font-size:24px;"></i> 
	// 				应用商店即将上线，海量应用准备中... 您在这里即可挑选、购买、安装企业应用。
	// 		  </p>';
	// }

}
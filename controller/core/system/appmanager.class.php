<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );



use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;

class CoreSystemAppmanagerController extends privateController
{
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-grid', 'icontype'=>'si', 'cname'=>'应用']);
	}

	
	/**
	 * 应用商店主页
	 */
	function index() {
		$data = $this->_data([],'应用管理','应用管理');
		$data['active'] = empty($_GET['active']) ? 'local' : $_GET['active'];
		
		render( $data, 'core/system/web', 'appmanager');
	}


	/**
	 * 升级应用模块
	 * @return [type] [description]
	 */
	function upgrade() {

		Utils::cliOnly();
		$app = M('App');
		$app->__schema();
		echo "OK";
	}

	/**
	 * 本地应用列表
	 * @return [type] [description]
	 */
	function local() {
		
		$app = M('App');  // 不再扫描应用
		
		// 扫描应用
		// $resp = $app->scan();
		// $create = $resp['create'];
		// $app->update(158, ['status'=>'uninstalled']);
		
		try {
			$appsdata = $app->select(' order by org,created_at,_id');

		} catch( Exception $e ){

			utils::out( $e->getMessage()  );
			$appsdata = ['data'=>[], 'total'=>0];	
		}

		$data = $this->_data(["apps"=>$appsdata['data']], '应用管理', '应用管理');
		render( $data, 'core/system/web/appmanager', 'local');
	}


	/**
	 * 已安装应用（ 下载UI客户端
	 */
	function uiDownload(){
		$app = M('App');
		$appid = $_POST['id'];
		$local_appid= $_POST['lid']; // Localid

		if ( empty( $appid ) ) {
			throw new Excp('错误的请求', 402, ['_POST'=>$_POST]);
		}
			
		$store = M('Appstore');
		$data['online'] = $store->isOnline(); 
		if ( $data['online'] == false ) {
			$this->storeError("无法连接应用商店");
			return;
		}

		$data['app'] = $store->getApp( $appid ); // 读取应用
		render( $data, 'core/system/web/appmanager', 'uidownload');
	}


	/**
	 * 下载UI客户端源码
	 * @return [type] [description]
	 */
	function uiZipAndDownload() {
		$app = M('App');
		$appid = $_GET['lid'];
		$local_appid = $_GET['lid'];
		if ( empty( $local_appid ) ) {
			throw new Excp('错误的请求', 402, ['_GET'=>$_GET]);
		}
		$app->uiZipAndDownload( $local_appid );
	}


	/**
	 * 应用商店 （ 应用列表
	 * @return
	 */
	function store() {
		
		$store = M('Appstore');
		$query = $_GET;

		// 检查Live状态
		$data['online'] = $store->isOnline(); 
		if ( $data['online'] ) {
			// 读取应用列表
			$data['apps'] = $store->search( $query );
		}

		render( $data, 'core/system/web/appmanager', 'store');
	}

	/**
	 * 应用商店，错误通报
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	private function storeError( $message ) {
		$data['message'] = $message;
		render( $data, 'core/system/web/appmanager', 'store_error');
	}



	/**
	 * 应用商店 （ 应用详情
	 * @return 
	 */
	function storeDetail() {

		$store = M('Appstore');
		$query = $_GET;

		if ( empty( $query['id']) ) {
			throw new Excp('错误的请求', 402, ['query'=>$query]);
		}
		
		$data['online'] = $store->isOnline(); 
		if ( $data['online'] == false ) {
			$this->storeError("无法连接应用商店");
			return;
		}
		
		$data['app'] = $store->getApp( $query['id'] ); // 读取应用
		render( $data, 'core/system/web/appmanager', 'store_detail');
	}


	/**
	 * 应用商店 （ 下载应用并呈现详情页
	 * @return 
	 */
	function storeDownload() {

		$store = M('Appstore');
		$query = $_GET;

		if ( empty( $query['id']) ) {
			throw new Excp('错误的请求', 402, ['query'=>$query]);
		}

		$data['online'] = $store->isOnline(); 
		if ( $data['online'] == false ) {
			$this->storeError("无法连接应用商店");
			return;
		}

		// 调用下载接口
		$task = $store->download( $query['id'] );
		$this->storeDetail();
	}


	/**
	 * 应用商店 ( 安装应用
	 * @return [type] [description]
	 */
	function storeInstall() {

		$store = M('Appstore');
		$query = $_GET;

		if ( empty( $query['id']) ) {
			throw new Excp('错误的请求', 402, ['query'=>$query]);
		}

		$data['online'] = $store->isOnline(); 
		if ( $data['online'] == false ) {
			$this->storeError("无法连接应用商店");
			return;
		}

		// 应用目录可写（ 运行安装程序
		if ( $store->isInstallAble( $query['id']) ) {
			
			$task = $store->install( $query['id'] );
			$this->storeDetail(); 	// 打开详情页
			return;

		}

		// 后台安装应用方法	
		$data['app'] = $store->getApp( $query['id'] ); // 读取应用
		render( $data, 'core/system/web/appmanager', 'store_install');
		
	}



	/**
	 * 应用商店 （ 查询应用状态
	 * @return 
	 */
	function storeStatus() {

		$store = M('Appstore');
		$query = $_GET;

		if ( empty( $query['id']) ) {
			throw new Excp('错误的请求', 402, ['query'=>$query]);
		}

		// 查询状态
		$store = M('Appstore');
		$data = $store->downloadStatus( $query["id"] );
		echo json_encode($data);
		// echo json_encode(['status'=>'downloading', 'progress'=>90, 'message'=>'正在下载应用代码']);

	}


	/**
	 * 应用商店 （ 刷新应用信息
	 * @return [type] [description]
	 */
	function storeRefresh() {
		$store = M('Appstore');
		$store->clearCache();
		$url = R('core-system','appmanager', 'index', ['active'=>'store']);
		header("Location: $url");
	}


	/**
	 * 应用商店 ( 购买表单
	 * @return
	 */
	function storeBuy() {

		$store = M('Appstore');
		$query = $_GET;

		$data['online'] = $store->isOnline(); 
		if ( $data['online'] ) {
			$data['app'] = $store->getApp( $query['id'] ); // 读取应用
		}

		render( $data, 'core/system/web/appmanager', 'store_buy');
	}




	/**
	 * 应用商店 ( 服务条款
	 * @return [type] [description]
	 */
	function storeTerms(){

		$store = M('Appstore');
		$query = $_GET;

		$data['online'] = $store->isOnline(); 
		if ( $data['online'] ) {
			$data['app'] = $store->getApp( $query['id'] ); // 读取应用
			$parser =  new \cebe\markdown\GithubMarkdown();
			$data['agreement'] = $parser->parse($data['app']['agreement']);
		}

		render( $data, 'core/system/web/appmanager', 'store_terms');
	}


	/**
	 * 应用商店 ( 验证 Paycode, 同时下载
	 * @return
	 */
	function storeCheckPaycodeAndDownload() {
		$store = M('Appstore');
		$query = $_POST;

		$data['online'] = $store->isOnline(); 
		if ( $data['online'] ) {
			$result = $store->checkPaycodeAndDownload( $query['id'], $query['paycode'] );
		}
		
		Utils::out( $result );
	}



}
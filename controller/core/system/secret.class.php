<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Que as Que;
use \Xpmse\Task as Task;

class CoreSystemSecretController extends privateController {
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'fa-code', 'icontype'=>'fa', 'cname'=>'开发工具']);
	}

	function make() {

		$secret = M('Secret');
		$keypair = $secret->genKeyPair();
		$resp = $secret->create($keypair);
		Utils::out(['message'=>'创建成功']);
	}


	function remove(){
		$id = $_POST['id'];
		if ( empty($id) ) {
			throw new Excp("未知ID",404, ['_POST'=>$_POST]);
		}

		$resp = M('Secret')->remove($id);
		if ( $resp !== true ) {
			throw new Excp("删除失败", 500, ['id'=>$id, 'resp'=>$resp]);	
		}

		Utils::out(['message'=>'删除成功']);
	}
	


	function index() {

		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$this->_crumb('系统选项', R('baas-admin','data','index') );
	    $this->_crumb('密钥管理');

	    $page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
	    $secret = M('Secret')->query()
				   ->orderBy('created_at')
				   ->select( '_id as id', 'appid', 'appsecret', 'token', 'expires_at', 'userid', 'created_at' )
				   ->pgArray(10, ['*'], '', $page);

	    $data = $this->_data([
	    	"secret" => $secret,
	    	"_page" => '/secret/search.index',
	    	"query" =>[
	    		"page"=>$page
	    	]
	    ],'系统选项','密钥管理');

		render( $data, 'core/system/web', 'main');
	}

}
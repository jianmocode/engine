<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;

class CoreSystemPagesController extends privateController
{
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-layers', 'icontype'=>'si', 'cname'=>'页面']);
	}

	
	/**
	 * 页面检索
	 */
	function index() {

        $inst = new \Xpmse\Model\Page;
        $response = $inst->search($_GET);
        $this->_crumb("页面列表");
        $data = $this->_data([],'页面管理');
        $data['active'] = empty($_GET['active']) ? 'local' : $_GET['active'];
        $data['response'] = $response;
        $data['query'] = $_GET;

        if ( $_GET["debug"] ) {
            echo "<!-- \n";
            Utils::out( $data );
            echo "-->\n";
            exit;
        }
		render( $data, 'core/system/pages', 'search');
    }


    /**
     * 页面详情
     */
    function detail() {

		$id = trim($_GET['id']);
        $inst = new \Xpmse\Model\Page;
        
		
		if ( empty($id) ) {
			throw new Excp("请提供页面ID", 402, ["id"=>$id]);
        }
        
        $rs = $inst->get($id);
        if ( empty($rs) ) {
			throw new Excp("页面不存在", 404, ["id"=>$id]);
        }

        $rs["methodStyle"] = [
            "GET" => "success",
            "POST" => "warning",
            "DELETE" => "danger",
        ];

        $rs["adaptStyle"] = [
            "desktop" => "primary",
            "mobile" => "warning",
            "wechat" => "success",
            "wxapp" => "danger",
        ];
        
        $action_name = $rs["cname"];
        $this->_active("core-system/pages/index");
        $this->_crumb("页面列表", R("core-system", "pages", "index"));
        $this->_crumb($rs["cname"]);
        $data = $this->_data([],implode("-",[$action_name, "页面管理"]));
        $data["id"] = $id;
        $data["rs"] = $rs;
        $data["action_name"] = $action_name;

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		render( $data, 'core/system/pages', 'form');
    }


    /**
     * 删除页面
     */
    function remove(){
        $id = trim($_POST['id']);
        $inst = new \Xpmse\Model\Page;

        if ( empty($id) ) {
			throw new Excp("请提供页面ID", 402, ["id"=>$id]);
        }

        $resp = $inst->remove($id);
        echo json_encode(['message'=>"删除成功", 'extra'=>['response'=>$resp]]);
    }

     /**
     * 清除页面缓存
     */
    function clearcache(){
        $id = trim($_POST['id']);
        $inst = new \Xpmse\Model\Page;

        if ( empty($id) ) {
			throw new Excp("请提供页面ID", 402, ["id"=>$id]);
        }

        $resp = $inst->clearCache($id);
        echo json_encode(['message'=>"清理成功", 'extra'=>['response'=>$resp]]);
    }
}
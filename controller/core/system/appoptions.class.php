<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;

class CoreSystemAppOptionsController extends privateController
{
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-settings', 'icontype'=>'si', 'cname'=>'设置']);
	}

	
	/**
	 * 配置选项
	 */
	function index() {

        $slug = $_GET["slug"];
        if ( empty($slug) ) {
            $slug = "xpmse/xpmse";
        }
        $inst = new \Xpmse\Model\Option;
        $apps = $inst->getApps($slug);
        $response = $inst->getAll( $slug );

        $this->_crumb("应用设置");
        $data = $this->_data([],'应用设置');
        $data['current'] = $slug;
        $data['apps'] = $apps;
        $data["options"] = $response["data"];
        $data['query'] = $_GET;


        if ( $_GET["debug"] ) {
            echo "<!-- \n";
            Utils::out( $data );
            echo "-->\n";
            exit;
        }

		render( $data, 'core/system/appoptions', 'index');
    }


    /**
     * 保存配置
     */
    function save(){
        $data = $_POST;
        Utils::JsonFromInput( $data );

        if ( empty($data["key"]) ) {
            throw new Excp("为找到配置键", 404, ["data"=>$data]);
        }

        if ( empty($data["app"]) ) {
            throw new Excp("为找到配置所属应用", 404, ["data"=>$data]);
        }

        $inst = new \Xpmse\Model\Option;
        $data["key"] = trim($data["key"]);
        $data["app"] = trim($data["app"]);
        $inst->set( $data["key"], $data["value"], $data["app"]);
        $resp = $inst->get($data["key"], $data["app"]);

        utils::out([
            "code" => 0, 
            "message" => "更新成功",
            "resp" =>$resp
        ]);
    }

}
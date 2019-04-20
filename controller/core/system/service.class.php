<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;

class CoreSystemServiceController extends privateController
{
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'fa-server', 'icontype'=>'fa', 'cname'=>'服务']);
	}

	
	/**
	 * 配置选项
	 */
	function index() {

        $slug = $_GET["slug"];
        $inst = new \Xpmse\Model\Service;
        $apps = $inst->getApps($slug);
        if ( empty($slug) ) {
            $app = current($apps);
            $slug = $app["slug"];
        }

        $services = $inst->getAll( $slug );

        $this->_crumb("服务设置");
        $data = $this->_data([],'服务设置');
        $data['current'] = $slug;
        $data['apps'] = $apps;
        $data["services"] = $services;
        $data['query'] = $_GET;

        if ( $_GET["debug"] ) {
            echo "<!-- \n";
            Utils::out( $data );
            echo "-->\n";
            exit;
        }

		render( $data, 'core/system/service', 'index');
    }


    /**
     * 启动服务
     */
    function start(){
        
        $data = $_POST;
        $service_id = $data["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["data"=>$data]);
        }
        $se = new \Xpmse\Model\Service;
        $response = $se->start( $service_id );

        utils::out([
            "code" => 0, 
            "message" => "启动成功",
            "setting" =>$response
        ]);
    }

    /**
     * 平滑重启服务
     */
    function reload(){
        
        $data = $_POST;
        $service_id = $data["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["data"=>$data]);
        }
        $se = new \Xpmse\Model\Service;
        $response = $se->reload( $service_id );

        utils::out([
            "code" => 0, 
            "message" => "重载成功(平滑重启)",
            "setting" =>$response
        ]);
    }

    /**
     * 重启服务
     */
    function restart(){
        
        $data = $_POST;
        $service_id = $data["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["data"=>$data]);
        }
        $se = new \Xpmse\Model\Service;
        $response = $se->restart( $service_id );

        utils::out([
            "code" => 0, 
            "message" => "重启成功",
            "setting" =>$response
        ]);
    }

    /**
     * 关闭服务
     */
    function shutdown(){
        $data = $_POST;
        $service_id = $data["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["data"=>$data]);
        }
        $se = new \Xpmse\Model\Service;
        $response = $se->shutdown( $service_id );

        utils::out([
            "code" => 0, 
            "message" => "关闭成功",
            "setting" =>$response
        ]);
    }

    /**
     * 读取服务详情
     */
    function inspect() {
        $query = $_GET;
        $service_id = $query["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["query"=>$query]);
        }
        $se = new \Xpmse\Model\Service;
        $data["se"]= $se->getByServiceId( $service_id );
        render( $data, 'core/system/service', 'inspect');
    }


    /**
     * 读取服务日志
     */
    function log() {

        $query = $_GET;
        $service_id = $query["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["query"=>$query]);
        }
        $max = empty($query["max"]) ? 500 : intval($query["max"]);
        $se = new \Xpmse\Model\Service;
        $data["se"]= $se->getByServiceId( $service_id );
        $data["log"]= $se->tailLog( $service_id, $max );
        render( $data, 'core/system/service', 'log');
    }


    /**
     * 保存配置
     */
    function save(){
        $data = $_POST;
        $service_id = $data["service_id"];
        if (empty($service_id)) {
            throw new Excp("未提供服务ID", 402,["data"=>$data]);
        }

        Utils::JsonFromInput( $data );
        $inst = new \Xpmse\Model\Service;
        $inst->updateBy("service_id", $data );

        utils::out([
            "code" => 0, 
            "message" => "更新成功",
            "resp" =>$resp
        ]);
    }

}
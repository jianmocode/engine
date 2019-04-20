<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class SetupController extends \Xpmse\Loader\Controller {
	
	
	function __construct() {

		$this->models = [
			'\\{{org}}\\{{name}}\\Model\\Pet', 
		];
	}

	/**
	 * 初始化默认数据
	 * @return [type] [description]
	 */
	private function defaults_init() {

		// 注册配置
		$option = new \Xpmse\Option('{{org}}/{{name}}');
		$option->register("配置项一", "someoption/sample", [
			"key" => "value",
			"key" => ["k"=>"v"]
		]);
	}


	/**
	 * 应用安装脚本（ 创建数据表、初始化配置 
	 * @return 
	 */
	function install() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			if ( !class_exists($mod) ) {
				echo json_encode(['code'=>404, "message"=>"未找到应用模块($mod)"]);
			}

			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
			try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		try {
			$this->defaults_init();
		}  catch ( Excp $e ) {
			echo $e->toJSON();
			return;
		}

		echo json_encode('ok');
	}


	/**
	 * 应用升级脚本
	 * @return [type] [description]
	 */
	function upgrade(){
		echo json_encode('ok');	
	}


	/**
	 * 应用修复脚本( 重建数据表
	 * @return 
	 */
	function repair() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		echo json_encode('ok');		
	}


	/**
	 * 卸载应用 ( 删除数据表， 删除配置项
	 * @return 
	 */
	function uninstall() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		try {
			$option = new \Xpmse\Option('{{org}}/{{name}}');
			$option->unregister();
		} catch ( Excp $e ) {}

		echo json_encode('ok');		
	}
}
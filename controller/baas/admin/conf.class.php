<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;
use \Xpmse\Stor;
use \Xpmse\Mem;

class baasAdminConfController extends privateController {
	
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'/static/defaults/images/icons/wechat.svg', 'icontype'=>'img', 'cname'=>'微信公众平台']);

		$this->table = 'sys_conf';
		$this->prefix = '_baas_';
		$this->tables = [];
	    $this->tab = M('table', $this->table, ['prefix'=>$this->prefix]);
		

		// 更新配置缓存信息
		try { $this->refresh(); } catch( Excp $e ) {}

	}

	function qrcode() {
		$page = $_Get['page'];
	}


	function upgrade( $init = false ) {
		if ( $init === false ) { 
			Utils::cliOnly();
		}
		$this->__upgradeSchema( true );
		echo "DONE";
	}


	function index() {

		$this->__upgradeSchema();

		$tab = $this->tab;
		$pg = isset($_GET['page']) ? $_GET['page'] : 1;
		$this->_crumb('微信配置', R('baas-admin','conf','index', ['table'=>$table]) );
	    $this->_crumb('配置项列表');

	    $rows = $tab->query()
	    			->orderBy('pri', 'asc')
	    			->paginate( 50, ["*"], '', $pg )
	    			->toArray();



	   	foreach ($rows['data'] as $row ) {
	   		$gname = $row['gname'];
	   		if (empty($gname) ) {
	   			continue;
	   		}

	   		if ( !is_array($groups[$gname] ) ) {
	   			$groups[$gname] = [];
	   		}

	   		if ( is_array($row['option']) && $row['option']['type'] == 'select' ) {
	   			$scope = $row['option']['scope'];
	   			$row['value'] = $scope[$row['value']];
	   		}

	   		array_push($groups[$gname], $row);
	   	}

	    $pages = array();		
		for ($i=1; $i<=$rows["last_page"] ; $i++) { 
			$pages[$i] = $i;
		}
	   	
		$data = [
			'tabs' =>$this->tables,
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'page'=>$pages,
			'rs' =>  $rows['data'],
			'groups' => $groups,
			'cur'=>$rows["current_page"],
			'pre'=>$rows["prev_page_url"],
			'next'=>$rows["next_page_url"],
			'total'=>$rows['total'],
			'table_columns' => $this->getColumns($tab, true),
			'columns' => $this->getColumns($tab),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
			'_page'=>'admin/conf/search.index'
		];

		$data = $this->_data( $data , '微信配置');
		render($data, 'baas', 'main');
	}



	function modify() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = $this->tab;
	    $row = $tab->getLine('WHERE _id=?',['*'], [$id] );


		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  $row,
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '微信配置');
		render($data, 'baas/admin/conf', 'panel.modify');

	}



	/**
	 * 修改
	 * @return [type] [description]
	 */
	function panel() {

		$table = $this->table;
		$id = $_GET['_id'];
		$type = $_GET['type'];

		$tab = $this->tab;
	    $rs = $tab->getLine('WHERE _id=?',['*'], [$id] );

	   
		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'rs'=>$rs,
			"id" => $id,
			'type'=> $type
		];
		$data = $this->_data( $data , '微信配置');
		render($data, 'baas/admin/conf', 'panel.index');
	}

	function read() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = $this->tab;
	    $rs = $tab->getLine('WHERE _id=?',['*'], [$id] );

		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  $rs,
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '微信配置');
		render($data, 'baas/admin/conf', 'panel.read');

	}



	function create() {

		$table = $this->table;
		$type = $_GET['type'];

	    $tab = $this->tab;

		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  [],
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '微信配置');
		render($data, 'baas/admin/conf', 'panel.modify');

	}



	function save() {

		$table = $this->table;
		$tab = $this->tab;

		$_id = $_POST['_id'];
		foreach ($_POST as $key => $value) {
			if ( strpos($value,'__JSON_TEXT__|') === 0 ) {
				$value = str_replace('__JSON_TEXT__|', '', $value);
				$_POST[$key] = json_decode($value, true);
			}
		}

		unset($_POST['name']);
		unset($_POST['group']);

		if (!empty($_id)) {
			try {
				$data = $tab->update($_id,$_POST);
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}else{
			try {
				$data = $tab->create($_POST);
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}

		// 清空缓存
		$mem = new Mem;
		$mem->delete("BaaS:CONF");
		

		echo json_encode(["code"=>0, "data"=>$data]);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	function remove() {

		$table = $this->table;
		$tab = $this->tab;
		$id = $_POST['id'];
		
		try {
			$data = $tab->delete( $id  );
		} catch (Excp $e) {
			echo $e->tojson();
			return;
		}

		echo json_encode(["code"=>0, "data"=>$data]);

	}



	private function getColumns( $tab, $full = false ) {
		$columns = $tab->getColumns();
	    $table_filter = ['_id','_acl','created_at', 'updated_at', 'deleted_at','_user', '_group'];
	    if ( $full === false ) {
		    foreach ($columns as $idx=>$value ) {
		    	if ( in_array($value, $table_filter) ) {
		    		unset( $columns[$idx] );
		    	}
		    }
	    }
	    return $columns;
	}

	private function getColumnsMap( $tab, $columns ) {
		$map = [];
		foreach ($columns as $col) {
			$map[$col] = $tab->getColumn( $col );
		}

		return $map;
	}



	private function __upgradeSchema( $force=false) {

		if ( !$this->tab->tableExists() || $force == true ) { // 初始化用户表
			$this->__schema();
		}

		$allow = ['name', 'cname' , 'value', 'group','gname','option', 'data', 'key'];
		$columns = $this->getColumns($this->tab);

		if ( !empty(array_diff($allow, $columns)) ){
			$this->__schema(true);
		}

	}


	private function __schema( $force=false ) {

		$tab = $this->tab;

		$schema =[
			["name"=>"name",  "type"=>"string", "option"=>["length"=>64, "unique"=>1], "acl"=>"-:-:-" ],
			["name"=>"key",   "type"=>"string",  "option"=>["length"=>64], "acl"=>"-:-:-" ],
			["name"=>"cname",  "type"=>"string", "option"=>["length"=>64], "acl"=>"-:-:-" ],
			["name"=>"value",  "type"=>"string", "option"=>["length"=>256, 'default'=>''], "acl"=>"-:-:-"  ],
			["name"=>"data",   "type"=>"text",   "option"=>["json"=>true], "acl"=>"-:-:-" ],
			["name"=>"option",  "type"=>"text",  "option"=>["json"=>true], "acl"=>"-:-:-" ],
			["name"=>"group",  "type"=>"string", "option"=>["length"=>256], "acl"=>"-:-:-"  ],
			["name"=>"gname",  "type"=>"string", "option"=>["length"=>256], "acl"=>"-:-:-"  ],  // 分组中文名
			["name"=>"pri",    "type"=>"integer", "option"=>["default"=>99, "index"=>1], "acl"=>"-:-:-"  ],  // 显示顺序
			["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
			["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
			["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
		];

		$tab->__schema( $schema );

		try {

			$cnum = ['一', '二', '三', '四', '五' ];
			$enum = ['','_2','_3','_4','_5'];

			for( $i=0; $i<5; $i++ ) {

				$tab->createOrUpdate([
					'cname'=>"配置别名",
					'name'=> "wxapp.name{$enum[$i]}", 
					'key' => 'name',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'value' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 1
				]);

				$tab->createOrUpdate([
					'cname'=>"微信公众号 appid",
					'name'=> "wxapp.appid{$enum[$i]}", 
					'key' => 'appid',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 2
				]);

				$tab->createOrUpdate([
					'cname'=>"微信公众号 secret",
					'name'=> "wxapp.secret{$enum[$i]}", 
					'key' => 'secret',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=> ["type"=>"input"],
					"pri" => 3
				]);

				$tab->createOrUpdate([
					'cname'=>"公众号类型",
					'name'=> "wxapp.type{$enum[$i]}",
					'key' => 'type',
					'gname' => "公众号{$cnum[$i]}",
					'group' => "wxapp{$enum[$i]}",
					'option'=>[
						"type"=>"select", "scope"=>[
							"1" => "服务号",
							"2" => "订阅号",
							"3" => "小程序",
							"4" => "企业微信"
					 	]
					],
					"pri" => 4
				]);

				$tab->createOrUpdate([
					'cname'=>"微信支付商户号 mch_id",
					'name'=> "wxpay.mch_id{$enum[$i]}", 
					'key' => 'mch_id',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 5
				]);

				$tab->createOrUpdate([
					'cname'=>"微信支付API密钥 key",
					'name'=> "wxpay.key{$enum[$i]}", 
					'key' => 'key',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 6
				]);


				$tab->createOrUpdate([
					'cname'=>"Token",
					'name'=> "wxapp.token{$enum[$i]}", 
					'key' => 'token',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 7
				]);

				$tab->createOrUpdate([
					'cname'=>"EncodingAESKey",
					'name'=> "wxapp.aes{$enum[$i]}", 
					'key' => 'aes',
					'group' => "wxapp{$enum[$i]}",
					'gname' => "公众号{$cnum[$i]}",
					'option'=>["type"=>"input"],
					"pri" => 8
				]);


				$tab->createOrUpdate([
					'cname'=>"消息加解密方式",
					'name'=> "wxapp.encrypt_type{$enum[$i]}",
					'key' => 'encrypt_type',
					'value' => "1",
					'gname' => "公众号{$cnum[$i]}",
					'group' => "wxapp{$enum[$i]}",
					'option'=>[
						"type"=>"select", "scope"=>[
							"1" => "明文模式",
							"2" => "兼容模式",
							"3" => "安全模式（推荐）"
					 	]
					 ],
					"pri" => 9
				]);

			}
		
		} catch( Excp $e ) {}
	}


	private function refresh() {

		$mem = new Mem;
		$mem->delete("BaaS:CONF");

		// $cmap = 
		// $cmap = false;
		
		// if ( $cmap == false  || $cmap == null) {

		// 	$tab = M('table', 'sys_conf', ['prefix'=>'_baas_']);
		// 	$cmap = [];
		// 	$config = $tab->select("", ["name","value"] );


		// 	foreach ($config['data'] as $row ) {
		// 		$cmap[$row['name']] = $row['value'];
		// 	}

		// 	$tab = $this->tab;
		// 	$config = $tab->select("", ["name","path"] );

		// 	foreach ($config['data'] as $row ) {
		// 		$cmap[$row['name']] = $row['path'];
		// 	}

		// 	$mem->setJSON("BaaS:CONF", $cmap );

		// }

		// return $cmap;

	}


}
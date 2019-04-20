<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;
use \Xpmse\Stor;

class baasAdminCertController extends privateController {
	
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-settings', 'icontype'=>'si', 'cname'=>'系统选项']);

		$this->table = 'sys_cert';
		$this->prefix = '_baas_';
		$this->tables = [];

	}

	function upgrade( $init = false) {
		if ( $init === false ) { 
			Utils::cliOnly();
		}
		$this->__upgradeSchema( true );
		echo "DONE";
	}

	private function __upgradeSchema(){
		$this->index();
	}


	function attachment() {


		$name = $_GET['name'];
		$path = "/tmp";
		$file_name = $path . '/' . $name;
		$action = trim($_POST['action']);

		if ( $action == 'upload' ) {  // 上传附件

			if ( $_FILES['file']['error']  != 0 ||  $_FILES['file']['tmp_name'] == "" ) {
				echo json_encode(['errno'=>'100500', 'errmsg'=>'文件上传失败', 'extra'=>['_FILES'=>$_FILES, '_POST'=>$_POST]]);
				return;
			}

			$content = file_get_contents($_FILES['file']['tmp_name']);
			file_put_contents($file_name, $content );
			$suffix = end( explode('.', $name) );


			echo json_encode( [
				'url'=>$file_name, 
				'path'=>"$file_name", 
				'type'=>$suffix, 
				'placeholder'=>$name 
			]);
			return;


		} else if ($action == 'delete') { // 删除文件

			echo json_encode(['ret'=>'complete', 'msg'=>'删除成功']);
			return;
		}
		
		// 无效请求
		echo json_encode(['errno'=>'100100', 'errmsg'=>'未知请求']);

	}



	function index() {

		$pg = isset($_GET['page']) ? $_GET['page'] : 1;
		$table = $this->table;

		$this->_crumb('证书管理', R('baas-admin','data','index', ['table'=>$table]) );
	    $this->_crumb('证书列表');

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
	    $tab = M('table', $this->table, ['prefix'=>$this->prefix]);
		if ( !$tab->tableExists() ) { // 初始化用户表
			
			$schema =[
				["name"=>"name",  "type"=>"string", "option"=>["length"=>64, "unique"=>1], "acl"=>"-:-:-" ],
				["name"=>"cname",  "type"=>"string", "option"=>["length"=>64], "acl"=>"-:-:-" ],

				["name"=>"path",  "type"=>"string", "option"=>["length"=>256], "acl"=>"-:-:-"  ],
				["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
			];

			$resp = $tab->__schema( $schema );

			try {
			
				$tab->create([
					'cname'=>'HTTPS 证书',
					'name'=> 'https.cert', 
					'path' => ''
				]);

				$tab->create([
					'cname'=>'HTTPS 证书密钥',
					'name'=> 'https.cert.key', 
					'path' => ''
				]);

				$tab->create([
					'cname'=>'微信支付证书',
					'name'=> 'pay.cert', 
					'path' => ''
				]);

				$tab->create([
					'cname'=>'微信支付证书密钥',
					'name'=> 'pay.cert.key', 
					'path' => ''
				]);

				$tab->create([
					'cname'=>'微信支付证书 CA',
					'name'=> 'pay.rootca', 
					'path' => ''
				]);

			
			} catch( Excp $e ) {}

		}


	    $rs = $tab->query()
	    			->paginate(
	    				20, ["*"], '', $pg )
	    			->toArray();

	    $pages = array();		
		for ($i=1; $i<=$rs["last_page"] ; $i++) { 
			$pages[$i] = $i;
		}
	   	
		$data = [
			'tabs' =>$this->tables,
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'page'=>$pages,
			'rs' =>  $rs['data'],
			'cur'=>$rs["current_page"],
			'pre'=>$rs["prev_page_url"],
			'next'=>$rs["next_page_url"],
			'total'=>$rs['total'],
			'table_columns' => $this->getColumns($tab, true),
			'columns' => $this->getColumns($tab),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
			'_page'=>'admin/cert/search.index'
		];

		$data = $this->_data( $data , '证书管理');
		render($data, 'baas', 'main');
	}



	/**
	 * 修改
	 * @return [type] [description]
	 */
	function panel() {

		$table = $this->table;
		$id = $_GET['_id'];
		$type = $_GET['type'];

		$tab = M('table', $table, ['prefix'=>$this->prefix]);
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
		$data = $this->_data( $data , '用户管理');
		render($data, 'baas/admin/cert', 'panel.index');
	}

	function read() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
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

		$data = $this->_data( $data , '证书管理');
		render($data, 'baas/admin/cert', 'panel.read');

	}


	function modify() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
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
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '用户管理');
		render($data, 'baas/admin/cert', 'panel.modify');

	}


	function create() {

		$table = $this->table;
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);

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

		$data = $this->_data( $data , '证书管理');
		render($data, 'baas/admin/cert', 'panel.modify');

	}



	function save() {

		$table = $this->table;
		$m =  M('table', $table, ['prefix'=>$this->prefix]);
		$_id = $_POST['_id'];
		foreach ($_POST as $key => $value) {
			if ( strpos($value,'__JSON_TEXT__|') === 0 ) {
				$value = str_replace('__JSON_TEXT__|', '', $value);
				$_POST[$key] = json_decode($value, true);
			}
		}

		unset($_POST['name']);

		if ( isset($_POST['upload_cert_path']) ) {

			if ( file_exists($_POST['upload_cert_path']) ) {
				$_POST['path'] = str_replace("/tmp", Utils::certpath(),  $_POST['upload_cert_path']);
				$content = file_get_contents( $_POST['upload_cert_path']);
				file_put_contents($_POST['path'], $content );
			}

		} else {
			unset( $_POST['path'] );
		}

		if (!empty($_id)) {
			try {
				$data = $m->update($_id,$_POST);
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}else{
			try {
				$data = $m->create($_POST);
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}

		echo json_encode(["code"=>0, "data"=>$data]);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	function remove() {

		$table = $this->table;
		$m =  M('table', $table, ['prefix'=>$this->prefix]);
		$id = $_POST['id'];
		
		try {
			$data = $m->delete( $id  );
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


}
<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;

class baasAdminDataController extends privateController {
	
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-settings', 'icontype'=>'si', 'cname'=>'系统选项']);

		$this->table = isset($_GET['table']) ? $_GET['table'] : 'core_media';
		$this->prefix = !isset($_GET['prefix']) ? '_baas_' : '_baas_' . $_GET['prefix'] . "_";
		$this->tables = [];
		$this->prefix = '{nope}';

		$all_tables = M('User')->runSql("show tables", true );
		foreach ($all_tables as $key => $tab) {
			$name = end($tab);
			if ( strpos($name, 'core_') === 0 &&  strpos($name, 'core_media') === false) {
				continue;
			}
			if ( $name == '_baas_sys_cert' ) continue;
			if ( $name == '_baas_sys_conf' ) continue;

			array_push($this->tables, $name);
			// if ( strpos($name,$this->prefix) !== false ) {
				
			// 	$name = str_replace($this->prefix, '', $name);
			// 	if ( $name == 'sys_cert' ) continue;
			// 	if ( $name == 'sys_conf' ) continue;

			// 	array_push($this->tables, $name);
			// }
		}


		if  ( empty($this->table) ) {
			$this->table = end($this->tables);
		}

		if ( !in_array($this->table, $this->tables) ) {
			$this->table = end($this->tables);	
		}

	}

	function index() {

		$pg = isset($_GET['page']) ? $_GET['page'] : 1;
		$table = $this->table;

		$this->_crumb('数据管理', R('baas-admin','data','index', ['table'=>$table]) );
	    $this->_crumb($table);

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);

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
			'_page'=>'admin/data/search.index'
		];


		$data = $this->_data( $data , '数据管理');
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
		$data = $this->_data( $data , '数据管理');
		render($data, 'baas/admin/data', 'panel.index');
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

		$data = $this->_data( $data , '数据管理');
		render($data, 'baas/admin/data', 'panel.read');

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

		$data = $this->_data( $data , '数据管理');
		render($data, 'baas/admin/data', 'panel.modify');

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

		$data = $this->_data( $data , '数据管理');
		render($data, 'baas/admin/data', 'panel.modify');

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
<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;

class baasTableController extends baasBaseController {
	
	private $table = null; 
	private $prefix = '_baas_';

	function __construct() {

		parent::__construct();
		$this->table = $this->data['_table'];
		$this->prefix = empty($this->data['_prefix']) ? '' : '_baas_' . $this->data['_prefix'] . '_';
	}

	function index() {

		echo json_encode([
				"server" => "Xpm Server V2",
				"status" => "ok"
			]);
	}
	

	/**
	 * 删除数据表结构
	 * @return [type] [description]
	 */
	function clear() {

		$currUser  = $this->currUser();
		if ( !$currUser['_isadmin'] ) {
			unset($currUser['_user']);
			throw new Excp("本函数需要管理员权限", 403, ["loginUser"=>$currUser] );
		}


		$tb = $this->tab();
		$tb->__clear();
		echo json_encode(["code"=>0, 'result'=>'ok']);
	}


	/**
	 * 创建/修改数据表结构
	 * @return [type] [description]
	 */
	function schema() {

		$currUser  = $this->currUser();
		if ( !$currUser['_isadmin'] ) {
			unset($currUser['_user']);
			throw new Excp("本函数需要管理员权限", 403, ["loginUser"=>$currUser] );
		}


		$tb = $this->tab();
		
		// alter table <table_name> add deleted_at timestamp  NULL,add created_at timestamp  NULL, add updated_at timestamp  NULL, add _id bigint(20), add _acl text COMMENT '{__JSON__}', add _user varchar(128),add _group varchar(128);		
		$schema = array_merge([
			["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
			["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
			["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
		],$this->data['schema']);

		if ( $this->data['dropIfExist']  === true ) {
			try { 
				$tb->__clear();
			} catch( Excp $e ) { 
			} catch( Exception $e ) { }
		}

		$this->data['acl'] = ( !empty( $this->data['acl'] ) ) ? $this->data['acl'] : [
			"fields" =>[ "{default}"=>"rwd:r:-" ],
			"record"=>"rwd:rw:-",
			"table" =>"rwd:-:-",
			"user" => 'admin',
			"group" => 'member'
		];

		$this->data['acl']['field'] = empty($this->data['acl']['field']) ? "rwd:r:-" : $this->data['acl']['field'];
		$this->data['acl']['fields'] = is_array($this->data['acl']['fields']) ?  $this->data['acl']['fields'] : [] ;
		$this->data['acl']['fields']["{default}"] = empty($this->data['acl']['fields']["{default}"]) ? $this->data['acl']['field'] : "rwd:r:-";
		$this->data['acl']['record'] = !empty($this->data['acl']['record']) ?  $this->data['acl']['record'] : "rwd:rw:-" ;
		$this->data['acl']['table'] = !empty($this->data['acl']['table']) ?  $this->data['acl']['table'] : "rwd:-:-" ;
		$this->data['acl']['user'] = !empty($this->data['acl']['user']) ?  $this->data['acl']['user'] : "admin" ;
		$this->data['acl']['group'] = !empty($this->data['acl']['group']) ?  $this->data['acl']['group'] : "login" ;

		// 保存数据权限信息
		foreach ($schema as $sc ) {
			if ( isset( $sc['acl']) ) {
				$field = $sc['name'];
				$this->data['acl']['fields'][$field] = $sc['acl'];
			}
		}

		$resp = M('Tabacl')->save( $this->table_name(), $this->data['acl']);
		$resp = $tb->__schema( $schema );

		echo json_encode(["code"=>0, 'result'=>'ok']);
	}


	/**
	 * 创建一条数据
	 * @return [type] [description]
	 */
	function create() {
		
		$acl = M('Tabacl');
		$tb = $this->tab();
		$data = $this->data['data'];
		$currUser = $this->currUser();

		if ( !$acl->haveCreateRight( $this->table_name(), $currUser) ) {
			throw new Excp("没有创建权限", 403, ["data"=>$data] );
		}

		$data = array_merge( $data, $currUser );

		$acl->writeFilter( $data, $this->table_name(), $currUser, $currUser );

		$resp = $tb->create( $data );
		$acl->readFilter( $resp, $this->table_name(), $currUser, $currUser );

		echo json_encode([
			"code"=>0,
			"result"=>$resp
		]);

	}


	/**
	 * 更新一条数据
	 * @return [type] [description]
	 */
	function update() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$data = $this->data['data'];
		$id = $this->data['_id'];

		
		$owner = $tb->getLine("WHERE _id=? LIMIT 1",  ["_user","_group", "_acl"], [$id]);
		$acl->writeFilter( $data, $this->table_name(), $owner, $currUser );
		$resp = $tb->update( $id, $data );
		$acl->readFilter( $resp, $this->table_name(), $owner, $currUser );

		echo json_encode([
			"code"=>0,
			"result"=>$resp
		]);
	}




	/**
	 * 更新一条数据
	 * @return [type] [description]
	 */
	function updateby() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();

		$tb = $this->tab();
		$data = $this->data['data'];
		$uni_key = $this->data['uni_key'];

		$owner = $tb->getLine("WHERE $uni_key=? LIMIT 1",  ["_user","_group", "_acl"], [$data[$uni_key]]);
		$acl->writeFilter( $data, $this->table_name(), $owner, $currUser );
		$resp = $tb->updateBy( $uni_key, $data );
		$acl->readFilter( $resp, $this->table_name(), $owner, $currUser );
		
		echo json_encode([
			"code"=>0,
			"result"=>$resp
		]);
	}


	/**
	 * 删除数据
	 * @return [type] [description]
	 */
	function remove() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$data_key = $this->data['data_key'];
		$uni_key = $this->data['uni_key'];

		$owner = $tb->getLine("WHERE $uni_key=? LIMIT 1",  ["_user","_group", "_acl"], [$data_key]);
		if ( !$acl->haveRemoveRight( $this->table_name(), $owner, $currUser ) ) {
			throw new Excp("没有删除权限", 403, ["data_key"=>$data_key, 'uni_key'=>$uni_key] );
		}
		$resp = $tb->remove( $data_key, $uni_key, true );

		if ( $resp === false) {
			throw new Excp("删除失败", 500, ["data_key"=>$data_key, 'uni_key'=>$uni_key] );
		}
		
		echo json_encode(["code"=>0,"result"=>$resp]);
	}


	/**
	 * 查询数据
	 */
	function select() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$query = isset($this->data['query']) ?  $this->data['query'] : "";
		$fields =  isset($this->data['fields']) ?  $this->data['fields'] : [];
		$data =  isset($this->data['data']) ?  $this->data['data'] : [];
		$resp = $tb->select( $query, $fields, $data );

		if ( count($fields) > 0 ) {
			if ( !in_array('_user', $fields) ) {
				array_push($fields, '_user');
			}

			if ( !in_array('_group', $fields) ) {
				array_push($fields, '_group');
			}

			if ( !in_array('_acl', $fields) ) {
				array_push($fields, '_acl');
			}
		}

		if ( is_array($resp['data']) ) {
			foreach ($resp['data'] as $idx => $row ) {
				$acl->readFilter( $resp['data'][$idx], $this->table_name(), $resp['data'][$idx], $currUser );
			}
		}

		echo json_encode(["code"=>0,"result"=>$resp]);
	}


	/**
	 * 读取一组
	 */
	function getdata() {
		
		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$query = isset($this->data['query']) ?  $this->data['query'] : "";
		$fields =  isset($this->data['fields']) ?  $this->data['fields'] : [];
		$data =  isset($this->data['data']) ?  $this->data['data'] : [];
		$resp = $tb->getData( $query, $fields, $data );

		if ( count($fields) > 0 ) {
			if ( !in_array('_user', $fields) ) {
				array_push($fields, '_user');
			}

			if ( !in_array('_group', $fields) ) {
				array_push($fields, '_group');
			}

			if ( !in_array('_acl', $fields) ) {
				array_push($fields, '_acl');
			}
		}


		if ( is_array($resp) ) {

			foreach ($resp as $idx => $row ) {
				$acl->readFilter($resp[$idx], $this->table_name(), $resp[$idx], $currUser );
			}
		}

		echo json_encode(["code"=>0,"result"=>$resp]);
	}



	/**
	 * 读取一行
	 */
	function getline() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$query = isset($this->data['query']) ?  $this->data['query'] : "";
		$fields =  isset($this->data['fields']) ?  $this->data['fields'] : [];
		$data =  isset($this->data['data']) ?  $this->data['data'] : [];
		$resp = $tb->getLine( $query, $fields, $data );

		if ( count($fields) > 0 ) {
			if ( !in_array('_user', $fields) ) {
				array_push($fields, '_user');
			}

			if ( !in_array('_group', $fields) ) {
				array_push($fields, '_group');
			}

			if ( !in_array('_acl', $fields) ) {
				array_push($fields, '_acl');
			}
		}



		if ( is_array($resp) ) {
			$acl->readFilter($resp, $this->table_name(), $resp, $currUser );
		}

		echo json_encode(["code"=>0,"result"=>$resp]);

	}


	/**
	 * 读取一行
	 */
	function getvar() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$query = isset($this->data['query']) ?  $this->data['query'] : "";
		$field =  isset($this->data['field']) ?  $this->data['field'] : "_id";
		$data =  isset($this->data['data']) ?  $this->data['data'] : [];
		$resp = $tb->getLine( $query, ['_user','_group', $field], $data );

		if (!empty($resp)) {
			$acl->readFilter($resp, $this->table_name(), $resp, $currUser );
		}

		echo json_encode(["code"=>0,"result"=>$resp[$field] ]);

	}


	function runsql() {

		$currUser  = $this->currUser();
		if ( !$currUser['_isadmin'] ) {
			unset($currUser['_user']);
			throw new Excp("本函数需要管理员权限", 403, ["loginUser"=>$currUser] );
		}

		$tb = $this->tab();
		$sql = isset($this->data['sql']) ?  $this->data['sql'] : "";
		$data =  isset($this->data['data']) ?  $this->data['data'] : [];
		$return = isset($this->data['return']) ?  $this->data['return'] :false;
		$resp = $tb->runsql( $sql,  $return, $data );

		if ( $return === true ) {

			if ( is_array($resp) ) {
				unset($resp['_user']);
				unset($resp['_group']);
				unset($resp['_acl']);

				foreach ($resp as $idx => $row ) {
					unset($resp[$idx]['_user']);
					unset($resp[$idx]['_group']);
					unset($resp[$idx]['_acl']);
				}
			}

			if ( is_array($resp['data']) ) {
				foreach ($resp['data'] as $idx => $row ) {
					unset($resp['data'][$idx]['_user']);
					unset($resp['data'][$idx]['_group']);
					unset($resp['data'][$idx]['_acl']);
				}
			}

			echo json_encode(["code"=>0,"result"=>$resp]);
		} else {

			echo json_encode(["code"=>0,"result"=>'ok']);
		}

	}


	function nextid() {
		$tb = $this->tab();
		$resp = $tb->nextid();
		echo json_encode(["code"=>0,"result"=>$resp]);
	}



	function get() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();
		$tb = $this->tab();
		$id = $this->data['_id'];

		$resp = $tb->get( $id );

		if (!empty($resp)) {
			$acl->readFilter($resp, $this->table_name(), $resp, $currUser );
		}

		echo json_encode([
			"code"=>0,
			"result"=>$resp
		]);
	}



	function query() {

		$acl = M('Tabacl');
		$currUser = $this->currUser();

		$tb = $this->tab();
		$q = $this->data['query'];
		$fields = $this->data['fields'];

		$q['join'] = (!is_array($q['join'])) ? [] : $q['join'];
		$q['leftjoin'] = (!is_array($q['leftjoin'])) ? [] : $q['leftjoin'];
		$q['rightjoin'] = (!is_array($q['rightjoin'])) ? [] : $q['rightjoin'];
		$q['where'] = (!is_array($q['where'])) ? [] : $q['where'];
		$q['order'] = (!is_array($q['order'])) ? [] : $q['order'];
		$q['group'] = (!is_array($q['group'])) ? [] : $q['group'];
		$q['having'] = (!is_array($q['having'])) ? [] : $q['having'];
		$q['limit'] = (!is_array($q['limit'])) ? [] : $q['limit'];
		$q['paginate'] = (!is_array($q['paginate'])) ? [] : $q['paginate'];
		$q['inwhere'] = (!is_array($q['inwhere'])) ? [] : $q['inwhere'];

		$qb = $tb->query();


		if ( count($fields) > 0 ) {
			if ( !in_array('_user', $fields) ) {
				array_push($fields, $this->table . '._user');
			}

			if ( !in_array('_group', $fields) ) {
				array_push($fields, $this->table .'._group');
			}

			if ( !in_array('_acl', $fields) ) {
				array_push($fields, $this->table .'._acl');
			}
		}

		// Join Builder
		foreach ($q['join'] as $join ) {
			$qb = $qb->join( $join['table'], $join['field'], $join['exp'], $join['value']);
		}

		// Left Join Builder
		foreach ($q['leftjoin'] as $join ) {
			$qb = $qb->leftjoin( $join['table'], $join['field'], $join['exp'], $join['value']);
		}

		// Left Join Builder
		foreach ($q['rightjoin'] as $join ) {
			$qb = $qb->rightjoin( $join['table'], $join['field'], $join['exp'], $join['value']);
		}



		// Where Builder
		foreach ($q['where'] as $w ) {
			if ( $w['op'] == 'or' ) {
				if ( strtolower($w['exp']) == 'in')  {
					$qb = $qb->orwhereIn( $w['field'], $w['value']);
				} else {
					$qb = $qb->orwhere( $w['field'], $w['exp'], $w['value']);
				}
			} else  {
				if ( strtolower($w['exp']) == 'in')  {
					$qb = $qb->whereIn( $w['field'], $w['value']);
				} else {
					$qb = $qb->where( $w['field'], $w['exp'], $w['value']);
				}
			} 
		}


		// Group by 
		if ( !empty($q['group']) ) {
			$qb = $qb->groupBy( $q['group']['field']);

			foreach ($q['having'] as $having ) {
				$qb = $qb->having($having['field'], $having['exp'], $having['value']);
			}
		}


		// Order Builder
		foreach ($q['order'] as $o ) {
			$qb = $qb->orderBy( $o['field'], $o['order']);
		}

		// LIMIT 
		if ( !empty( $q['limit']) ) {
			if ( $q['limit']['from'] != null ) {
				$qb = $qb->offset($q['limit']['from'] );
			}

			$qb = $qb->limit($q['limit']['limit'] );
		}


		// paginate
		$pgresp = [];
		if ( !empty($q['paginate']) ) {

			$pgresp = $qb->select( $fields )
					->paginate( $q['paginate']['perpage'],$q['paginate']['fields'], $q['paginate']['link'], $q['paginate']['page'] )
					->toArray();

			if ( is_array($pgresp['data']) ) {
				foreach ($pgresp['data'] as $idx => $row ) {
					$acl->readFilter($pgresp['data'][$idx], $this->table_name(), $pgresp['data'][$idx], $currUser );
				}
			}

			$resp = $pgresp['data'];

			// echo json_encode(["code"=>0,"result"=>$resp]);
			// return;

		} else {
			$qb = $qb->select( $fields );
			$resp = $qb->get()->toArray();
		}

		if ( is_array($resp) ) {

			$inWhereIds = [];
			$inWhereData = [];


			foreach ($resp as $idx => $row ) {
				$acl->readFilter($resp[$idx], $this->table_name(), $resp[$idx], $currUser );


				// 处理 in Where
				foreach ($q['inwhere'] as $field => $w ) {
					if ( !is_array($row[$field]) ) continue;

					$inWhereIds[$field] = !empty($inWhereIds[$field]) ? $inWhereIds[$field] : [];

					if ( is_array(current($row[$field])) ) {
						$ids = array_keys($row[$field]);
					} else {
						$ids = $row[$field];
					}



					$inWhereIds[$field] = array_merge( $inWhereIds[$field] , $ids);
				}

			}

			// // 处理 in Where 
			foreach ($q['inwhere'] as $field => $w ) {
				if ( !is_array($inWhereIds[$field]) ) continue;

				$inWhereIds[$field] = array_merge(array_unique($inWhereIds[$field]), []);



				$tab = M( 'Table', $w['table'], ['prefix'=>$this->prefix]);
				$infield = $w['where'];
				$fields = $w['fields'];

				$data = $tab->query()
							->whereIn( "_id",  $inWhereIds[$field])
							->select( $fields )
							->get()->toArray();

				// IN Where 赋值
				foreach ($data as $dt ) {
					$acl->readFilter($dt, $this->prefix.$w['table'] , $dt, $currUser );

					$key = $dt[$infield];
					$inWhereData[$field][$key] = $dt;
				}

				// 赋值
				foreach ($resp as $idx => $row ) {

					if ( is_array(current($row[$field])) ) {
						foreach ($row[$field] as $key =>$val) {
							$resp[$idx][$field][$key] = array_merge($inWhereData[$field][$key], $val);
						}
					} else {
						$resp[$idx][$field] = $inWhereData[$field];
					}
				}
			}

		}

		if ( empty($pgresp) ) {
			Utils::out(["code"=>0,"result"=>$resp]);
		} else {
			$pgresp['data'] = $resp;
			Utils::out(["code"=>0,"result"=>$pgresp]);
		}
		
	}
	

	

	private function table_name() {
		$prefix = ($this->option['prefix'] == '{nope}') ? '' : $this->option['prefix'];
		return $prefix . $this->table;
	}


	private function tab() {
		return M( 'Table', $this->table, ['prefix'=>$this->prefix]);
	}

}
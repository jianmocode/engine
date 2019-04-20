<?php
namespace Xpmse\Model;

/**
 * 
 * 权限模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Tabacl
 *
 * USEAGE: 
 *
 */


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;

class Tabacl extends Model {


	function __construct( $param=[] ) {

		$param = (empty($param)) ? [] : $param;
		$param = array_merge(['prefix'=>'_baas_sys_'], $param );
		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct( $param , $driver );
		$this->table('acl');

		if ( !$this->tableExists() ) {
			$this->__schema();
		}
	}

	/**
	 * 删除表
	 * @return [type] [description]
	 */
	function __clear(){
		$this->dropTable();
	}

	function __schema() {

		// 数据结构
		try {

			$this->putColumn( 'name', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

				->putColumn( 'fields', $this->type('text', ['json'=>true] ) ) // Very Hign

				->putColumn( 'record', $this->type('string', ['index'=>1, "null"=>false, 'length'=>12] ) ) // medium

				->putColumn( 'table', $this->type('string', ['index'=>1,  "null"=>false, 'length'=>12] ) ) // Low

				->putColumn( 'user', $this->type('string', ['index'=>1, "null"=>false,'length'=>128] ) )

				->putColumn( 'group', $this->type('string', [ 'index'=>1, "null"=>false, 'length'=>128] ) )
			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}



	/**
	 * 保存数据
	 * @param  [type] $name [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function save( $name,  $data ) {

		$_id = $this->getVar("_id", "WHERE name=? LIMIT 1", [$name] );
		$row = $this->getLine("WHERE name=? LIMIT 1", [], [$name] );
	

		if ( $_id == null ) {
			$data['name'] = $name;
			return $this->create( $data );
		} else {
			return $this->update( $_id, $data );
		}
	}

	/**
	 * 读取权限
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	function read( $name, $nocache=false ) {

		$mem = new Mem;
		$cache = "tabacl:$name";
		$resp = $mem->getJSON( $cache );

		if ( $resp == false || $resp == null || $nocache == true ) {
			$resp = $this->getLine( "WHERE name=? LIMIT 1", ['user','group', 'record', 'table', 'fields'],  [$name]);
			$mem->set( $cache, $resp );
			return $resp;
		}

		return $resp;
	}



	/**
	 * 根据字段信息，过滤数据
	 * @param  [type] $row [description]
	 * @return [type]      [description]
	 */
	function readFilter( & $row, $name, $owner, $user, $allowed=['_acl', '_user', '_group', '_id', 'created_at','updated_at'] ) {


		if ( empty($name )) {
			throw new Excp('未输入过滤条件', 403, $name );
		}

		// if ( empty( $owner['_user']) || empty( $owner['_group']) ) {
		// 	return true;
		// }

		if ( !$user['_isadmin'] ) {

			$row['_acl'] = empty($owner['_acl']) ? [] : $owner['_acl'];
			$acl = $this->read( $name );
			$rsacl = array_merge( $acl['fields'], $row['_acl']);


			// 校验字段权限
			foreach ($row as $field=>$value ) {
				if ( in_array($field, $allowed) ) {
					continue;
				}
				$av = !empty($rsacl[$field])  ? $rsacl[$field] : $rsacl['{default}'];
				if ( !$this->checkAccess('r', $av, $owner, $user ) ) { // 没有阅读权限
					if ( is_string($row[$field]) ) {
						$row[$field] = "** no permission **";
					} else if ( is_array( $row[$field]) ) {
						$row[$field] = ['** no permission **'];
					} else  {
						$row[$field] = "** no permission **";
					}
				}
			}

		}


		// 删除一系列系统字段
		unset( $row['_acl']);
		unset( $row['_user']);
		unset( $row['_group']);
		unset( $row['deleted_at']);
	}


	/**
	 * 是否有创建记录权限
	 * @param  [type] $row  [description]
	 * @param  [type] $name [description]
	 * @param  [type] $user [description]
	 * @return [type]       [description]
	 */
	function haveCreateRight( $name, $user ) {

		if ( $user['_isadmin'] ) { return true; }

		if ( empty($name )) {
			throw new Excp('未输入过滤条件', 403, $name );
		}



		$tabacl = $this->read( $name );
		return $this->checkAccess('w', $tabacl['record'], ["_user"=>$tabacl['user'], "_group"=>$tabacl['group']], $user );
	}


	function haveRemoveRight( $name, $owner,  $user ) {
		
		if ( $user['_isadmin'] ) { return true; }

		if ( empty($name )) {
			throw new Excp('未输入过滤条件', 403, $name );
		}

		$tabacl = $this->read( $name );
		return $this->checkAccess('d', $tabacl['record'], $owner,  $user);
	}



	/**
	 * 写入数据过滤
	 */
	function writeFilter( & $row, $name, $owner,  $user, $allowed=['_acl', '_user', '_group', '_id', 'created_at','updated_at'] ) {

		if ( $user['_isadmin'] ) { return true; }
		
		if ( empty($name )) {
			throw new Excp('未输入过滤条件', 403, $name );
		}

		if ( empty( $owner['_user']) || empty( $owner['_group']) ) {
			return true;
		}

		$owner['_acl'] = empty($owner['_acl']) ? [] : $owner['_acl'];

		$acl = $this->read( $name );
		$rsacl = array_merge( $acl['fields'], $owner['_acl']);


		// 校验字段权限
		foreach ( $row as $field=>$value ) {
			
			if ( in_array($field, $allowed) ) {
				continue;
			}

			$av = !empty($rsacl[$field])  ? $rsacl[$field] : $rsacl['{default}'];
			if ( !$this->checkAccess('w', $av, $owner, $user ) ) { // 没有写入权限
				unset($row[$field]);
			}
		}

	}





	/**
	 * 验证权限
	 * @param  [type] $m 权限 r/w/d 
	 * @param  [type] $av 权限
	 * @param  [type] $owner  所有者 用户
	 * @param  [type] $user  当前用户
	 * @return bool true/false
	 */
	function checkAccess( $m, $av, $owner, $user ) {
		
		return true;

		$pos = 2;
		if ( $owner['_user'] == $user['_user'] ) {  // 
			$pos = 0;
		} else if ($owner['_group'] == $user['_group']  ) {
			$pos = 1;
		}

		if ( is_array($av) ) {  // 校验指定到用户的权限  
			if (is_array($av[$m]) ) {
				if ( in_array( $user['_user'], $av[$m] )) {
					return true;
				} else if ( in_array( $user['_group'], $av[$m] )) {
					return true;
				}
			}

			return false;
		}

		$av = explode(':', $av);
		if ( count($av) != 3) {
			return false;
		}

		$value = $av[$pos];
		if ( strpos($value, $m) !== false ) {
			return true;
		}

		return false;

	}




	function deleteFilter( & $_id, $option ) {

	}

}

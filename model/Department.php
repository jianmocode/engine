<?php
namespace Xpmse\Model;

/**
 * 部门模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *  \Xpmse\Model\Department
 *
 * USEAGE:
 *
 */


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;


class Department extends Model {
	

	/**
	 * 公司部门数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');

		parent::__construct($param , $driver );
		$this->table('department');
	}



	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {

		try {

			$this->putColumn( 'id', $this->type('integer', ['unique'=>1, "unsigned"=>true]) )  // 部门 ID
				 ->putColumn( 'name', $this->type('string', ['length'=>200]) ) // 部门名称
				 ->putColumn( 'parentid', $this->type('integer', ['default'=>1, "index"=>true, "unsigned"=>true]) )  // 父部门ID
				 ->putColumn( 'order', $this->type('integer', ['default'=>0, "index"=>true, "unsigned"=>true]) )  // 在父部门中的次序值
				 ->putColumn( 'createDeptGroup', $this->type('boolean', ['default'=>false]) )  // 同步创建企业群
				 ->putColumn( 'autoAddUser', $this->type('boolean',['default'=>false]) )  // 自动加入群
				 ->putColumn( 'orgDeptOwner', $this->type('string', ['length'=>200]) )  // 企业群群主
				 ->putColumn( 'deptHiding', $this->type('boolean',['default'=>false]) )  // 是否隐藏部门
				 ->putColumn( 'outerDept', $this->type('boolean', ['default'=>false]) )  // 只能查看员工自己

				 // 可以查看指定隐藏部门的其他部门列表，如果部门隐藏，则此值生效，取值为其他的部门id数组
				 ->putColumn( 'deptPerimits', $this->type('text',['json'=>true]) )  

				 // 可以查看指定隐藏部门的其他人员列表，如果部门隐藏，则此值生效，取值为其他的人员userid组成的的字符串
				 ->putColumn( 'userPerimits', $this->type('text' , ['json'=>true]) )  

				 // 本部门的员工仅可见员工自己为true时，可以配置额外可见部门，值为部门id数组
				 ->putColumn( 'outerPermitDepts', $this->type('text' , ['json'=>true]) )  

				 //本部门的员工仅可见员工自己为true时，可以配置额外可见人员，值为userid数组
				 ->putColumn( 'outerPermitUsers', $this->type('text' , ['json'=>true]) )  

				 // 部门的主管列表 ,取值为由主管的userid组成的字符串，不同的userid使用’ | '符号进行分割
				 ->putColumn( 'deptManagerUseridList', $this->type('text' , ['json'=>true]) )  

				 // Object 部门权限清单 fullname => true/false
				 ->putColumn( 'acl', $this->type('text' , ['json'=>true]) )

				 ;


		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}


	}



	/**
	 * 检查用户是否是该部门的主管
	 * @param  [type]  $userid 用户ID
	 * @param  [type]  $dept   部门数据结构
	 * @return boolean 部门主管返回true, 否则返回false
	 */
	function isManager( $userid, $dept ) {

		if ( !isset( $dept['deptManagerUseridList']) ||  !is_array($dept['deptManagerUseridList']) ) {
			return false;
		}

		$deptManagerUseridList = $dept['deptManagerUseridList'];
		return in_array($userid, $deptManagerUseridList );
	}



	/**
	 * 保存部门信息
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function save( $data ) {

		if ( !empty( $data['id'])  ) {
			$dt = $this->get( $data['id'] );
			if ( !empty($dt) ) {
				return $this->updateBy( 'id', $data );
			}
		}

		return $this->create( $data );
	}



	/**
	 * 读取部门列表
	 * @param  integer $parentid [description]
	 * @return [type]            [description]
	 */
	function deptList( $parentid = null ) {

		if ( $parentid === null ) {
			$resp = $this->select(" order by parentid ");
		}else {
			$resp = $this->select(" where parentid=$parentid order by parentid ");
		}

		$data = (isset($resp['data'])) ? $resp['data'] : [];
		$map = [];
		foreach ($data as $row) {
			$id = $row['id'];
			$map[$id] = $row;
		}

		$resp['map'] = $map;

		return $resp;
	}


	/**
	 * 根据部门ID，读取部门信息
	 * @param  [type] $ids [description]
	 * @return [type]      [description]
	 */
	function getListByIds( $ids  ) {

		$result = [];
		$idstr = ( is_array($ids) ) ?  implode("','", $ids) : '';
		$resp = $this->select(" where id in ('$idstr')" );
		$depts = $this->deptList();

		if (isset($resp['data']) && is_array($resp['data'])) {

			$deptMap = $depts['map'];
			foreach ($resp['data'] as $dept ) {
				$did = $dept['id'];
				$id = $dept['id'];
				$tagName = $dept['name'];
				$parentid = $dept['parentid'];

                while ( isset($deptMap[$parentid]) && $parentid != 1 ) {
                    $id = $deptMap[$parentid]['id'];
                    $parentid  = $deptMap[$parentid]['parentid'];
                    $tagName =   $deptMap[$id]['name']  . ' > ' . $tagName;
                }
				$dept['fullname'] = $tagName;
				$result[$did] = $dept;
			}
		}
		return $result;
	}



	/**
	 * 读取部门列表 （ Tree List )
	 * @param  [type] $parentid [description]
	 * @return [type]           [description]
	 */
	function deptTreeList( $parentid = 0 ) {

		$resp = ['map'=>[],'data'=>[],'total'=>0];

		// $tree = $this->deptTree( $parentid );
		$depts = $this->deptEach(function($dept) {
			return $dept;
		}, $parentid);

		$resp['map'] = $depts;
		$resp['total'] = count($depts);

		foreach ($depts as $dept) {
			array_push($resp['data'], $dept);
		}

		return $resp;
	}






	/**
	 * 读取部门列表(树) 
	 */
	function deptTree( $parentid = 0 ) {

		$data = []; $total = 0;

		if ($parentid == 0 ) {
			$resp = $this->select(" where id=1 ");
		} else {
			$resp = $this->select(" where parentid=$parentid and id<>1 ");
		}

		if ( isset($resp['data']) && isset($resp['total'])  && 
			 is_array($resp['data']) && intval($resp['total']) > 0 ) {

			$data = array_merge( $data, $resp['data'] );
			$total = $total + intval($resp['total']);

			foreach ( $data as $idx=>$dept ) {
				$id = $dept['id'];	
				$data[$idx]['children'] = $this->deptTree( $id );
			}
		}

		
		return ['data'=>$data, 'total'=>$total];
	}



	/**
	 * 读取子部门清单
	 */
	function deptChildrenList( $dept_id ) {
		return $this->deptEach(function($dept){
			return $dept;
		}, $dept_id);
	}


	/**
	 * 读取上一级部门清单
	 */
	function deptUpLevelList( $dept_id ) {
		
		$deptData = $this->deptList();
		$parentData = (isset($deptData['data']) && is_array($deptData['data'])) ? $deptData['data'] : null ;
		$children = $this->deptChildrenList( $dept_id );

		if ( is_array($parentData) ) {
			foreach ($parentData as $idx => $dept ) {
				if (  $dept['id'] == $dept_id ) {
					unset($parentData[$idx]);
					continue;
				}
				foreach ($children as $ci => $cdept ) {
					if ( $dept['id'] == $cdept['id']  ) {
						unset($parentData[$idx]);
					}
				}
			}
		}
		return $parentData;
	}




	/**
	 * 遍历部门数据
	 */
	function deptEach( callable $callback, $dept_id = 0 ) {

		$data = []; $callback_result = [];

		if ($dept_id == 0 ) {
			$resp = $this->select(" where id=1 ");
		} else {
			$resp = $this->select(" where parentid=$dept_id and id<>1 ");
		}

		if ( isset($resp['data']) && isset($resp['total'])  && 
			 is_array($resp['data']) && intval($resp['total']) > 0 ) {

			$data = array_merge( $data, $resp['data'] );

			
			// 参数表
			$arg_list = func_get_args();
			$arg_num = func_num_args();
			$args = [];
			for( $i=2; $i<$arg_num; $i++ ) {
				$args[] = $arg_list[$i];
			}

			foreach ( $data as $idx=>$dept ) {
				$id = $dept['id'];
				$cb_args = array_merge( [$dept], $args );
				
				$callback_result["$id"] = call_user_func_array($callback, $cb_args);
				// 递归调用所有子分类
				$child_result = call_user_func_array( [$this,'deptEach'], array_merge([$callback, $id], $args));
				
				// 合并结果集合
				foreach ($child_result as $id => $ret ) {
					$callback_result[$id] = $ret;
				}
			}
			
		}

		return $callback_result;
	}




	/**
	 * TreeView JSON
	 * @param  integer $parentid [description]
	 * @return [type]            [description]
	 */
	function deptJSON( $parentid = 0, $active = 1, $link=null, $expanded=false ) {

		$data = []; $json = [];

		if ($parentid == 0 ) {
			$resp = $this->select(" where id=1 ");
		} else {
			$resp = $this->select(" where parentid=$parentid and id<>1 ");
		}

		if ( isset($resp['data']) && isset($resp['total'])  && 
			 is_array($resp['data']) && intval($resp['total']) > 0 ) {

			$data = array_merge( $data, $resp['data'] );


			foreach ( $data as $idx=>$dept ) {
				$id = $dept['id'];
				$_id = $dept['_id'];
				
				$href = null;
				if ( is_string($link) ) {
					$href =  str_replace('{ID}', $id, urldecode($link));
					$href =  str_replace('{_ID}', $_id, urldecode($link));
				} else if ( is_callable($link) ) {
					$href = $link($id);
				} else {
					$href =  "#dept-{$id}";
				}

				$json[$idx] = [
					"text"=> $dept['name'],
					"href"=> $href,
					"tags"=> ['0'],
					"_id"=> $_id,
					"id"=> $id
				];

				if ( $active  == $_id ) {
					$json[$idx]['state']['selected'] = true;
					$json[$idx]['state']['expanded'] = true;
				}

				// Expanded :: 
				if ( $expanded == true ) {
					$json[$idx]['state']['expanded'] = true;
				}

				$nodes = json_decode($this->deptJSON( $id, $active, $link, $expanded ), true);
				if ( count($nodes) > 0 ) {
					$json[$idx]['nodes'] = $nodes;
				}
			}
		}

		return json_encode($json);
	}


	/**
	 * 初始化部门 ( 即将废弃 )
	 * @return 
	 */
	function deptInit( $conf = null ) {
		return $this;
	}


	/**
	 * 清空所有数据缓存
	 * @return [type] [description]
	 */
	function cleanCache() {
		$cacheList = ["dept:init:need"];
	}


	/**
	 * 查询部门成员数量
	 * @param  integer $dept_id [description]
	 * @return [type]           [description]
	 */
	function userCount( $dept_id=0 ) {
		$dept_id = ( $dept_id == 0 ) ? 1 : $dept_id;
		$user = M('User');
		$cnt = 0;
		try {

			$resp = $user->select(" WHERE 1 AND 
						( department like ? 
						or department like ? 
						or department like ? 
						or department like ? )", ['count(_id) as cnt'], 
						["%[{$dept_id},%", "%,{$dept_id},%", "%,{$dept_id}]%", "%[{$dept_id}]%"] );
				
			if ( isset( $resp['data'] ) && count($resp['data'] == 1)) {
				$cnt = $resp['data'][0]['cnt'];
			}

			return $cnt;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}

	}


	/**
	 * 部门成员列表
	 * @param  boolean $short_info [description]
	 * @param  [type]  $perpage    [description]
	 * @return [type]              [description]
	 */
	function userList( $dept_id=0, $short_info=true, $page=null, $perpage=null ) {

		$dept_id = ( $dept_id == 0 ) ? 1 : $dept_id;
		$fields =  ( $short_info === true ) ? ['_id','userid','name','avatar','position'] : [];

		// $user = M('User');
		$user = new \Xpmse\User;
		// $resp = $user->select(" WHERE 1 AND  
		// 				( department like ? 
		// 				or department like ? 
		// 				or department like ? 
		// 				or department like ? )", $fields, 
		// 				["%[{$dept_id},%", "%,{$dept_id},%", "%,{$dept_id}]%", "%[{$dept_id}]%"] );

		// return $resp;

		if ( $perpage ===  null ) {
			try {
				$resp = $user->select(" WHERE 1 AND  
						( department like ? 
						or department like ? 
						or department like ? 
						or department like ? )", $fields, 
						["%[{$dept_id},%", "%,{$dept_id},%", "%,{$dept_id}]%", "%[{$dept_id}]%"] );


				foreach ($resp['data'] as $idx => $data) {
					$user->format($resp['data'][$idx]);
				}
				return $resp;

			} catch( Exception $e ) {
				Excp::elog($e);
				throw $e;
			}

		} else {
			try {

				$data = [];
				$qb = $user->query()->select('*')->where("department", 'like', '%' . $dept_id. '%');
				
				$resp = $qb->pgArray($perpage, ['_id'], '', $page);
				for( $i =1; $i<= $resp['last_page']; $i++ ){
					$resp['pages'][] = $i;
				}

				foreach ($resp['data'] as $idx => $data) {
					$user->format($resp['data'][$idx]);
				}

				return $resp;

			} catch ( Exception $e ) {
				Excp::elog($e);
				throw $e;
			}
		}

	}


	/**
	 * 是否需要初始化部门信息 ( 即将废弃 )
	 * @return [type] [description]
	 */
	function deptNeedInit() {

		return false;
	}
	
}


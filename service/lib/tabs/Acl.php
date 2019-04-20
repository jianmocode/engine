<?php
/**
 * 权限模型 ( 数据共享表 ) 
 *
 * CLASS 
 * 		\Xpmse\Tab
 * 		      |
 * 	       AclTable
 *
 * USEAGE:
 *
 */
namespace Xpmse;

include_once(  __DIR__ . '/../Model.php');
include_once(  __DIR__ . '/../Mem.php');
include_once(  __DIR__ . '/../Excp.php');
include_once(  __DIR__ . '/../Err.php');
include_once(  __DIR__ . '/../Conf.php');
include_once(  __DIR__ . '/../Tuan.php');


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Tuan as Tuan;


class AclTable extends Model {


	private $list = null;
	private $cache_prefix = 'ACL:';
		
	/**
	 * 权限控制表
	 * @param integer $company_id [description]
	 */
	function __construct( $options = [] ) {

		$options['prefix'] = 'core_';
		parent::__construct( $options );
		$this->table('acl');
	}

	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {

		// 数据结构
		try {

			// 权限标记
			$this->putColumn( 'key', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

			// 权限名称
			->putColumn( 'name', $this->type('string', ["null"=>false,'length'=>128, 'index'=>true] ) )

			// 权限数值
			->putColumn( 'value', $this->type('text', ["json"=>true] ) )

			// 应用 ID 
			->putColumn( 'app', $this->type('string', ["null"=>false,'length'=>128, 'index'=>true] ) )

			// 权限中文名
			->putColumn( 'cname', $this->type('string', ["null"=>false,'length'=>80] ) )

			// 权限简介
			->putColumn( 'intro', $this->type('string', ['length'=>200] ) )

			// 在列表中的排列顺序
			->putColumn( 'pri', $this->type('integer', ['default'=>0, 'index'=>true] ) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


	/**
	 * 格式化数据
	 * @return Array Object
	 */
	function _format( & $row ) {
		$mem = new Mem( false, $this->cache_prefix );

		if ( isset($row['value']) && is_array($row['value']) ) {

			$row['value_string'] = implode(',', $row['value']);
			$depts = []; $users = []; // 用户 & 部门数据

			// 解析权限标记
			$row['value_style'] = [];
			foreach ($row['value'] as $idx=>$v ) {
				$v = trim( $v );
				if ( $v === 'boss' ) {
					$row['value_style'][$idx] = '企业主|boss|tag-danger';
					$row['value_tags']['boss'] = [
						'value'=>'boss',
						'style'=> 'tag-danger',
						'st'=> 'danger',
						'tag' => '企业主'
					];

				} else if ( $v === 'admin' ) {
					$row['value_style'][$idx] = '管理员|admin|tag-warning';
					$row['value_tags']['admin'] = [
						'value'=>'admin',
						'style'=> 'tag-warning',
						'st'=> 'warning',
						'tag' => '管理员'
					];

				} else if ( $v === 'manager' ) {
					$row['value_style'][$idx] = '主管|manager|tag-primary';
					$row['value_tags']['manager'] = [
						'value'=>'manager',
						'style'=> 'tag-primary',
						'st'=> 'primary',
						'tag' => '主管'
					];

				} else if ( $v === 'user' ) {
					$row['value_style'][$idx] = '员工|user|tag-primary';
					$row['value_tags']['user'] = [
						'value'=>'user',
						'style'=> 'tag-primary',
						'st'=> 'primary',
						'tag' => '员工'
					];

				}else if ( $v === 'vistor' ) {
					$row['value_style'][$idx] = '访客|vistor|tag-success';
					$row['value_tags']['vistor'] = [
						'value'=>'vistor',
						'style'=> 'tag-success',
						'st'=> 'success',
						'tag' => '访客'
					];
					
				} else if ( preg_match('/^dept\-(.+)$/', $v, $match) ) { // 指定部门
					
					$cache = "dept";
					$dept_id  = $match[1];
					$dept = $mem->getJSON("cache:$dept_id");

					if ( $dept == false ) {
						array_push($depts, $dept_id );
					} else {
						$row['value_style'][$idx] = "{$dept['fullname']}|$v|tag-info";
						$row['value_tags'][$v] = [
							'value'=>$v,
							'style'=> 'tag-info',
							'st'=> 'info',
							'tag' => "{$dept['fullname']}"
						];
					}

				} else if ( preg_match('/^user\-(.+)$/', $v, $match) ) {  // 指定用户
					
					$cache = "user";
					$user_id  = $match[1];
					$user = $mem->getJSON("cache:$user_id");

					if ( $user == false ) {
						array_push($users, $user_id );
					} else {
						$row['value_style'][$idx] = "{$user['name']}|$v|tag-info";
						$row['value_tags'][$v] = [
							'value'=>$v,
							'style'=> 'tag-info',
							'st'=> 'info',
							'tag' => $user['name'],
						];
					}
				}
			}


			// 更新部门 & 用户信息
			if ( count($depts) > 0 || count($users) > 0  ) {
				
				$deptMap = $this->getDepts( $depts );
				$userMap = $this->getUsers( $users );
			
				foreach ($row['value'] as $idx=>$v ) {
					if ( preg_match('/^dept\-(.+)$/', $v, $match) ) { // 指定部门
						$dept_id  = $match[1];
						$dept = $deptMap[$dept_id];

						$row['value_style'][$idx] = "{$dept['fullname']}|$v|tag-info";
						$row['value_tags'][$v] = [
							'value'=>$v,
							'style'=> 'tag-info',
							'st'=> 'info',
							'tag' => "{$dept['fullname']}"
						];

					} else if ( preg_match('/^user\-(.+)$/', $v, $match) ) {  // 指定用户

						$user_id  = $match[1];
						$user = $userMap[$user_id];

						$row['value_style'][$idx] = "{$user['name']}|$v|tag-info";
						$row['value_tags'][$v] = [
							'value'=>$v,
							'style'=> 'tag-info',
							'st'=> 'info',
							'tag' => $user['name'],
						];
					}
				}
			}


			$row['value_style_string']  = implode(',',$row['value_style']);
		}

		return $row;
	}


	
	/**
	 * 读取部门资料
	 * @return [type] [description]
	 */
	function getDepts( $ids, $nocache=false ) {

		$depts = [];
		$cache = "dept";
		$api = '/dept/get';

		$mem = new Mem( false, $this->cache_prefix );
		if ( $nocache !== true ) { // 从缓存中读取数据
			foreach ($ids as $idx=>$id ) {
				$dept = $mem->getJSON( "$cache:$id" );
				if ( $dept !== false ) {
					$depts[$id] = $dept;
					unset( $ids[$idx] );
				}
			}
		}

		if (count($ids) == 0 ) {
			return $depts;
		}

		$tuan = new Tuan;
		$resp = $tuan->call($api, [],['ids'=>$ids]);
		if ( $resp['code'] == 0 ) {
			foreach ( $resp['data']  as $id=>$dept ) {
				$depts[$id] = $dept;
				$mem->setJSON( "$cache:$id", $dept,  3600 * 24 );
			}
		}

		return $depts;
	}	


	/**
	 * 读取用户资料
	 * @return [type] [description]
	 */
	function getUsers( $ids, $nocache=false ) {
		$users = [];
		$cache = "user";
		$api = '/user/get';

		$mem = new Mem( false, $this->cache_prefix );
		if ( $nocache !== true ) { // 从缓存中读取数据
			foreach ($ids as $idx=>$id ) {
				$user = $mem->getJSON( "$cache:$id" );
				if ( $user !== false ) {
					$users[$id] = $user;
					unset( $ids[$idx] );
				}
			}
		}

		if (count($ids) == 0 ) {
			return $users;
		}

		$tuan = new Tuan;
		$resp = $tuan->call($api, [],['ids'=>$ids]);

		if ( $resp['code'] == 0 ) {
			foreach ( $resp['data']  as $id=>$user ) {
				$users[$id] = $user;
				$mem->setJSON( "$cache:$id", $user, 3600 * 24 );
			}
		}

		return $users;
	}



	/**
	 * 读取角色信息
	 * @return [type] [description]
	 */
	function getRoles() {
		$row = ['value' => ['boss', 'admin', 'manager', 'user', 'vistor' ]];
		return $this->_format( $row );
	}

	/**
	 * 根据传入tags 数据，格式化
	 * @param  [type] $tags [description]
	 * @return [type]       [description]
	 */
	function getByTags( $tags ) {
		$row  = [];
		if ( is_string($tags) ) {
			$row['value'] = explode(',', $tags);
		} else if ( is_array($tags) ) {
			$row['value']  = $tags;
		}
		return $this->_format( $row );
	}


	/**
	 * 重载 创建方法, 设定权限默认值
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function create( $data  ) {

		if( !isset($data['value']) || !is_array($data['value']) ) {
			$data['value'] = ['boss','admin','user'];
		}

		return parent::create( $data );
	}


	/**
	 * 注册权限信息
	 * 
	 * @param  [type] $options [description]
	 * @return [type]          [description]
	 */
    function register( $options ) {

    	$data = [];
    	$data['app'] = ( isset($options['app']) ) ? $options['app'] : '0'; 
    	$data['name'] = ( isset($options['name']) ) ? $options['name'] : "";
    	$data['cname'] = ( isset($options['cname']) ) ? $options['cname'] : "";
    	$data['intro'] = ( isset($options['intro']) ) ? $options['intro'] : "";
    	$data['pri'] = ( isset($options['pri']) ) ? $options['pri'] : 0;
    	$data['key'] = ( isset($options['key']) ) ? $options['key'] :  $options['app'] .':'. $options['name'];
    	$data['value'] = ( isset($options['value']) && is_array($options['value']) ) ? $options['value'] : ['boss','admin','user'];


    	if ( $data['name'] == null ) {
    		throw new  Excp('权限名称不能为空', '403');
    	}

    	if ( $data['cname'] == null ) {
    		throw new  Excp('权限中文名不能为空', '403');
    	}

    	$resp = $this->create( $data );

    	if ( $resp === false ) {
    		return false;
    	}
    	
    	$this->_format( $resp );

    	return $resp;
	}


	/**
	 * 从JSON文件中读取
	 * @param  [type] $json_file [description]
	 * @return [type]            [description]
	 */
	function loadJSON( $json_file ) {
		if ( !file_exists($json_file) ) {
			throw new  Excp('权限文件不存在', '404', ['acl_file'=>$json_file]);
		}

		$json_text = file_get_contents($json_file);
		$json_data = json_decode($json_text, true);


		if ( $json_data == null || json_last_error() !== JSON_ERROR_NONE || !is_array($json_data) ) {
			throw new  Excp('ACL文件格式不正确', '503', ['acl_file'=>$json_file, 'json_error'=>json_last_error_msg()]);
		}


		$result = true; $errors = [];
		foreach ($json_data as $group => $applist ) {

			if( !is_array($applist) ) { 
				continue;
			}

			foreach ($applist as $app=>$opts ) {
				if( !is_array($opts) ) { 
					continue;
				}

				foreach ($opts as $opt ) {

					$opt['app'] = "{$group}::{$app}";

					if ( $this->register( $opt ) === false ) {
						$result = false;
						$errors[] = $opt;
					}
				}
			}
		}

		if ($result === false ) {
			return $errors;
		}

		return true;
	}



	/**
	 * 清理缓存
	 * @return [type] [description]
	 */
	function cleanCache( $app="" ) {
    	$mem = new Mem( false, $this->cache_prefix );
    	$mem->delete( $app );
	}


	/**
	 * 清空所有权限信息
	 * @return [type] [description]
	 */
	function cleanAll() {

		$ret = true;
		$list  = $this->select("","_id");
		if ( !isset($list['data'])  || !is_array($list['data']) ) {
			return false;
		}

		foreach ($list['data'] as $row) {
			$id = $row['_id'];
			if ( $this->delete( $id ) === false ) {
				$ret = false;
			}
		}

		$this->cleanCache();
		return $ret;
	}

	/**
	 * 读取一条权限
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	function getByKey( $key ) {
		$cache = "list:key:{$key}";
		$mem = new Mem( false, $this->cache_prefix );
		
		// 从缓存中读取数据
		if ( $nocache !== true ) {
			$json_data = $mem->getJSON($cache);
			if ($json_data !== false ) {
				return $json_data;
			}
		}
		
		// 从数据库中查询
		$row = $this->getLine("WHERE key='{$appid}'");
		$this->_format( $row );
		$mem->setJSON( $cache, $row );

		return $row;
	}


	/**
	 * 读取权限表 ( 根据APP查询 )
	 * @param  string $app appid
	 * @return [type]      [description]
	 */
	function getByApp( $appid = '0', $nocache=false ) {


		$cache = "list:{$appid}";
		$mem = new Mem( false, $this->cache_prefix );
		
		// 从缓存中读取数据
		if ( $nocache !== true ) {
			$json_data = $mem->getJSON($cache);
			if ($json_data !== false ) {
				return $json_data;
			}
		}
		
		// 从数据库中查询
		$list = $this->select("WHERE app='{$appid}'");
		$this->formatData( $list );
		$mem->setJSON( $cache, $list );
		return $list;
	}


	/**
	 * 格式化权限数据输出
	 * @param  Array $selectResult [description]
	 * @return Array ['data'=>[], 'map'=>[], 'total'=>0]
	 */
	function formatData( & $selectResult ) {
		
		if ( !isset($selectResult['data']) || !is_array($selectResult['data']) ) {
			$selectResult = ['data'=>[], 'total'=>0 ];
		}

		$selectResult['map'] = [];
		foreach ($selectResult['data']  as $row ) {
			$row = $this->_format( $row );
			$key = $row['key'];
			$selectResult['map'][$key] = $row;
		}

		return $selectResult;
	}



	/**
	 * 读取权限表 ( 废弃 使用GetByApp )
	 * @return [type] [description]
	 */
	function getAll() {
		$cache = "list";
		$mem = new Mem( false, $this->cache_prefix );
		$json_text = $mem->get($cache);
		if ($json_text !== false ) {
			$this->list = json_decode($json_text,true);
			if ( is_array($this->list) ) return $this->list;
		}

		$list  = $this->select();
		if ( !isset($list['data']) || !is_array($list['data']) ) {
			$this->list =[];
			return $this->list;
		}

		$json_data = [];
		foreach ($list['data'] as $row) {
			$fullname = $row['fullname'];
			$json_data[$fullname] = $row;
		}

		$this->list = $json_data;
		$json_text = json_encode($this->list);
		$mem->set( $cache, $json_text);

		return $this->list;

	}


	/**
	 * 检查权限是否已注册
	 * @param  [type]  $fullname [description]
	 * @return boolean           [description]
	 */
	function isRegister( $key, $list = null ) {
		if ( $list == null){
			$list = $this->getByApp();
		}
		return isset($list['map'][$key]);
	}



	/**
	 * 验证是否拥有权限
	 * @param  [type]  $fullname [description]
	 * @param  [type]  $user     [description]
	 * @param  [type]  $dept     [description]
	 * @return boolean           [description]
	 */
	function has( $fullname,  $user ) {

		if (  $this->list == null ) {
			$this->getall();
		}

		$result = false;

		$ac = $this->list[$fullname];
		if ( isset($ac['default']) && is_array($ac['default']) ) {
			$allow  = $ac['default'];
			if( $user['isBoss'] === true && in_array('boss', $allow) ) {
				$result = true;
			}

			if( $user['isAdmin'] === true && in_array('admin', $allow) ) {
				$result = true;
			}

			if( $user['isManager'] === true && in_array('manager', $allow) ) {
				$result = true;
			}

			if( in_array('user', $allow) ) {
				$result = true;
			}
		}

		if ( isset($user['dept_detail']) && is_array($user['dept_detail']) ) {
			foreach ($user['dept_detail'] as $dept) {
				if ( is_array($dept) && is_array($dept['acl']) ) {
					if ( isset($dept['acl'][$fullname]) ) {
						$result = $dept['acl'][$fullname];
					}
				}
			}
		}

		if ( isset( $user['acl'][$fullname]) ) {
			$result = $user['acl'][$fullname];
		}



		return $result;
	}

}
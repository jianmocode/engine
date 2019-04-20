<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;


/**
 * XpmSE钉钉SDK
 */
class Ding {

	private $c = [];
	private $header = [];
	private $ut = null;
	private $access_token = [];


	/**
	 * 钉钉SDK
	 * @param array $conf [description]
	 */
	function __construct( $conf = [] ) {

		if ( !isset($conf['corpid']) || !isset($conf['corpsecret']) || !isset($conf['ssosecret']) ) {
			$conf = Conf::G('dingtalk');
			if ( $conf == null ) {
				throw new Excp('缺少配置信息', '404');
			}
		}
		$this->c = $conf;
		$this->ut = new Utils();
		$this->header = ["Content-Type:application/json"];
	}


	/**
	 * 获取AccessToken
	 * @param  string $type CorpSecret类型， 有效值: crop/sso 
	 * @return 成功返回 string $token,  失败返回 Err $err 错误对象 
	 */
	function getAccessToken( $type='corp' ) {
		
		if ( isset($this->access_token[$type]) ) {
			return $this->access_token[$type];
		}

		$mem = new Mem;
		$cache_name = "cache/ding/access_token/$type";
		$token = $mem->get($cache_name);
		if ( $token !== false ) {
			$this->access_token[$type] = $token;
			return $token;
		}


		
		$corpid = $this->c['corpid'];

		if ( $type == 'corp' ) {
			$corpsecret = $this->c['corpsecret'];
			$api = "https://oapi.dingtalk.com/gettoken";

		} else if ( $type == 'sso' ){
			$api = "https://oapi.dingtalk.com/sso/gettoken";	
			$corpsecret = $this->c['ssosecret'];
		} else {
			return new Err('500', '未知类型', ['type'=>$type, 'corpid'=>$corpid]);
		}


		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["corpid"=>$corpid, 'corpsecret'=>$corpsecret],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['type'=>$type, 'corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp]);
		}

		if ( !isset($resp['access_token'] )) {
			return new Err('500', '未知错误', ['type'=>$type, 'corpid'=>$corpid, 'corpsecret'=>$corpsecret, 'resp'=>$resp]);
		}

		$this->access_token[$type] = $resp['access_token'];

		// 更新缓存
		$mem->set($cache_name, $resp['access_token'], 7000);
		return $resp['access_token'];
	}





	/**
	 * 读取部门列表
	 * @return 成功返回 array $list 部门列表,  失败返回 Err $err 错误对象 
	 */
	function getDepartmentList() {
		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/department/list";

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["access_token"=>$access_token],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['department']) || !is_array($resp['department'])) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['department'];
	}


	/**
	 * 读取部门详情
	 * @return 成功返回 array $list 部门列表,  失败返回 Err $err 错误对象 
	 */
	function getDepartment( $id ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/department/get";

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["access_token"=>$access_token, "id"=>$id],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['id'])) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}
		unset( $resp['errcode'] );
		unset( $resp['errmsg'] );
		return $resp;
	}


	/**
	 * 创建部门
	 * @param  [type]  $name            [description]
	 * @param  integer $parentid        [description]
	 * @param  [type]  $order           [description]
	 * @param  boolean $createDeptGroup [description]
	 * @return [type]                   [description]
	 */
	function createDepartment( $name, $parentid=1, $order=null, $createDeptGroup=false ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/department/create";

		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => ["name"=>$name, "parentid"=>$parentid, "order"=>$order, "createDeptGroup"=>$createDeptGroup]
		]);


		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['id']) ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['id'];
	}


	/**
	 * 更新部门信息
	 * @return [type] [description]
	 */
	function updateDepartment( $id, $opt=[] ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/department/update";

		$data = ['id'=>$id];
		(isset($opt['name'])) && $data['name'] = $opt['name'];
		(isset($opt['parentid'])) && $data['parentid'] = $opt['parentid'];
		(isset($opt['order']) && is_string($opt['order'])) && $data['order'] = $opt['order'];
		(isset($opt['createDeptGroup']) && is_bool($opt['createDeptGroup'])) && $data['createDeptGroup'] = $opt['createDeptGroup'];
		(isset($opt['autoAddUser']) && is_bool($opt['autoAddUser'])) && $data['autoAddUser'] = $opt['autoAddUser'];
		(isset($opt['deptManagerUseridList'])) && $data['deptManagerUseridList'] = $opt['deptManagerUseridList'];
		(isset($opt['deptHiding']) && is_bool($opt['deptHiding'])) && $data['deptHiding'] = $opt['deptHiding'];
		(isset($opt['deptPerimits'])) && $data['deptPerimits'] = $opt['deptPerimits'];
		(isset($opt['orgDeptOwner'])) && $data['orgDeptOwner'] = $opt['orgDeptOwner'];


		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => $data
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['errmsg']) || $resp['errmsg'] != 'ok' &&  $resp['errmsg'] != 'updated' ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return true;
	}




	/**
	 * 删除部门
	 * @param  [type] $id [description]
	 * @return 成功返回 true 失败返回  Err $err 错误对象
	 */
	function deleteDepartment( $id ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/department/delete";

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["access_token"=>$access_token, "id"=>$id],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['errmsg']) || $resp['errmsg'] != 'ok' &&  $resp['errmsg'] != 'delete' ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return true;
	}



	/**
	 * 读取部门成员列表
	 * @return 成功返回 array $list 部门列表,  失败返回 Err $err 错误对象 
	 */
	function getMemberList( $id, $complete=false ) {
		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		if ($complete === false ) {
			$api = "https://oapi.dingtalk.com/user/simplelist";
		} else {
			$api = "https://oapi.dingtalk.com/user/list";
		}

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["access_token"=>$access_token, "department_id"=>$id],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['userlist']) || !is_array($resp['userlist'])) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['userlist'];
	}



	/**
	 * 读取部门成员详情
	 * @param  [type] $userid [description]
	 * @return [type]         [description]
	 */
	function getMember( $userid ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/user/get";

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"query" => ["access_token"=>$access_token, "userid"=>$userid],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['userid'])) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}
		unset( $resp['errcode'] );
		unset( $resp['errmsg'] );
		return $resp;
	}


	/**
	 * 创建团队成员
	 * @param  [type] $userid [description]
	 * @param  array  $opt    [description]
	 * @return [type]         [description]
	 */
	function createMember( $opt=[] ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/user/create";

		$data = [];
		(isset($opt['name'])) && $data['name'] = $opt['name'];
		(isset($opt['department']) && is_array($opt['department'])) && $data['department'] = $opt['department'];
		(isset($opt['orderindepts']) && is_array($opt['orderindepts'])) && $data['orderInDepts'] = $opt['orderindepts'];
		(isset($opt['extattr']) && is_array($opt['extattr'])) && $data['extattr'] = $opt['extattr'];

		(isset($opt['position'])) && $data['position'] = $opt['position'];
		(isset($opt['mobile'])) && $data['mobile'] = $opt['mobile'];
		(isset($opt['tel'])) && $data['tel'] = $opt['tel'];
		(isset($opt['workplace'])) && $data['workPlace'] = $opt['workplace'];
		(isset($opt['remark'])) && $data['remark'] = $opt['remark'];
		(isset($opt['email'])) && $data['email'] = $opt['email'];
		(isset($opt['jobnumber'])) && $data['jobnumber'] = $opt['jobnumber'];

		if (!isset($data['name'])) { 
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'name'=>'成员名称不合法']);
		}

		// (isset($data['orderInDepts'])) && return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'orderindepts'=>'在对应的部门中的排不合法']);
		if (!isset($data['department'])) {
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'department'=>'部门信息不合法']);
		}
		if (!isset($data['mobile'])) {
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'mobile'=>'手机号码信息不合法']);
		}


		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => $data
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['userid']) ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['userid'];
	}



	/**
	 * 更新团队成员详情
	 * @param  [type] $userid [description]
	 * @param  array  $opt    [description]
	 * @return [type]         [description]
	 */
	function updateMember( $userid, $opt=[] ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/user/update";

		$data = ['userid'=>$userid];
		(isset($opt['name'])) && $data['name'] = $opt['name'];
		(isset($opt['department']) && is_array($opt['department'])) && $data['department'] = $opt['department'];
		(isset($opt['orderindepts']) && is_array($opt['orderindepts'])) && $data['orderInDepts'] = $opt['orderindepts'];
		(isset($opt['extattr']) && is_array($opt['extattr'])) && $data['extattr'] = $opt['extattr'];

		(isset($opt['position'])) && $data['position'] = $opt['position'];
		(isset($opt['mobile'])) && $data['mobile'] = $opt['mobile'];
		(isset($opt['tel'])) && $data['tel'] = $opt['tel'];
		(isset($opt['workplace'])) && $data['workPlace'] = $opt['workplace'];
		(isset($opt['remark'])) && $data['remark'] = $opt['remark'];
		(isset($opt['email'])) && $data['email'] = $opt['email'];
		(isset($opt['jobnumber'])) && $data['jobnumber'] = $opt['jobnumber'];


		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => $data
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['errmsg']) || $resp['errmsg'] != 'ok' &&  $resp['errmsg'] != 'updated' ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}


		return true;
	}


	/**
	 * 删除成员
	 * @param  [type] $id [description]
	 * @return 成功返回 true 失败返回  Err $err 错误对象
	 */
	function deleteMember( $userid ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/user/batchdelete";

		$userid = (is_array($userid)) ? $userid : [$userid];

		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => ["useridlist"=>$userid]
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['errmsg']) || $resp['errmsg'] != 'ok' &&  $resp['errmsg'] != 'delete' ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return true;
	}



	/**
	 * 上传媒体文件
	 * @param  [type] $wrapper [description]
	 * @param  string $type    [description]
	 * @return [type]          [description]
	 */
	function uploadMedia( $wrapper, $type="image") {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/media/upload";

		$stor = new Stor;
		$file = $stor->toMedia($wrapper);
		if ( is_a($file, '\Xpmse\Err')) {
			return $file;
		}

		$resp = $this->ut->Request('POST', $api, [
			"type" => 'media',
			"query" => ["access_token"=>$access_token, 'type'=>$type],
			"data" => [ '__files' => [$file] ]
		]);


		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['media_id']) || !isset($resp['type']) ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['media_id'];
	}



	/**
	 * 读取文件地址
	 * @param  [type] $media_id [description]
	 * @return [type]           [description]
	 */
	function getMedia( $media_id ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/media/get";

		$resp = $this->ut->Request('GET', $api, [
			"header" => $this->header,
			"follow" => false,
			"query" => ["access_token"=>$access_token, "media_id"=>$media_id],
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp]);
		}

		if ( !isset($resp['Location'])) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}

		return $resp['Location'];
	}


	/**
	 * 创建微应用
	 * @param  [type] $opt [description]
	 * @return [type]      [description]
	 */
	function createMicroapp( $opt ) {

		if ( !isset($this->access_token['corp']) ) {
			$token = $this->getAccessToken();
			if ( is_a($token, '\Xpmse\Err') ) {
				return $token;
			}
		}

		$access_token = $this->access_token['corp'];
		$api = "https://oapi.dingtalk.com/microapp/create";

		$data = [];
		(isset($opt['icon'])) && $data['appIcon'] = $opt['icon']; 
		(isset($opt['icon']) && strpos($opt['icon'], '@') !== 0 ) && $data['appIcon'] = $this->uploadMedia($opt['icon']);


		(isset($opt['name'])) && $data['appName'] = $opt['name'];
		(isset($opt['desc'])) && $data['appDesc'] = $opt['desc'];
		(isset($opt['moble'])) && $data['homepageUrl'] = $opt['moble'];
		(isset($opt['web'])) && $data['pcHomepageUrl'] = $opt['web'];
		(isset($opt['admin'])) && $data['ompLink'] = $opt['admin'];


		if (!isset($data['appIcon']) || !is_string($data['appIcon'])) { 
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'icon'=>'微应用的图标不合法']);
		}

		if (!isset($data['appName'])) { 
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'name'=>'微应用的名称不合法']);
		}

		if (!isset($data['appDesc'])) { 
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'desc'=>'微应用的描述不合法']);
		}

		if (!isset($data['homepageUrl'])) { 
			return new Err('400035','不合法的参数', ['access_token'=>$access_token, 'opt'=>$opt, 'moble'=>'微应用的移动端主页不合法']);
		}


		$resp = $this->ut->Request('POST', $api, [
			"header" => $this->header,
			"type" => 'json',
			"query" => ["access_token"=>$access_token],
			"data" => $data
		]);

		if ( isset($resp['errcode']) && $resp['errcode'] != 0 ) {
			$resp['errmsg'] = (isset($resp['errmsg'])) ? $resp['errmsg'] : '未知错误';
			return new Err($resp['errcode'], $resp['errmsg'], ['access_token'=>$access_token, 'resp'=>$resp, 'data'=>$data,'opt'=>$opt]);
		}

		if ( !isset($resp['agentid']) ) {
			return new Err('500', '未知错误', ['resp'=>$resp]);
		}
		return $resp['agentid'];

	}


}










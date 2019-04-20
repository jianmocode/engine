<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;

class baasUserController extends baasBaseController {
		

	private $table = null; 
	private $prefix = '_baas_';

	function __construct() {

		parent::__construct();
		$this->table = $this->data['_table'];
		$this->prefix = empty($this->data['_prefix']) ? '' : '_baas_' . $this->data['_prefix'] . '_';

		if ( empty($this->data['_handler']) ) {

			// Bind Table 
			$u = $this->utab();

			if ( !$u->tableExists() ) { // 初始化用户表
				
				$schema =[
					["name"=>"unionid",  "type"=>"string", "option"=>["length"=>64, "unique"=>1, "index"=>true], "acl"=>"-:-:-" ],
					["name"=>"openid",  "type"=>"string", "option"=>["length"=>64, "unique"=>1, "index"=>true], "acl"=>"-:-:-" ],
					["name"=>"nickName",  "type"=>"string", "option"=>["length"=>256], "acl"=>"rw:rw:-"  ],
					["name"=>"gender",  "type"=>"integer", "option"=>["length"=>1 , "index"=>true], "acl"=>"rw:rw:-"  ],
					["name"=>"language",  "type"=>"string", "option"=>["length"=>20], "acl"=>"rw:-:-"  ],
					["name"=>"city",  "type"=>"string", "option"=>["length"=>100], "acl"=>"rw:-:-"  ],
					["name"=>"province",  "type"=>"string", "option"=>["length"=>100], "acl"=>"rw:-:-"  ],
					["name"=>"country",  "type"=>"string", "option"=>["length"=>100], "acl"=>"rw:-:-"  ],
					["name"=>"avatarUrl",  "type"=>"string", "option"=>["length"=>256], "acl"=>"rw:rw:-"  ],
					["name"=>"group",   "type"=>"string", "option"=>["index"=>true, "length"=>64, 'default'=>'member'], "acl"=>"rw:-:-"  ],
					["name"=>"isadmin",   "type"=>"boolean", "option"=>["index"=>true, 'default'=>false], "acl"=>"-:-:-"  ],
					["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
					["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
					["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
				];

				$this->data['acl'] = ( !empty( $this->data['acl'] ) ) ? $this->data['acl'] : [
					"fields" =>[ "{default}"=>"rw:-:-" ],
					"record"=>"-:-:-",
					"table" =>"-:-:-",
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
				$resp = $u->__schema( $schema );

				
			} else {

				// 检查数据表
				$columns = $u->getColumns();
				if( !in_array('openid', $columns ) ) {
					throw new Excp("no openid field", 403, ['columns'=>$columns]);

				} else if ( !in_array('isadmin', $columns ) ) {
					throw new Excp("no isadmin field", 403, ['columns'=>$columns]);

				} else if ( !in_array('group', $columns ) ) {

					throw new Excp("no group field", 403, ['columns'=>$columns]);

				} else if ( !in_array('unionid', $columns ) ) {
					throw new Excp("no unionid field", 403, ['columns'=>$columns]);
				}
			}
		}
	}

	function index() {

		echo json_encode([
				"server" => "Xpm Server V2",
				"status" => "ok"
			]);
	}



	/**
	 * 用户登录，验证用户 Session
	 * @return [type] [description]
	 */
	function login() {

        // Get Session_id 
        session_start();

		if ( empty($_SESSION['_user']) || !empty($this->data['rawData']) ) {

			$resp = $this->wxapp->getSessionKey( $this->data['code'] );
			$session_key = $resp['session_key'];

			// 读取用户信息
			if ( empty($this->data['rawData']) ) {
				$data = $resp;	

			} else {
				$string = $this->data['rawData'] . $session_key;
				$s = sha1($string);
				if ( $s !== $this->data['signature']  ) {
					throw new Excp("数据签名不正确", 403, ['data'=>$this->data, "session_key"=>$session_key,"string"=>$string]);
				}

				$data = json_decode( $this->data['rawData'], true );
				if ( $data === false || $data == null ) {
					Utils::json_decode($this->data['rawData']);
					return;
				}

				$data = array_merge( $data, $resp );
			}


			if ( empty($this->data['_handler']) ) {

				$u = $this->utab();
				$user = $u->getLine("WHERE openid=? LIMIT 1", [], [$resp['openid']]);
				if ( $user == null ) {
					$user = $u->create( $data );
					$data = $user;
				}
				
				$data['_user'] = $user['_id'];
				$data['_group'] = $user['group'];
				$_user = $u->update( $user['_id'],  $data );
			
				$_SESSION['_user']  = $user['_id'];
				$_SESSION['_group'] = $user['group'];
				$_SESSION['_isadmin'] = $user['isadmin'];
				$_SESSION['_loginInfo'] = $data;

				$result = true;
                $_id = $_SESSION['_user'];
                $client_token = null;

			} else {

                $dt = M('Data');
				$_user = $dt->query( $this->data['_handler'], $_REQUEST,  $data );
				if ( $resp !== false ) {
					$result = true;
					$_id = $_SESSION['_user'] =  $_user["_id"];
                    $client_token = $_user["client_token"];
                    $_user["updated_at"] = date("Y-m-d H:i:s");
					$_SESSION['_uinfo'] = $_user;
                    $_SESSION['_loginInfo'] = $data;
                    
				}
			}

		} else {
            $_id = $_SESSION['_user'];
            
			$_user = $_SESSION['_uinfo'];
            $_user["from"] = 'session';
            $client_token = $_user["client_token"];
			$result = true;
		}


		echo json_encode( ['id'=> session_id(), 'result'=>$result, '_id'=>$_id, "_client_token"=>$client_token, '_user'=>$_user , 'data'=>$data ]);

	}


	// 退出登录
	function logout() {

		unset( $_SESSION['_user'] );
		unset( $_SESSION['_group'] );
		unset( $_SESSION['_isadmin'] );
		unset( $_SESSION['_loginInfo'] );
		echo json_encode( ['code'=>0, 'result'=>'ok']);
	}
	


	private function utab() {
		return M( 'Table', $this->table, ['prefix'=>$this->prefix]);
	}

	private function table_name() {
		return $this->prefix . $this->table;
	}


}
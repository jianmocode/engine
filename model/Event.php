<?php
namespace Xpmse\Model;

/**
 * 云端事件
 *
 * CLASS 
 *   \Xpmse\Model\Event
 *
 * USEAGE:
 *
 */


use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;
use \Xpmse\Log as Log;

class Event {

	private $events = [];
	private $resps = [];
	private $table_prefix = '_baas_';
	private $wxconf = [];

	function __construct( $options, $events = [] ) {
		$this->events = is_array($events)? $events  : [];
		$this->table_prefix = $options['table.prefix'];
		$this->wxconf['wxapp.appid'] = $options['wxapp.appid'];
		$this->wxconf['wxapp.secret'] = $options['wxapp.secret'];
	}

	function set( $events ) {
		$this->events = is_array($events)? $events  : [];
		$keys = array_keys($this->events);
		foreach ($keys as $key ) {
			$this->resps[$key] = is_array($this->resps[$key])? $this->resps[$key] : [];
		}
		return $this;
	}


	/**
	 * 触发事件
	 * 
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	function trigger( $name, $data=[], $useReturnData=false, $acl=false ) {
		
		$evts = is_array($this->events[$name]) ? $this->events[$name] : null;

		if ( empty($evts) ) return [];

		if ( $useReturnData ) { // 启用结果集数据模板
			$respAsData = [];
		}

		foreach ($evts as $idx => $evt ) {
			$method = !empty($evt['cmd']) ?  'event_' . $evt['cmd'] : null;
			$params = !empty($evt['params']) ? $evt['params'] : "";
			$resp = null;

			if ( !empty($method) &&  method_exists($this, $method) ) {
				try {
					
					if ( !empty($data) ) {
						$this->filterParams($params, $data );
					}

					if ( $useReturnData ) {  // 启用结果集数据模板
						$this->filterParams($params, $respAsData, true );
					}

					$resp = $this->$method( $params, $acl );
					if ( $useReturnData ) {  // 启用结果集数据模板
						foreach ($resp as $key => $val) {
							$respAsData["$idx.$key"] = $val;
						}
					}

					// utils::out($respAsData );

				} catch( Excp $e ) {
					$resp = $e->toArray();
				}
			}

			$this->resps[$name][$idx] = $resp;
		}

		return $this->resps[$name];
	}



	/**
	 * 参数赋值
	 * @param  [type] $params [description]
	 * @param  array  $data   [description]
	 * @return [type]         [description]
	 */
	function filterParams( & $params, $data=[], $isResponse=false ) {
		
		foreach ($params as $idx => $value ) {

			if ( is_array($value) ) {
				$params[$idx] = $this->filterParams($params[$idx], $data, $isResponse );

			} else if ( is_string($value)  ) {	

				$reg = $isResponse ? '/\{\{([0-9a-z\.A-Z\_]+)\}\}/' : '/\{\{([0-9a-zA-Z\_]+)\}\}/';
				if ( preg_match_all($reg, $value, $match ) ) {

					foreach ($match[1] as $key) {
						$val = !empty($data[$key]) ? $data[$key] : "";
						if ( is_array($val) ) {
							$params[$idx] = $val;
						} else  {
							$params[$idx] = str_replace('{{'.$key.'}}', $val, $params[$idx] );
						}
					}
				}
			}
		}

		return $params;
	}


	/**
	 * 更新记录
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	private function event_update( $params, $acl=false ) {
		if ( empty($params['table']) ) {
			throw new Excp('请提供数据表名称', 502, ["params"=>$params]);
		}

		if ( empty($params['data']) ) {
			throw new Excp('请提供数据表内容', 502, ["params"=>$params]);
		}

		$unique = !empty($params['unique']) ? $params['unique'] : '_id';
	
		if ( empty($params['data'][$unique]) ) {
			throw new Excp('请提供唯一索引字段数值', 502, ["params"=>$params, 'unique'=>$unique]);
		}


		$tab = M( 'Table', $params['table'], ['prefix'=>$this->table_prefix]);
		if ( $acl === true ) {
			$acl = M('Tabacl');
			$currUser = $this->currUser();
			$data =$params['data'];
			$uni_key = $params['unique'];
			$owner = $tab->getLine("WHERE $uni_key=? LIMIT 1",  ["_user","_group", "_acl"], [$data[$uni_key]]);
			$acl->writeFilter( $data, $this->table_name($params['table']), $owner, $currUser );
			$resp = $tab->updateBy( $uni_key, $data );
			$acl->readFilter( $resp, $this->table_name($params['table']), $owner, $currUser );
			return $resp;
		}


		return $tab->updateBy(
			$unique,
			$params['data']
		);

	}


	/**
	 * 创建记录
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	private function event_create( $params, $acl=false ) {

		if ( empty($params['table']) ) {
			throw new Excp('请提供数据表名称', 502, ["params"=>$params]);
		}

		if ( empty($params['data']) ) {
			throw new Excp('请提供数据表内容', 502, ["params"=>$params]);
		}

		$tab = M( 'Table', $params['table'], ['prefix'=>$this->table_prefix]);
		if ( $acl === true ) {

			$acl = M('Tabacl');
			$currUser = $this->currUser();
			$data =$params['data'];

			if ( !$acl->haveCreateRight( $this->table_name( $params['table'] ), $currUser) ) {
				throw new Excp("没有创建权限", 403, [
						"data"=>$data,
						"whoami"=>$currUser, 
						'table'=> $this->table_name( $params['table'] )
				]);
			}

			$data = array_merge( $data, $currUser );
			$acl->writeFilter( $data, $this->table_name( $params['table'] ), 
				$currUser, $currUser );
			$resp = $tab->create( $data );
			$acl->readFilter( $resp, $this->table_name( $params['table']), $currUser, $currUser );

			return $resp;
		}

		return $tab->create($params['data']);
	}




	/**
	 * 运行应用
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	private function event_app( $params, $acl=false ) {


		$appname =  $params['name'];
		$api = is_array($params['api']) ? $params['api'] : ['defaults','index'];
		$data = is_array($params['data']) ? $params['data'] : [];
		$query =  is_array($api[2]) ? $api[2] : [];

		$_api = $appname . '/'. $api[0] . '/'. $api[1];
	
		$dt = M('Data');
		$resp = $dt->query( $_api, $query, $data );
		return json_encode( $resp , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );



		return;

		require_once( AROOT . 'controller' . DS . 'core/app/route.class.php' );
		
		$appname =  $params['name'];
		$apporg =  $params['org'];
		$api = is_array($params['api']) ? $params['api'] : ['defaults','index'];
		$params['data'] = is_array($params['data']) ? $params['data'] : [];



		$_GET['n'] = 'core-app';
		$_GET['c'] = 'route';
		$_GET['a'] = 'portal';

		$_GET['app_name'] = $appname;
		$_GET['app_org'] = $apporg;

		$_GET['app_c'] = $api[0];
		$_GET['app_a'] = $api[1];
		
		$query =  is_array($api[2]) ? $api[2] : [];
		$_GET = array_merge($_GET, $query );

		$_POST = $params['data'];
		$_POST['wxapp.appid'] = $this->wxconf['wxapp.appid'];
		$_POST['wxapp.secret'] = $this->wxconf['wxapp.secret'];

		$_REQUEST = array_merge(  $_GET, $_POST );


		$route = new \coreAppRouteController();
		ob_start();
		call_user_func([$route, 'portal']);
		$content = ob_get_contents();
	    ob_clean();

	    if ( $content != null  ) {
	    	$resp = json_decode( $content, true );

	    	// 异常输出
	    	if ( isset($resp['result']) && 
	    		 isset( $resp['content']) && 
	    		$resp['result'] === false ) {
	    		return Utils::get($resp['content']);
	    	}

	    	if ( json_last_error() == JSON_ERROR_NONE )  {
	    		return $resp;
	    	}
	    }

	    return $content;
	}


	function get( $name ) {
		return is_array($this->$resp[$name]) ? $this->$resp[$name] : []; 
	}

	function response() {
		return $this->resps;
	}

	/**
	 * 读取当前会话客户
	 * @return [type] [description]
	 */

	private function currUser() {

		$user = (isset($_SESSION['_user'])) ? $_SESSION['_user'] : session_id();
		$group = (isset($_SESSION['_group'])) ? $_SESSION['_group'] : 'guest';
		$isadmin= (isset($_SESSION['_isadmin'])) ? $_SESSION['_isadmin'] : 0;
		
		$data = [];
		$data['_user'] = $user;
		$data['_group'] = $group;
		$data['_isadmin'] = $isadmin;
		return $data;
	}

	private function table_name( $table ) {
		return $this->table_prefix . $table;
	}


	private function tab( $table ) {
		return M( 'Table', $table, ['prefix'=>$this->table_prefix]);
	}


}
<?php
// XpmSE系统初始化控制器
include_once( AROOT . 'controller' . DS . 'public.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Mem as Mem;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;
use \Xpmse\Acl as Acl;
use \Xpmse\Tab as Tab;

define('_ES_REFRESH_TIME', 0 );
set_time_limit(0);

function sys_option() {

	// 注册桌面
	$opt = new \Xpmse\Option;
	$dashboard = $opt->get("dashboard");
	if ( $dashboard === null ) {
		$opt->register(
			"控制台地址", 
			"dashboard", 
			"",
			10
		);
    }
    
    // 注册自定义菜单
    $custmenu = $opt->get("custmenu");
	if ( $custmenu === null ) {
        $data = [
            "active" => false,
            "menu" => []
        ];
        $menufile = __DIR__ . "/../config/menu.json";
        if ( file_exists($menufile) ) {
            $text = file_get_contents( $menufile );
            $json_data = json_decode( $text, true );
            if ( $json_data !== false ) {
                $data = [
                    "active" => false,
                    "menu" => $json_data
                ];
            }
        }
        
		$opt->register(
			"自定义菜单", 
			"custmenu", 
			json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
			2
		);
	}
}



/**
 * core run
 * @param  [type] $q     [description]
 * @param  [type] $query [description]
 * @param  [type] $data  [description]
 * @return [type]        [description]
 */
function core_run_action( $q=[], $query=[], $data=[] ){

	$controller = explode('.', $q['c']);

	if ( end($controller) == 'class' ) {
		$q['c'] = $controller[0];
	} else {
		$controller[1] = 'class';
	}

	$q['ns'] = str_replace('-', '/', $q['ns'] );

	if ( $q['ns'][0] == '/') {
		$_GET['n'] = substr(str_replace('/', '-', $q['ns'] ), 1);
	} else {
		$_GET['n'] = str_replace('/', '-', $q['ns'] );
	}


	$_GET['c'] = $q['c'];
	$_GET['a'] = $q['a'];

	$_GET = array_merge( $query, $_GET );
	$_POST = array_merge( $data, $_POST );
	$_REQUEST = array_merge( $_REQUEST , $_GET, $_POST );
	$_FILES = $file;

	$class_file = AROOT .'controller/' . $q['ns'] . '/' . implode('.', $controller) . '.php' ;
	$class_name = '\\' . str_replace('/', '', $q['ns'] ) . $q['c'] . 'Controller';

	if ( !file_exists( $class_file) ) {
		error('控制器类文件不存在 ( '. $class_file .' )');
		exit;
	}
	require_once( $class_file );

	if ( !class_exists($class_name) ) {

		error('控制器类不存在 ( '. $class_name .' )');
		exit;
	}


	try {
		$api = new $class_name();

		ob_start();
		call_user_func([$api, $q['a']], true );
		$content = ob_get_contents();
        ob_clean();
        
        $resp = json_decode( $content, true );

        if ( $resp !== false  && $resp !== null) {
        	return json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        } else {
        	return $content;
        }

	} catch( Excp $e ) {
		error( $e->getMessage() . "\n" );
	}
}


function error(){
	return [];
}


class initController {

	private $mem = null;
	private $st = 'status';
	private $option = [];

	function __construct() {

		// parent::__construct();
		@session_start();
		$this->mem = new Mem( false, 'init:' );
		$this->option = [
			'tab' => $_SESSION['_setup:tab'],
			'redis' => $_SESSION['_setup:redis'],
			'user'=> $_SESSION['_setup:user'],
			'app'=> $_SESSION['_setup:app'],
			'random' => $_SESSION['_setup:random'],
			'sys' =>  isset($_SESSION['_setup:sys']) ? $_SESSION['_setup:sys'] : []
		];

	}


	


	// 安装 ( 创建数据表/扫描安装应用等 )
	function setup() {
		
		session_write_close();
		if ( $_POST['random'] != $this->option['random'] ) {
			echo json_encode(['code'=>500, 'status'=>'failure','message'=>'非法请求来源']); 
			return;
		}

		$this->clearCache();
		$this->tabInit( $this->option['tab'] );
		$this->dataInit();
		$this->installApps( $this->option['app'] );
		$this->initComplete();
		$this->setStaus(100,'安装完毕');
		
		// 清空Session
		@session_start();
		session_destroy();
		$_SESSION['_setup:random'] = $this->option['random'];
		session_write_close();

		// 返回结果
		echo json_encode(['code'=>1, 'status'=>'success','message'=>'安装完毕']);
	}



	// 查询安装状态
	function setupStatus() {
		session_write_close();
		if ( $_POST['random'] != $this->option['random'] ) {
			echo json_encode(['code'=>500, 'status'=>'failure','message'=>'非法请求来源']);
			return;
		}

		$init = ( empty($_POST['init']) ) ? '0' : $_POST['init'];
		if ( $init == "1" ) {
			$this->setStaus(10, '准备安装');
		}

		echo json_encode($this->getStaus() );
	}


	/**
	 * 安装完毕后调用
	 * @return 
	 */
	protected function initComplete() {

		$this->configJSON( $this->option['sys'] ); // 生成Config.json 
		$this->clearCache(); //清空缓存

		// 生成安装锁定
		$root = dirname( _XPMSE_CONFIG_FILE );
		try {
			file_put_contents( $root . '/service.lock', date('Y-m-d H:i:s') );
		} catch( Exception $e) {}

	}


	/**
	 * 根据信息生成配置
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function configJSON( $option ) {

		$c = new Conf;
		$conf = $c->renew();
		$logo_path = (isset($option['logo_path'])) ? $option['logo_path'] : '';
		if ( isset($option['logo_path']) ) {
			unset($option['logo_path']);
			unset($option['logo_url']);
		}
		if ( isset($option['name']) && isset($option['short']) && isset($option['company']) ) {
			$conf['general'] = array_merge( $conf['general'], $option );
		}

		// 生成图标文件
		if( !empty($logo_path)  && is_array($conf['general']['logo'])) {
			$stor = new Stor;
			$root = ( !empty( $conf['defaults']['storage']['public']) ) ? $conf['defaults']['storage']['public'] : 'local://public';
			foreach ($conf['general']['logo'] as $idx=>$path ) {
				if ( $idx == 'default' ) {
					try {
						if ( $stor->cp($logo_path, "{$root}::/defaults/favicon.png") ) {
							$conf['general']['logo'][$idx] = "{$root}::/defaults/favicon.png";	
						}
					} catch (Excp $e) {}

				} elseif ( $idx == 'fav' ) {
					try {
						$dst_warpper = "{$root}::/defaults/fav.png";
						if ( $stor->resize( $logo_path, $dst_warpper, $width=32, $height=32 ) ) {
							$conf['general']['logo'][$idx] = $dst_warpper;
						}
					}catch( Excp $e ){}

				} else if ( is_numeric($idx) ) {
					try { 
						$dst_warpper = "{$root}::/defaults/favicon_{$idx}.png";
						if ( $stor->resize( $logo_path, $dst_warpper, $width=$idx, $height=$idx ) ) {
							$conf['general']['logo'][$idx] = $dst_warpper;
						}
					} catch (Excp $e) {}
				}
			}

			// try { 
			// 	$stor->del( $logo_path );
			// } catch( Excp $e ) {}
		}

		try {
			$conf['general']['at'] = date('Y-m-d H:i:s');
			file_put_contents( _XPMSE_CONFIG_FILE, json_encode( $conf, JSON_PRETTY_PRINT ) );
		} catch( Exception $e ){ 

		}

		return true;
	}


	/**
	 * 数据表结构初始化 ( P: 20 - 40 )
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function tabInit( $option =[] ) {

		session_write_close();
		$p = 20; // 20 - 40
		
		$this->setStaus($p, '初始化数据表');
		$option['format'] = ( !isset($option['format']) ) ? '0' : $option['format'];

		$tabs = [
			'acl' =>  ['name'=>'acl', 'cname'=>'权限表', 'get' => function() { return new Acl; } ],
			'api' =>  ['name'=>'api', 'cname'=>'开发者表', 'get' => function() {  return M('Secret'); }],
			'tabacl' =>  ['name'=>'tabacl', 'cname'=>'BaaS权限表', 'get' => function() {  return M('Tabacl'); }],
			'project' =>  ['name'=>'project', 'cname'=>'项目表', 'get' => function() {  return M('Project'); }],
			'app' =>  ['name'=>'app_local', 'cname'=>'应用表', 'get' => function() {  return M('App'); }],
			'page' =>  ['name'=>'page', 'cname'=>'页面表', 'get' => function() {  return M('Page'); }],
			'media' =>  ['name'=>'media', 'cname'=>'媒体文件表', 'get' => function() {  return M('Media'); }],
			'dept' => ['name'=>'department',  'cname'=>'部门表', 'get' => function() {  return  M('Department'); }],
			'user' => ['name'=>'department_user',  'cname'=>'用户表',  'get' => function() {  return  M('User'); }],
			'task' => ['name'=>'task',  'cname'=>'任务表',  'get' => function() {  return  M('task'); }],
            'option' => ['name'=>'option',  'cname'=>'配置表',  'get' => function() {  return  M('Option'); }],
            'service' => ['name'=>'service',  'cname'=>'服务表',  'get' => function() {  return  M('Service'); }],
            'search' => ['name'=>'search',  'cname'=>'全文检索表',  'get' => function() {  return new \Xpmse\Search();}],
            'code' => ['name'=>'code',  'cname'=>'代码模板表',  'get' => function() {  return  M('Code'); }],
            'domain' => ['name'=>'domain',  'cname'=>'域名表',  'get' => function() {  return  M('Domain'); }]
		];

		// 删除主要数据表
		if ( $option['format'] == '1' ) {

			$p = $p + 1;
			$this->setStaus($p, "清空数据表" );
			
			foreach ( $tabs as $m=>$t ) {
				try {
					$tab = $t['get']();
					if ( method_exists($tab, 'dropTable') ) {
						$p = $p + 1;
						$this->setStaus($p, "清空{$t['cname']}" );
						$tab->dropTable();
						// Tab::wait( _ES_REFRESH_TIME );
					}			
				} catch ( Excp $e ) {
					echo $e->toJSON();
					die();
				} catch ( Exception $e  ) {
					echo Excp::etoJSON( $e );
					die();
				}
			}
			
		}


		// 初始化对象
		foreach ( $tabs as $m=>$t ) {
			try {
				$tab = $t['get']();
				if ( method_exists($tab, '__schema') ) {
					$p = $p + 1;
					$this->setStaus($p, "初始化{$t['cname']}" );
					$tab->__schema();
					// 初始化默认数据
                    if ( method_exists($tab, '__defaults') ) {
                        $tab->__defaults();
                    }
				}			
			} catch ( Excp $e ) {
				echo $e->toJSON();
				die();
			} catch ( Exception $e  ) {
				echo Excp::etoJSON( $e );
				die();
			}
		}


		
		return true;
	}



	/**
	 * 设定状态信息
	 * @param [type] $p       [description]
	 * @param [type] $message [description]
	 */
	protected function setStaus( $p, $message = "" ) {
		return $this->mem->setJSON( $this->st, ['p'=>$p, 'message'=>$message] );
	}


	/**
	 * 读取状态信息
	 * @return [type] [description]
	 */
	protected function getStaus() {
		$resp = $this->mem->getJSON( $this->st );
		if ( $resp !== false ){
			return $resp;
		}
		return ['p'=>0, 'message'=>'状态数据异常'];
	}


	/**
	 * 数据初始化 ( 40 - 60)
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function dataInit( $option = [] ) {

	
		// 重新创建公司和部门
		$p = 40;
		$this->setStaus($p, "初始化公司和部门" );
		$this->deptInit( $this->option['sys'] );

		// 创建管理员
		$p = $p + 7;
		$this->setStaus($p, "初始化系统管理员" );
		$uid = $this->userInit(  $this->option['user']  );

		// 初始化API 
		$p = $p + 7;
		$this->setStaus($p, "初始化API授权" );
		$this->apiInit([]);


		$u = M('User');
		$u->setSession( $uid );

		try {
			$p = $p + 1;
			$this->setStaus($p, "初始化微信配置表" );
			$content = core_run_action(['ns'=>'baas/admin', 'c'=>'conf', 'a'=>'upgrade']);
			
		} catch ( Excp $e ) {
			echo $e->toJSON();
			die();
		}

		try {
			$p = $p + 1;
			$this->setStaus($p, "初始化证书管理表" );
			$content = core_run_action(['ns'=>'baas/admin', 'c'=>'cert', 'a'=>'upgrade']);
			
		} catch ( Excp $e ) {
			echo $e->toJSON();
			die();
		}

		// 初始化系统默认配置 sys_option
		try {
			$p = $p + 1;
			$this->setStaus($p, "初始化系统配置选项" );
			sys_option();
		} catch( Excp $e ) {
			echo $e->toJSON();
			die();
		}
		$u->logout();
	}



	/**
	 * 初始化部门信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function deptInit( $option = [] ) {

		$d = M('Department');
		$company = (isset($option['company'])) ? $option['company'] : '我的公司';
		$dept_id = $d->getVar('_id', 'WHERE id="1" LIMIT 1');
		if ( $dept_id != null ) {
			$d->delete($dept_id);
			// Tab::wait(_ES_REFRESH_TIME);
		}

		$resp = $d->create([
			'id'=>1,
			'name'=>$company,
			'parentid' => 1,
			'createDeptGroup'=>true
		]);

		if ( $resp === false ) {
			throw new Excp('初始化部门错误', 500, $d->errors );
		}

		return true;
	}


	/**
	 * 初始化管理员信息 (创建管理员)
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function userInit( $option =[] ) {

		// Tab::wait(_ES_REFRESH_TIME);

		$u = M('User');
		$id = $u->getVar('_id', 'WHERE mobile="13000000000" LIMIT 1');
		if ( $id  != null  ) {
			$u->delete($id);
		}

		$name = ( isset($option['name']) ) ? $option['name'] : 'XpmSE';
		$mobile = ( isset($option['mobile']) ) ? $option['mobile'] : '13000000000';
		$password = ( isset($option['password']) ) ? $option['password'] : 'TuanduiMao1221';

		// 清空数据
		$id = $u->getVar('_id', "WHERE mobile='$mobile' LIMIT 1");
		if ( $id  != null  ) {
			$u->delete($id);
			// Tab::wait(_ES_REFRESH_TIME);
		}

		$avatar = $u->genAvatar( $name );
		$resp = $u->create([
			'userid'=> $u->genUserid(),
			'name'=>$name,
			'avatar'=>$avatar['avatar'],
			'mobile'=>$mobile,
			'isAdmin'=>true,
			'isBoss'=>true,
			'department'=>[1],
			'password'=> password_hash( $password, PASSWORD_BCRYPT, ['cost'=>12] )
		]);		

		if ( $resp === false ) {
			throw new Excp('初始化用户错误', 500, $u->errors );
		}

		return $resp["_id"];
	}


	/**
	 * 初始化API Tocken 信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function apiInit( $option =[] ) {

		$a = M('Secret');

		$keypair = $a->genKeyPair();
		$appid = $keypair['appid'];
		$appsecret = $keypair['appsecret'];

		$id = $a->getVar('_id', 'WHERE appid="'.$appid.'" LIMIT 1');
		if ( $id  != null  ) {
			$a->delete($id);
			// Tab::wait(_ES_REFRESH_TIME);
		}

		$resp = $a->create([
			'appid'=>$appid,
			'appsecret'=>$appsecret
		]);

		if ( $resp === false ) {
			throw new Excp('初始化API账号错误', 500, $a->errors );
		}

		return true;
	}


	/**
	 * 自动安装应用 ( 60 -100 )
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function installApps( $option =[] ) {

		$this->setStaus(60, "正在扫描应用" );
		$app = M('App');
		$app->cleanCache();
		$app->scan( true );
		// Tab::wait( _ES_REFRESH_TIME );

		if ( $option['auto'] == 'on' ) {

			$p = 70;
			$this->setStaus($p, "正在安装应用" );
			$apps = $app->getUninstalled( true );
			if ( !is_array($apps['data']) ) { 
				throw new Excp('读取应用信息失败', 500, ['apps'=>$apps] );
			}

			// 计算每个应用的步长
			if (  count($apps['data'])  >= 1 ) {
				$step = floor( 30 / count($apps['data']) );
			} else {
				$step = 30;
			}

			// 遍历并安装应用
			foreach ( $apps['data'] as $row ) {
				$id = $row['_id'];
				$appid = $row['appid'];
				$appname = "{$row['cname']}";
				try {
					$p = $p + $step;
					$this->setStaus($p, "正在安装 $appname" );
					$app->setup($appid, 'install');		
				} catch ( Excp $e ) {
					$app->update($id, ['status'=>'uninstalled']);
				}
			}

			// Tab::wait( _ES_REFRESH_TIME );
		}


		return true;
	}



	/**
	 * 载入权限配置
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function aclInit( $option =[] ) {

	}


	/**
	 * 清空缓存信息
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	protected function clearCache( $option =[] ) {
		
		// 清空所有
		$mem = new Mem(false, '');
		$mem->delete('');

		$conf = new Conf;
		$conf->renew();
	}

}
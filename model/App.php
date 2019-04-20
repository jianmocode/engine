<?php
namespace Xpmse\Model;
define('DEFAULT_ORG', 'xpmse');
define('DEFAULT_PROXY', 'http://localhost.xpmapp.com');


/**
 * 
 * 应用模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\App
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



class App extends Model {

	/**
	 * 校验应用  Package 字段
	 * @var [type]
	 */
	private $package_json_required = [ 
		"name" =>  ['method'=>'is_string'], 
		"org" =>   ['method'=>'is_string'], 
		"cname"=>  ['method'=>'is_string'],
		"intro"=>  ['method'=>'is_string'], 
		"detail" => ['method'=>'is_string'], 
		"document" =>  ['method'=>'is_string'], 
		"author"=> ['method'=>'is_string'], 
		"homepage"=> ['method'=>'is_string'], 
		"dependencies"=> ['method'=>'is_array'], 
		"setup"=> ['method'=>'is_array']
	];

	/**
	 * 默认 Secret 
	 * @var array
	 */
	private $secret = [];


	/**
	 * 应用数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
		$this->table('app');

		if ( empty($param['secret']) ) {
			try {
				$sc = new \Xpmse\Secret;
				$param['secret'] = $sc->getFirstKeypair();
			} catch( Excp  $e ) {}
		}
		
		$this->secret = $param['secret'];

	}

	public static function resetStatus( $app ) {
		$last_error = error_get_last();
		var_dump('resetStatus', $last_error );

		if($last_error['type'] === E_ERROR) {
			print_r( $app  );
		}
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {

		// 数据结构
		try {

			// 应用ID
			$this->putColumn( 'appid', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

			// 应用路径
			->putColumn( 'path', $this->type('string', ['unique'=>1, "null"=>false,'length'=>200] ) )

			// 代理服务器脚本
			->putColumn( 'proxy_script', $this->type('string', ["null"=>true,'length'=>256] ) )

			// 代理服务器
			->putColumn( 'proxy_on', $this->type('boolean', ["default"=>1] ) )

			// 代理服务器
			->putColumn( 'proxy', $this->type('string', ["null"=>true,'length'=>256] ) )


			// 所有者机构 ( 组织 )
			->putColumn( 'org', $this->type('string', [ "null"=>false, "index"=>true, 'default'=>DEFAULT_ORG, 'length'=>100] ) )

			// 应用名称
			->putColumn( 'name', $this->type('string', ["null"=>false, "index"=>true, 'length'=>100] ) )

			// 应用实例名称 alias
			->putColumn( 'alias', $this->type('string', ["null"=>false, "index"=>true, 'length'=>100] ) )

			// 程序语言
			->putColumn( 'lang', $this->type('string', ["null"=>false, 'length'=>50] ) )

			// 应用 SLUG （ org/name )
			->putColumn( 'slug', $this->type('string', ["null"=>false, "unique"=>1, 'length'=>200] ) )			

			// 中文名称
			->putColumn( 'cname', $this->type('string', [ "null"=>false,'length'=>200] ) )

			// 版本号
			->putColumn( 'version', $this->type('string', ['length'=>200] ) )

			// 图标
			->putColumn( 'icon', $this->type('string', ['length'=>200] ) )

			// 图标
			->putColumn( 'image', $this->type('text', ['json'=>true] ) )

			// 图标类型
			->putColumn( 'icontype', $this->type('string', ['length'=>200] ) )

			// 应用简介
			->putColumn( 'intro', $this->type('string', ['length'=>200] ) )

			// 应用介绍
			->putColumn( 'detail', $this->type('text') )

			// 帮助文档 ( Markdown )
			->putColumn( 'document', $this->type('text') )

			// 应用作者
			->putColumn( 'author', $this->type('string', ['length'=>200, "index"=>true] ) )

			// 官网地址
			->putColumn( 'homepage', $this->type('string', ['length'=>200] ) )

			// 导航菜单
			->putColumn( 'menu', $this->type('text', ['json'=>true] ) )

			// 依赖关系结构
			->putColumn( 'dependencies', $this->type('text', ['json'=>true] ) )

			// 页面注入链接结构
			->putColumn( 'injections', $this->type('text', ['json'=>true] ) )

			// 应用需要获得的API权限
			->putColumn( 'api', $this->type('text', ['json'=>true] ) )

			// 应用提供的API列表
			->putColumn( 'register_api', $this->type('text', ['json'=>true] ) )

			// 应用安装卸载管理API
			->putColumn( 'setup', $this->type('text', ['json'=>true] ) )

			// 应用状态
			// uninstalled 未安装, installing 正在安装, installed 已安装,  downloading 正在下载,  reparing 修复中,  uninstalling 卸载中,  error 系统出错
			->putColumn( 'status', $this->type('string', ['length'=>200, 'default'=>'uninstalled', 'index'=>true] ) )

			// 应用任务执行进度
			->putColumn( 'progress', $this->type('integer', ['default'=>0] ) )

			// 应用许可证
			->putColumn( 'license', $this->type('text') )

			// 应用类型
			->putColumn( 'type', $this->type('string', ['length'=>50] ) )

			// 应用网关类型 
			->putColumn( 'gateway', $this->type('string', ['length'=>50, 'default'=>'http'] ) )

			// 应用鉴权 api key ( appid )
			->putColumn( 'apikey', $this->type('string', ['length'=>128]) )

			// 应用鉴权 api secret
			->putColumn( 'secret', $this->type('string', ['length'=>128]) )

			// 协议类型 1.6.11 +
			->putColumn( 'license', $this->type('string', ['length'=>50] ) )

			// 仓库地址  1.6.11 +
			->putColumn( 'repository', $this->type('text', ['json'=>true] ) )

			// 关键词  1.6.11 +
			->putColumn( 'keywords', $this->type('text', ['json'=>true] ) )

			;
			
		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


	/**
	 * 打包下载应用
	 * @param  [type]  $app_id [description]
	 * @param  boolean $return [description]
	 * @return [type]          [description]
	 */
	function uiZipAndDownload( $app_id, $return=false ) {

		$app = $this->getByAppid( $app_id );
		$uipath = $app['path'] . "/ui";
		$name = "{$app['org']}-{$app['name']}-{$app['version']}-ui.zip";
		if ( !is_dir($uipath) ) {
			throw new Excp('应用尚未开放客户端源码', 404, ['app_id'=>$app_id, 'app'=>$app]);
		}

		$s = new \Xpmse\Secret;
		$keypair = $s->getFirstKeypair();
		$home = Utils::getHome();
		$uri = parse_url($home);

		$zipFile = new \PhpZip\ZipFile();

		function makeConf( & $content, $map, $vars ) {

			$patterns =[]; $replacements = [];

			foreach ( $map as $key => $val ) {
				$patterns[] = "/{$key}[ ]*:[\"\' ]+.+[\"\' ]+/";
				$replacements[] = "{$key}:\"{$vars[$val]}\"";

				$patterns[] = "/[\"\' ]+{$key}[\"\' ]+[ ]*:[\"\' ]+.+[\"\' ]+/";
				$replacements[] = "\"{$key}\":\"{$vars[$val]}\"";

				$patterns[] = "/{$key}[ ]*=[ ]*[\"\' ]+.+[\"\' ]+/";
				$replacements[] = "{$key}=\"{$vars[$val]}\"";

			}
			$content = preg_replace($patterns, $replacements, $content);
			return $content;
		}



		// 添加根目录 & WEB 目录
		foreach( glob("$uipath/*") as $filename ) {

			// 添加配置
			if ( in_array(basename($filename), ["config.js","config-online.js"])) {

				$content = file_get_contents($filename);
				$content = makeConf( $content, 
					[
						"server" => "home",
						"domain" => "host",
						"appid" => "appid",
						"secret" => "secret"
					],
					[
						"appid"=>$keypair['appid'], 
						"secret"=>$keypair['secret'],
						"home"=>$home, 
						"host"=>$uri['host']
					]
				);

				$zipFile->addFromString(basename($filename), $content); // add an entry from the string
				// echo "$filename size " . filesize($filename) . "\n";
				continue;
			}

			// 添加文件
			if ( is_file($filename) ) {
				$zipFile->addFile( $filename, basename($filename) );
				// echo "$filename size " . filesize($filename) . "\n";
			}

			// 添加目录
			if ( is_dir($filename) && !in_array(basename($filename), ["node_modules", "wxapp", "app", ".tmp", ".cache"]) ) {
				$zipFile->addDirRecursive( $filename, basename($filename) );
				// echo "$filename size " . filesize($filename) . "\n";
			}
		}


		// 添加小程序
		foreach( glob("$uipath/wxapp/*") as $filename ) {

			// 添加配置
			if ( in_array(basename($filename), ["config.js"])) {

				$content = file_get_contents($filename);
				$content = makeConf( $content, 
					[
						"host" => "host",
						"https" => "host", 
						"wss" => "wss",
						"appid" => "appid",
						"secret" => "secret"
					],
					[
						"appid"=>"<您的小程序appid>", 
						"secret"=>"{$keypair['appid']}|{$keypair['secret']}",
						"wss"=>"$home/ws-server", 
						"host"=>$uri['host']
					]
				);

				$zipFile->addFromString("wxapp/".basename($filename), $content); // add an entry from the string
				// echo "$filename size [conf]" . filesize($filename) . "\n";
				continue;
			}

			// 添加文件
			if ( is_file($filename) ) {
				$zipFile->addFile( $filename, "wxapp/".basename($filename) );
				// echo "$filename size [file]" . filesize($filename) . "\n";
			}

			// 添加目录
			if ( is_dir($filename) && !in_array(basename($filename), ["node_modules"]) ) {
				$zipFile->addDirRecursive( $filename, "wxapp/".basename($filename) );
				// echo "$filename size [path]" . filesize($filename) . "\n";
			}
		}


		// 添加安卓应用
		foreach( glob("$uipath/app/android/*") as $filename ) {

			// 添加配置
			if ( in_array(basename($filename), ["xpm.js"])) {

				$content = file_get_contents($filename);
				$content = makeConf( $content, 
					[
						"host" => "host",
						"https" => "host", 
						"wss" => "wss",
						"appid" => "appid",
						"secret" => "secret"
					],
					[
						"appid"=>"< appid >", 
						"secret"=>"{$keypair['appid']}|{$keypair['secret']}",
						"wss"=>"$home/ws-server", 
						"host"=>$uri['host']
					]
				);

				// echo $content;
				$zipFile->addFromString("app/android/".basename($filename), $content); // add an entry from the string
				// echo "$filename size [conf]" . filesize($filename) . "\n";
				continue;
			}

			// 添加文件
			if ( is_file($filename) ) {
				$zipFile->addFile( $filename, "app/android/".basename($filename) );
				// echo "$filename size [file]" . filesize($filename) . "\n";
			}

			// 添加目录
			if ( is_dir($filename) && !in_array(basename($filename), ["node_modules"]) ) {
				$zipFile->addDirRecursive( $filename, "app/android/".basename($filename) );
				// echo "$filename size [path]" . filesize($filename) . "\n";
			}
		}

		// 添加ios应用
		foreach( glob("$uipath/app/ios/*") as $filename ) {

			// 添加配置
			if ( in_array(basename($filename), ["xpm.js"])) {

				$content = file_get_contents($filename);
				$content = makeConf( $content, 
					[
						"host" => "host",
						"https" => "host", 
						"wss" => "wss",
						"appid" => "appid",
						"secret" => "secret"
					],
					[
						"appid"=>"< appid >", 
						"secret"=>"{$keypair['appid']}|{$keypair['secret']}",
						"wss"=>"$home/ws-server", 
						"host"=>$uri['host']
					]
				);

				// echo $content;
				$zipFile->addFromString("app/ios/".basename($filename), $content); // add an entry from the string
				// echo "$filename size [conf]" . filesize($filename) . "\n";
				continue;
			}

			// 添加文件
			if ( is_file($filename) ) {
				$zipFile->addFile( $filename, "app/ios/".basename($filename) );
				// echo "$filename size [file]" . filesize($filename) . "\n";
			}

			// 添加目录
			if ( is_dir($filename) && !in_array(basename($filename), ["node_modules"]) ) {
				$zipFile->addDirRecursive( $filename, "app/ios/".basename($filename) );
				// echo "$filename size [path]" . filesize($filename) . "\n";
			}
		}


		// ZipFile 
		if ( $return === true ) {
			return $zipFile->outputAsString();
		}

		// 下载
		$zipFile->outputAsAttachment($name); 

	}

	

	/**
	 * 安装/卸载/升级/修复 应用
	 * @param  [type] $app_id   [description]
	 * @param  [type] $app_name [description]
	 * @return [type]           [description]
	 */
	function setup( $app_id, $method='Install' ) {

		$method = ucwords(strtolower($method));
		$app = $this->getByAppid($app_id);
		if ( empty($app) ) {
			throw new Excp('应用不存在或未安装', 404, ['app_id'=>$app_id, 'method'=>$method, 'app'=>$app]);
		}

		return call_user_func_array([$this, "setup{$method}"], [$app]);
	}


	/**
	 * 安装应用
	 * @param  [type] $app App记录
	 * @return [type]      [description]
	 */
	function setupInstall( $app, $force=false ) {

		$status = $app['status'];
		if( $status != "uninstalled" )  {
			throw new Excp("应用状态异常({$status})", 404, ['status'=>$status, 'app'=>$app]);
		}

		// 检查依赖关系
		if ( $force !== true ) {
			if ( isset($app['dependencies']) && is_array($app['dependencies']) && count($app['dependencies']) > 0 ) {
				foreach ( $app['dependencies'] as $app_name=>$app_version ) {

					$this->appDependencies( $app_name, $app_version);

					// if ( !$this->appDependencies( $app_name, $app_version) ) {
					// 	throw new Excp("依赖应用: {$app_name} 版本:{$app_version}，未安装或版本不符合要求。", 404, ['dependencies'=>['name'=>$app_name, 'version'=>$app_version], 'app'=>$app]);
					// }
				}
			}
		}

		// 标记为安装中
		$resp = $this->update($app['_id'], ['status'=>'installing']);
		if ( $resp === false ) {
			throw new Excp('锁定应用状态失败(无法更新应用状态)', 500, [ 'updateResp'=>$resp]);
		}
		$this->cleanCache();


		// 运行安装脚本
		if ( isset($app['setup']) && isset($app['setup']['install']) && is_array($app['setup']['install']) ) {
			try {
				$this->runSetupScript( $app, 'install' );
			} catch( Excp $e ){
				$this->update($app['_id'], ['status'=>'uninstalled']);
				throw $e;
			} catch ( Exception $e  ){
				throw $e;
				$this->update($app['_id'], ['status'=>'uninstalled']);
			}

		}

		// 标记为已安装
		$resp = $this->update($app['_id'], ['status'=>'installed']);
		if ( $resp === false ) {
			throw new Excp('标记为已安装失败', 500, [ 'updateResp'=>$resp]);
		}

		$this->cleanCache();
		return $resp;
	}


	/**
	 * 卸载应用
	 * @param  [type] $app App记录
	 * @return [type]      [description]
	 */
	function setupUninstall( $app, $force=false ) {

		$status = $app['status'];
		if( $status != "installed" )  {
			throw new Excp('应用状态异常', 404, ['status'=>$status, 'app'=>$app]);
		}


		if ( $force !== true ) {
			// 检查依赖关系
			$depApps = $this->query()
						 ->where("dependencies", 'like', '%'.$app['slug'].'%')
						 ->where("status", '=', "installed")
						 ->select("_id","cname", "slug", "setup", 'version',"dependencies", "status")
						 ->get()
						 ->toArray();

						;
			if ( count($depApps) >= 1 ) {
				$apps_arr = [];
				foreach ($depApps as $da) {
					$apps_arr[] = "【{$da['cname']} {$da['version']}】";
				}

				$apps_str = implode("、", $apps_arr);
				throw new Excp("{$apps_str}依赖此应用, 请先卸载这些应用。", 403, [ 'apps'=>$depApps]);
			}
		}


		// 标记为卸载中
		$resp = $this->update($app['_id'], ['status'=>'uninstalling']);
		if ( $resp === false ) {
			throw new Excp('标记为卸载中失败', 500, [ 'updateResp'=>$resp]);
		}
		$this->cleanCache();

		// 运行安装脚本
		if ( isset($app['setup']) && isset($app['setup']['uninstall']) && is_array($app['setup']['uninstall']) ) {
			try {
				$this->runSetupScript( $app, 'uninstall' );
			} catch( Excp $e ){
				$this->update($app['_id'], ['status'=>'installed']);
				throw $e;
			}
		}

		// 标记为未安装
		$resp = $this->update($app['_id'], ['status'=>'uninstalled']);
		if ( $resp === false ) {
			throw new Excp('标记为卸载失败', 500, [ 'updateResp'=>$resp]);
		}

		$this->cleanCache();
		return $resp;
	}


	/**
	 * 修复应用
	 * @param  [type] $app App记录
	 * @return [type]      [description]
	 */
	function setupRepair( $app ) {

		$status = $app['status'];
		if( $status != "installed" )  {
			throw new Excp('应用状态异常', 404, ['status'=>$status, 'app'=>$app]);
		}

		// 标记为修复中
		$resp = $this->update($app['_id'], ['status'=>'reparing']);
		if ( $resp === false ) {
			throw new Excp('标记为安装中失败', 500, [ 'updateResp'=>$resp]);
		}
		$this->cleanCache();


		// 运行安装脚本
		if ( isset($app['setup']) && isset($app['setup']['repair']) && is_array($app['setup']['repair']) ) {
			try {
				$repair_action = $this->runSetupScript( $app, 'repair' );
			} catch( Excp $e ){
				$this->update($app['_id'], ['status'=>'installed']);
				throw $e;
			}
		}


		// 标记为已安装
		$resp = $this->update($app['_id'], ['status'=>'installed']);
		if ( $resp === false ) {
			throw new Excp('标记为未安装失败', 500, [ 'updateResp'=>$resp]);
		}

		$resp['action'] = $repair_action;
		$this->cleanCache();
		return $resp;
	}




	/**
	 * 运行安装/升级/修复/卸载脚本 (即将废弃 === )
	 * @param  [type] $app         [description]
	 * @param  string $script_name [description]
	 * @return [type]              [description]
	 */
	function runSetupScript( $app, $script_name='install' ) {
		
		if ( !isset( $app['setup']) || !isset($app['setup'][$script_name]) || 
			 !is_array($app['setup'][$script_name]) || 
			 !is_string($app['setup'][$script_name]['controller']) || 
			 !is_string($app['setup'][$script_name]['action']) ) {
			return false;
		}
		
		$u = new \Xpmse\User;
		$user = $u->getLoginInfo();

		// 校验用户身份
		if ( $user['isAdmin'] == false ) {
			throw new Excp('运行安装脚本失败:没有权限', 500, ['app'=>$app,'script_name'=>$script_name,'resp_text'=>""]);
		}

		// 选定当前应用网关
		$app['type'] = empty( $app['type']) ? 'local' :  $app['type'];
		$gateway = ( $app['type'] == 'remote') ? 'http' : 'local';
		$gateway = "\\Mina\\Gateway\\{$gateway}";
		$gw = new $gateway([
			"seroot" => Utils::seroot(),
			"user" => $user
		]);


		$gw->load("{$app['org']}/{$app['name']}", function( $a ) use ($app) {
			return $app;
		})
		->init()
		->fetch($app['setup'][$script_name]['controller'], $app['setup'][$script_name]['action']);

	
		$resp = $gw->get();

		if ( $resp['code'] != 0 ) {
			throw new Excp('运行安装脚本失败:' . $resp['message'],  500, ['script_name'=>$script_name, 'resp'=> $resp]);
		}


		$content = $resp['content'];
		$json_data = json_decode( $content, true );
		if ( $json_data == false || $json_data == null  ) {
			throw new Excp('运行安装脚本失败: JSON解析错误',  500, ['content'=>$content]);
			return;
		}

		if ( is_array($json_data) && $json_data['code'] != 0  &&  $json_data['message'] ) {
			throw new Excp('运行安装脚本失败:' . $json_data['message'],  500, ['json_data'=>$json_data]);
		}

		return $resp['content'];

	}


	/**
	 * 检查版本依赖关系
	 * @param  [type] $app_name [description]
	 * @return 成功返回状态，或应用不存在返回 null
	 */
	function appDependencies( $app_name, $app_version ) {
		$data = $this->getLine("WHERE slug=?  LIMIT 1", ["_id","status", "cname", "slug","appid","name","version"], [$app_name]);

		if ( empty($data) ) {
			throw new Excp("依赖应用: 【{$app_name}】不存在。", 404, 
				['slug'=>$app_name, 'version'=>$app_version, 'app'=>$data]
			);
			return false;
		}

		if ( $data['status'] != "installed") {
			throw new Excp("依赖应用: 【{$data['cname']}】该应用尚未安装。", 405, 
				['slug'=>$data['slug'], 'version'=>$app_version, 'app'=>$data]);
			return false;
		}


		if ( preg_match("/^([0-9\.]+)~$/", $app_version, $match) ) { // 大于等于 Version
			if ( $this->version_compare($data['version'], '>=' , $match[1]) == false ) {
				throw new Excp("依赖应用: 【{$data['cname']} {$match[1]}以上】当前应用版本 {$data['version']}不符合要求。", 405, 
				['slug'=>$data['slug'], 'version'=>$data['version'], 'app'=>$data]);
				return false;
			}

		} elseif ( preg_match("/^([0-9\.]+)$/", $app_version, $match) ) { // 等于 Version

			if ( $this->version_compare($data['version'], '==' , $match[1]) == false ) {
				throw new Excp("依赖应用:【{$data['cname']} {$match[1]}】当前应用版本 {$data['version']}不符合要求。", 405, 
				['slug'=>$data['slug'], 'version'=>$data['version'], 'app'=>$data]);
				return false;
			}

		} elseif ( $app_version === 'master' || $app_version === '*' )  {
			return true;
		}

		if ( $data['status'] != "installed") {
			throw new Excp("依赖应用:【{$data['cname']} {$app_version}】 该应用版本不符合要求。", 405, 
				['slug'=>$data['slug'], 'version'=>$data['version'], 'app'=>$data]);
			return false;
		}

		return false;

	}


	/**
	 * 转化版本
	 * @return [type] [description]
	 */
	function version_compare( $v1, $exp, $v2 ) {

		$v1arr = explode(".", $v1);
		$v2arr = explode(".", $v2);
		
		if ( $exp == '==' || $exp == '>=' || $exp == '<=' ) {
			if ( $v1 == $v2 ) {
				return true;
			}else if ( $exp == '==' ) {
				return false;
			}
        }
        
        $val1 = intval(implode($v1arr));
        $val2 = intval(implode($v2arr));

        try {
            eval('$resp='."$val1 $exp $val2;");
        }catch( \Excpition $e ){
            return false;
        }
       
        return $resp;
	}


	/**
	 * 校验应用是否已被安装
	 * @param  String $app_id 应用ID
	 * @return boolean | false  已安装返回应用信息, 未安装返回False
	 */
	function installed( $app_id ) {
		
		$list = $this->getInstalled();

		if ( isset($list['map'][$app_id]) ) {
			return $list['map'][$app_id];
		}

		return false;
	}



	/**
	 * 读取应用注入信息 (废弃: 通过应用API方式注入)
	 * @param String $app_id 应用ID
	 */
	function getInjections( $app_id ) {

		$cache = "injections:{$app_id}";
		$mem = new Mem(false,'APPS:');
		$result = $mem->getJSON($cache);
		if ( is_array($result) ) {
			return $result;
		}


		$result = [];
		$injection_map = [];
		$appinfo_map =[];
		$list = $this->getInstalled();


		if ( !isset( $list['map'][$app_id]) || !is_array( $list['map'][$app_id]) ) {
			return [];
		}

		$app_name =  $list['map'][$app_id]['name'];
		foreach ($list['data'] as $app ) {
			$injections = $app['injections'];
			$name = $app['name'];
			$injection_map[$name] = $injections;
			$appinfo_map[$name] = $app;
		}

		foreach ($injection_map as $name=>$app_injections ) {
			if ( isset($app_injections[$app_name]) ) {
				foreach ($app_injections[$app_name] as $action => $list ) {
					foreach ($list as $idx => $info ) {
						$app_id = $appinfo_map[$name]['appid'];
						$app_injections[$app_name][$action][$idx]['app'] = [
							'id' => $appinfo_map[$name]['appid'],
							'name' => $appinfo_map[$name]['name'],
							'cname' => $appinfo_map[$name]['cname'],
							'icon' => $appinfo_map[$name]['icon'],
							'icontype' => $appinfo_map[$name]['icontype'],
							'version'=> $appinfo_map[$name]['version']
						];

						$app_injections[$app_name][$action][$idx]['from'] = $name;
						
						$app_injections[$app_name][$action][$idx]['url_static'] = R('core-app','route','staticurl', [
							'app_id'=>$app_id,
							'app_name'=>$name,
							'path' => $info['path']
						]);

						$app_injections[$app_name][$action][$idx]['url_portal'] = R('core-app','route','portal', [
							'app_id'=>$app_id,
							'app_name'=>$name,
							'app_c' => $info['controller'],
							'app_a' => $info['action'],
						]);

						$app_injections[$app_name][$action][$idx]['url_noframe'] = R('core-app','route','noframe', [
							'app_id'=>$app_id,
							'app_name'=>$name,
							'app_c' => $info['controller'],
							'app_a' => $info['action'],
						]);

						$app_injections[$app_name][$action][$idx]['url'] = R('core-app','route','index', [
							'app_name'=>$name,
							'app_id'=>$app_id,
							'app_c' => $info['controller'],
							'app_a' => $info['action'],
						]);
					}
				}
				$result = array_merge($result, $app_injections[$app_name] );
			}
		}

		$mem->setJSON( $cache, $result );
		return $result;
	}



	/**
	 * 读取已安装应用的API 清单
	 * @return [type] [description]
	 */
	function getRegisterAPI( $name, $org = DEFAULT_ORG ){
		$apps = $this->getInstalled();
		// wprint_r($apps['map']);

		$app =(isset( $apps['map'][$name]) )? $apps['map'][$name] : null;

		if ( $app === null ) return null;
		
		
		if (isset($app['register_api']) && is_array($app['register_api']) ) {
			return ['api'=>$app['register_api'], 'appid'=>$app['appid'], 'name'=>$app['name'],'path'=>$app['path']];
		}
		return ['api'=>[],'appid'=>null, 'name'=>null];
	}



	/**
	 * 读取未安装应用清单
	 */
	function getUninstalled( $nocache = false ) {

		return $this->getApps( $nocache, "where status='uninstalled'", 'uninstalled' );
	}


	function getInstalled( $nocache = false ) {
		return $this->getApps( $nocache, "where status='installed'", 'installed' );
	}


	/**
	 * 读取应用清单
	 * @return [type] [description]
	 */
	function getApps( $nocache = false, $where='', $cachename='all' ) {
		
		$mem = new Mem(false,'APPS:');
		$cache = "list:$cachename";
		if ( $nocache === true ){
			$json_text = false;
		} else {
			$json_text = $mem->get($cache);
		}

		if ( $json_text !== false ) {
			$json_data =  json_decode($json_text,true);
			if ( is_array($json_data) &&  is_array($json_data['data']) ) {
				return $json_data;
			}
		}

		try {
			$appList = $this->select("$where order by created_at,_id");			
		}catch( Exception $e ){
			$appList = [];
		}

		if ( is_array($appList) &&  is_array($appList['data']) ) {
			$ut = new Utils;
			foreach ($appList['data'] as $idx=>$app ) {


				$id = $app['appid'];
				$name = $app['name'];
				$slug = $app['slug'];
				$alias = $app['alias'];
				$menuList = $app['menu'];

				$menuArray = []; $orders = [];

				// 动态菜单
				if ( isset($menuList['_hook']) && !empty($menuList['_hook']) ) {

					$hook = $ut->parseLink( $menuList['_hook'] );
					if ( is_array($hook) ) {
						$query = array_merge([
	                           'app_id'=> $id,
	                           'app_name' => $name,
	                           'app_c'=>$hook["c"],
	                           'app_a'=>$hook["a"]
	                        ], $hook["q"]);
						$menuList['_hook'] = $query;
					}
				} // 动态菜单END

				foreach( $menuList as $key=>$menu ) {  // 菜单数据处理，兼容旧菜单格式

					if ( !is_array($menu) || $key === '_hook') {
						
						continue;
					}

					if ( isset($menu['slug'])) {  // 新菜单格式

						if ( isset($menu['link'])  ) {
							$link = Utils::parseLink( $menu['link'] );
							if( is_array($link) ) {
								$menu["controller"] = $link['c'];
								$menu["action"] = $link['a'];
								$menu["query"] = $link['q'];
								$menu["link"] = "";
							}

							if ( empty($menu['link']) ) {
								$menu["linktype"] = empty($menu["linktype"]) ? 'i' : $menu["linktype"];
								$menu['link'] = AR( $alias, $menu["linktype"],
											$menu["controller"], $menu["action"],    
											$menu["query"]);
							}

						}

						if ( $menu['icontype'] == 'img' ) {

							foreach ($menu['icon'] as $k=>$url ) {
								$menu['icon'][$k] = ASR( $alias, $url );
							}

						}

						if( !empty( $menu['submenu']) && is_array($menu['submenu']) ) {  // 解析子菜单链接
							
							// Utils::out("\n====\n");

							foreach ($menu['submenu'] as $smidx => $sm ) {
								if ( empty($menu['submenu'][$smidx]['link']) ) {
									continue;
								}

								$sm_link = Utils::parseLink( $menu['submenu'][$smidx]['link'] );
								if( is_array($sm_link) ) {
									$menu['submenu'][$smidx]["controller"] = $sm_link['c'];
									$menu['submenu'][$smidx]["action"] = $sm_link['a'];
									$menu['submenu'][$smidx]["query"] = $sm_link['q'];
									$menu['submenu'][$smidx]["link"] = "";
								}


								if ( empty($menu['submenu'][$smidx]['link']) ) {
									$menu['submenu'][$smidx]["linktype"] = empty($menu['submenu'][$smidx]["linktype"]) ? 'i' : $menu['submenu'][$smidx]["linktype"];
									$menu['submenu'][$smidx]['link'] = AR( $alias, $menu['submenu'][$smidx]["linktype"],
										$menu['submenu'][$smidx]["controller"], $menu['submenu'][$smidx]["action"],    
										$menu['submenu'][$smidx]["query"]
									);

									// Utils::out( $menu['submenu'][$idx]['link'], "\n alias=", $alias );
								}
							}


						}

						array_push($menuArray, $menu);
						if  ($menu['index'] === true ) {
							$app['index'] = $menu;
						}
						continue;

					} // END  新菜单格式

					$menu["query"] = (isset($menu["query"]))? $menu["query"] : [];
					$menu['link'] =  (isset($menu["link"]))? $menu["link"] : "";
					$menu['order'] = (isset($menu["order"]))? intval($menu["order"]) : 100;
					$menu['index'] = (isset($menu["index"]))? $menu['index'] : false;
					if ( isset($menu['link'])  ) {
						$link = $ut->parseLink( $menu['link'] );
						if( is_array($link) ) {
							$menu["controller"] = $link['c'];
							$menu["action"] = $link['a'];
							$menu["query"] = $link['q'];
							$menu["link"] = "";

						}

						if ( empty($menu['link']) ) {
							$menu["style"] = empty($menu['style']) ?  'i' : $menu['style'];
							$menu['link'] = AR( $alias, $menu["style"],
										$menu["controller"], $menu["action"],    
										$menu["query"]);
						}

					}

					array_push($orders, $menu['order']);
					array_push($menuArray, $menu);

					if  ($menu['index'] === true ) {
						$app['index'] = $menu;
					}

				}
				if ( !isset($app['index']) ) {
					$app['index'] = isset($menuArray[0]) ? $menuArray[0] : null;
				}
				$appList['data'][$idx]['index'] = $app['index'];

				@array_multisort($orders, SORT_ASC, $menuArray);
				$slug = empty($app['slug']) ? $name : trim($app['slug']);


				$appList['data'][$idx]['menu'] = $menuArray;
				$appList['data'][$idx]['menumap'] = $menuList;
				$appList['map'][$id] = $app;
				$appList['map'][$name] = $app;
				$appList['map'][$slug] = $app;

				
			}

			$json_text = json_encode($appList);
			$mem->set($cache, $json_text);

			return $appList;
		}

		return ['data'=>[], 'map'=>[], 'total'=>0];
	}

	
	function cleanCache() {
		$mem = new Mem(false,'APPS:');
		$cache = ['list:installed','list:uninstalled','list:all', 'slug:'];
		$list = $this->getInstalled( true );
		foreach ($list['data'] as $app ) {
			$id = $app['appid'];
			$cache[] = "injections::{$id}";
		}

		$result = M('Menu')->cleanCache();
		return  ( $result &&  $mem->clean($cache) );
	}


	/**
	 * 格式化用户数据
	 */
	function format( & $appData ) {
		return $appData;
	}


	/**
	 * 生成一个唯一的ID
	 * @return [type] [description]
	 */
	function genAppid() {
		$str = uniqid(mt_rand(),1);
		return md5( $str );
	}



	/**
	 * 查找某路径应用清单
	 * @param  [type] $path [description]
	 * @return [type]       [description]
	 */
	function find( $path = _XPMAPP_ROOT, $config =[] ) {
			
		$apps = [];
		$hd = opendir($path);
		if ( !$hd ) return;

		$config['maxdepth'] = empty($config['maxdepth'])? 2 : intval($config['maxdepth']);
		$config['curdepth'] = empty($config['curdepth'])? 1 : intval($config['curdepth']);
		if ( $config['curdepth'] == 1) {
			$config['root'] = $path;
		}


		while (($dir = readdir($hd)) !== false)  {

            // 忽略目录结构
			if (!preg_match('/^([0-9a-zA-Z_]+)$/', $dir, $match) ) {
				continue;
            }
            
            // 忽略 node_modules
            if ( strpos($dir, "node_modules") !== false ) {
                continue;
            }

			if ( file_exists("$path/$dir/package.json") ) {


				try {
					$data = $this->parsePackage("$path/$dir/package.json");
				} catch( Excp $e ) {
                    $e->log();
					// $error = $e->getExtra();
					// $resp['error'][] = array_merge(['message'=>'解析应用失败'],$error );
					continue;
                }
                

				$p = $data['path']; 
				$orgstr = str_replace( $config['root'] , '', $path);
				$orgarr = explode('/', $orgstr);
				$org  = empty($orgarr[1]) ? DEFAULT_ORG : $orgarr[1];
				// $alias = str_replace("{$config['root']}/", '', "$path/$dir");

				array_push($apps, [
					"org" => $org,
					"path"=> "$path/$dir",
					"package" => "$path/$dir/package.json"
					// "alias" => $alias
				]);
				
            }
            
            if ( $config['curdepth'] <= $config['maxdepth'] )  {
				$config['curdepth'] = $cnt = count(explode('/', "$alias/$dir" ));
				$subdirApps =$this->find( "$path/$dir", $config );
				if ( !empty($subdirApps) ){
					$apps = array_merge( $apps, $subdirApps);
				}
			}
		}


		return $apps;
	}





	/**
	 * 扫描应用列表
	 * @param  string $type [description]
	 * @return [type]       [description]
	 */
	function scan(  $unlock = false, $approot=_XPMAPP_ROOT ) {
		
		$unlock = true;

		$resp = ['update'=>[], 'create'=>[], 'delete'=>[], 'error'=>[] ];
		$mem = new Mem(false,'APPS:');
		$lock = "scan:lock";

		if ( $mem->get($lock) !== false && $unlock !== true ) { // 锁定状态，稍后再试
			throw new Excp('更新过于频繁，请一分钟后重试', 403 );
			return $resp;
		}
		

		$apps = $this->select();
		$oldData = (isset($apps['data']) && is_array($apps['data'])) ? $apps['data'] : [];

		$oldMap = []; $newMap = [];

		foreach($oldData as $a ) {
			$path = $a['path'];
			$oldMap[$path] = $a['_id'];
		}

		$config = [];
		if (file_exists("$approot/app.json")) {
			$json_text = file_get_contents("$approot/app.json");
			$json_data = json_decode($json_text, true);
			if ( json_last_error() ) {
				$e = new Excp('解析应用失败', 500, ['json_error'=>json_last_error_msg(), 'app.json'=>"$approot/app.json"]);
				$resp['error'][] = array_merge(['message'=>'解析应用配置失败, 使用默认配置'], $e->getExtra() );
			}

			$config = $json_data;
		}

		$apps = $this->find( $approot, $config );


		foreach ($apps  as $app ) {
			try {
				$data = $this->parsePackage($app['package']);
			} catch( Excp $e ) {
				$error = $e->getExtra();
				$resp['error'][] = array_merge(['message'=>'解析应用失败'],$error );
				continue;
			}
			
			$p = $data['path'];
			$data = array_merge( $config, $data, $app );

	

			if( isset($oldMap[$p]) ) { // 更新
				// $data['_id'] = $oldMap[$p];
				$newData = $this->update( $oldMap[$p],  $data);
				if ( $newData === false ) {
					$resp['error'][] = ['message'=>'更新应用失败','package'=>$app['package']];
					continue;
				}
				$resp['update'][] = $oldMap[$p];
				$newMap[$p] = $oldMap[$p];
			} else { // 新增
				$newData = $this->create( $data );
				if ( $newData === false ) {
					$resp['error'][] = ['message'=>'新增应用失败','package'=>$app['package']];
					continue;
				}
				$resp['create'][] = $newData['_id'];
				$newMap[$p] = $newData['_id'];
			}
		}

		
		$diffMap = array_diff($oldMap, $newMap);
		foreach ($diffMap as $p => $_id) {  // 删除
			$this->delete($_id);
			$resp['delete'][]  = $_id;
		}

		// 扫描保护锁
		if ( $unlock !== true ) {
			$mem->set($lock, 'locked', 60); // 锁定60秒，防止重复执行
		}

		$this->cleanCache();
		return $resp;
	}


	/**
	 * 清空所有应用
	 * @return [type] [description]
	 */
	function clean() {
		$apps = $this->select();
		if ( is_array($apps['data']) ) {
			foreach ($apps['data'] as $a ) {
				$this->delete( $a['_id']);
			}
		}
	}


	/**
	 * 根据应用ID 读取应用数据
	 * @param  [type] $app_id [description]
	 * @return [type]         [description]
	 */
	function getByAppid( $app_id ) {
		return $this->getLine("WHERE appid='$app_id' LIMIT 1");
	}


	/**
	 * 根据应用 SLUG 读取应用数据
	 */
	function getBySlug( $app_slug, $fields=['appid', 'name', 'org','cname','image','icon','status', 'setup', 'proxy', 'proxy_on', 'proxy_script', 'path','version','icontype', 'gateway', 'apikey', 'secret', 'type'], $nocache=false ) {
		// echo "===== getBySlug";
		$mem = new Mem(false,'APPS:');
		$cache = "slug:{$app_slug}";
		if ( $nocache === true ){
			$rs = false;
		} else {
			$rs = $mem->getJSON($cache);
		}

		if ( $rs !== false ) {
			// echo "END =====";
			return $rs;
		}


		$rows = $this->query()->where('slug', '=', $app_slug)->select($fields)->limit(1)->get()->toArray();
		if ( empty($rows) ) {
			return [];
		}
		$rs = current( $rows );
		$mem->setJSON($cache, $rs);
		return $rs;
	}



	/**
	 * 重载数据入库逻辑 （自动创建 appid)
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function create( $data ) {
		if( !isset($data['appid']) && !isset($data['_id']) ) {
			$data['appid'] = $this->genAppid();
		}

		if( !isset($data['slug']) ) {
			$data['slug'] ='DB::RAW(CONCAT(org, "/", name))';
		}

		return parent::create( $data );
	}

	/**
	 * 重载更新数据逻辑 （自动创建 appid)
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update( $_id,  $data ) {

		if( isset($data['name']) || isset($data['org']) ) {
			$data['slug'] ='DB::RAW(CONCAT(org, "/", name))';
		}

		return parent::update( $_id, $data );
	}

	/**
	 * 重载更新数据逻辑 （自动创建 appid)
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function updateBy(  $uni_key, $data  ) {

		if( isset($data['name']) || isset($data['org']) ) {
			$data['slug'] ='DB::RAW(CONCAT(org, "/", name))';
		}

		return parent::updateBy( $uni_key, $data );
	}



	/**
	 * 解析 package.json 并返回数组
	 * @param  [type] $package_file [description]
	 * @return [type]               [description]
	 */
	function parsePackage( $package_file ) {

		$json_string = file_get_contents($package_file);
		$data = json_decode( $json_string, true);

		if ( json_last_error() ) {
			throw new Excp('解析应用失败', 500, ['json_error'=>json_last_error_msg(), 'package'=>$package_file]);
		}

		foreach ($this->package_json_required as $key => $call ) {
			$call['args'] = is_array($call['args']) ? $call['args'] : []; 
			$call['args']  =  array_merge( [$data[$key]], $call['args'] );
			$ret = call_user_func_array($call['method'], $call['args'] );
			if ( $ret === false ) {
				throw new Excp('解析应用失败 ($key 格式不正确)', 500, ['key'=>$key, 'call'=>$call]);
			}
		}
		

		// 检查应用类型 ( local 本地应用, remote 远程应用 )
		$idxfile = dirname($package_file) . '/index.php';
		$ctrldir = dirname($package_file) . '/controller';
		if ( file_exists($idxfile) && is_dir($ctrldir) ) {
			$data['type'] = 'local';
			$data['gateway'] = 'local';
			$data['apikey'] = $this->secret['appid'];
			$data['secret'] = $this->secret['secret'];
			$data['alias'] = "{$data['org']}/{$data['name']}";
			$data['lang'] = empty($data['lang']) ? 'php' : trim($data['lang']);
			$data['proxy'] = empty($data['proxy']) ? DEFAULT_PROXY : trim($data['proxy']);
			$data['proxy'] = $data['proxy'] . '/' . $data['alias'];
			if ( $data['lang'] == 'php') {
				$data['proxy_script'] = $data['proxy'] . '/index.php';
			}

		} else {

			$data['type'] = 'remote';
			$data['gateway'] = 'http';
			if ( !Utils::isURL($data['proxy']) || !Utils::isURL($data['proxy_script']) ) {
				throw new Excp('非法 (Proxy 地址)', 500, ['proxy'=>$data['proxy'], 'proxy_script'=>$data['proxy_script']]);
			}
		}

		// 处理文档
		$pathinfo = pathinfo( $package_file );
		$app_root = $pathinfo['dirname'];
		$data['path'] = $app_root;
		$data['detail'] = wrapper_file_get_contents(  $data['detail'], $app_root );
		$data['document'] = wrapper_file_get_contents(  $data['document'], $app_root );

		if ( empty($data['detail']) ) {
			$data['detail'] = $data['cname'];
		}

		if ( empty($data['document']) ) {
			$data['document'] = $data['cname'];
		}

		if ( empty($data['cname']) || empty($data['name']) ||  !is_array($data['setup'])) {
			throw new Excp('解析应用失败', 500, ['data'=>$data, 'json_error'=>'package json 格式不正确', 'package'=>$package_file]);
		}

		return $data;
	}


}
<?php
namespace Xpmse\Model;

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
use \Mina\Cache\Redis as Cache;
use Alchemy\Zippy\Zippy;


/**
 * 应用商店模型
 */
class Appstore extends Model {

	private $api = '';
	
	/**
	 * 应用数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
		$this->table('appstore');
		$this->api = _XPMSE_API_APPSTORE;

		$this->cache = new Cache( [
			"prefix" => '_appstore:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);
	}


	/**
	 * 清除缓存
	 * @return [type] [description]
	 */
	function clearCache() {

		$caches = ["detail:", "search:", "status:"];
		foreach ($caches as $cache ) {
			$this->cache->delete( $cache );	
		}
	}

	/**
	 * 清除数据下载缓存
	 * @param  string $appid
	 * @return
	 */
	function clearDownloadCache( $appid = "" ) {
		$cahce = "download:" . $appid;
		$this->cache->delete( $cache );
	}



	/**
	 * 核销付款码，如果成功下载应用
	 * @param  [type]  $appid   [description]
	 * @param  [type]  $paycode [description]
	 * @param  boolean $nocache [description]
	 * @return [type]           [description]
	 */
	function checkPaycodeAndDownload( $appid, $paycode ) {
		$data = $this->checkPaycode( $appid, $paycode ); 
		$app = $this->download( $appid ); // 发送下载应用请求
		return $app;
	}


	/**
	 * 核销付款码
	 */
	function checkPaycode( $appid, $paycode ) {

		$query  = ['cmd'=>'checkpaycode', 'id'=>$appid, 'paycode'=>$paycode];
		$rs = $this->post('/checkpaycode', $query);
		if ( isset($rs['app_host_id']) ) {  // 核销成功,清空缓存
			$this->clearCache();
			return $rs;
		} 

		$message = empty($rs['message']) ? '付款码核销失败' : $rs['message'];
		$extra = empty($rs['extra']) ? $rs : $rs['extra'];
		throw new Excp($message, 500, $rs );
	}


	/**
	 * 下载应用 
	 *
	 *  本地                         文件服务器
	 *
	 *  发送下载请求            →     接收请求，验证用户身份  @/model/Appstore.php
	 *  回报结果               ←     发送回执   @/model/Appstore.php
	 *  
	 *                                 ↓ （ 如果身份合法，推送文件
	 *                                 
	 *  校验身份接收文件        ←      推送文件 （ 每次推送1M，多次推送  @pipeline.class.php
	 *                            
	 *  
	 * @param  [type] $appi [description]
	 * @return [type]       [description]
	 */
	function download( $appid, $force=false ) {

		// {status:"downloading", message:"下载中，请稍候", progress:0, target:"/path/xx.s.cp", task:{...} }
		$cache = 'download:' .  $appid;
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data)  && $force === false) {
			if ( $data['status']  == 'downloading' || $data['status'] == 'done' ) {
				return $data['task'];
			}
		}

		$query  = ['cmd'=>'download', 'id'=>$appid];
		$task = $this->post('/download', $query);
		$this->cache->setJSON( $cache, ["status"=>"downloading", "target"=>null, "message"=>"下载中, 请稍后", "progress"=>0, "task"=>$task] );
		$this->clearCache();
		return $task;
	}


	/**
	 * 校验是否正在下载应用
	 * @param string $appid 
	 * @return boolean        [description]
	 */
	function isDownloading( $appid ) {

		$cache = 'download:' .  $appid;
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data) && $data['status']  == 'downloading' ) {
			return true;
		}
		return false;
	}


	/**
	 * 校验应用是否下载完成
	 * @param  [type]  $appid [description]
	 * @return boolean        [description]
	 */
	function isDownloadComplete( $appid, & $status ) {

		$cache = 'download:' .  $appid;
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data) && 
			 ( $data['status']  == 'done'  || $data['status'] == 'failure' )
			) {

			$status = $data['status'];
			return true;
		}
		return false;
	}


	/**
	 * 取消下载
	 * @param  [type] $appid [description]
	 * @return [type]        [description]
	 */
	function cancelDownload( $appid ) {
		$query  = ['cmd'=>'cancelDownload']; // 取消下载
		$task = $this->post('/cancelDownload', $query);
		return $task;
	}



	/**
	 * 读取/设定下载任务进度
	 */
	function downloadStatus( $appid, $data = null ) {

		$cache = 'download:' .  $appid;
		$cache_data = $this->cache->getJSON( $cache ) ;
		
		if ( $cache_data === false ) {
			$cache_data = [];
		}

		if ( is_array($data) ) {
			$cache_data =  array_merge($cache_data, $data );
			$this->cache->setJSON( $cache, $cache_data);
		}

		return $cache_data;
	}



	function isInstallAble( $appid ) {
		return false;
	}


	function isInstalling( $appid ) {
		return false;
	}


	/**
	 * 检查安装程序是否存在
	 * @param  [type]  $appid [description]
	 * @return boolean        [description]
	 */
	function isExist( $appid ) {
		
		// // 应用相关信息
		$app = $this->getApp( $appid, true );
		$root = _XPMAPP_ROOT;
		$org = $app['org'];
		$name = $app['name'];
		$target = "{$root}/{$org}/{$name}";
		
		if ( is_dir($target) ) {
			return true;
		}

		return false;
	}



	/**
	 * 安装应用程序
	 * @param  [type]  $appid [description]
	 * @param  boolean $force [description]
	 * @return [type]         [description]
	 */
	function install( $appid, $force=false )  {


		// {status:"downloading", message:"下载中，请稍候", progress:0, target:"/path/xx.s.cp", task:{...} }
		$cache = 'install:' .  $appid;
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data)  && $force === false) {
			if ( $data['status']  === 'installing' || $data['status'] == 'done' ) {
				return $data['task'];
			}
		}

		
		// 读取 APP 信息
		$app = $this->getApp( $appid, true );
		$downloadStatus = $this->downloadStatus( $appid );
		if ( $downloadStatus['status'] != 'done' ) {
			throw new Excp("应用程序尚未下载", 400, ['downloadStatus'=>$downloadStatus]);
		}

		$source = $downloadStatus['target'];
		if ( !is_readable($source) ) {
			// 清空缓存 ( 重新下载 )
			$this->clearDownloadCache( $appid );
			$this->clearCache();
			throw new Excp("无法访问已下载源码", 400, ['downloadStatus'=>$downloadStatus]);	
		}


		// 应用相关信息
		$root = _XPMAPP_ROOT;
		$org = $app['org'];
		$name = $app['name'];
		$target = "{$root}/{$org}/{$name}";
		$action = 'install';
		$app_local = M('App');

		$app_info = $app_local->getBySlug("{$org}/{$name}");
		if ( !empty($app_info) && !in_array($app_info['status'], ['uninstalled', 'installed']) ) {
			throw new Excp("应用程序已被锁定({$app_info['status']})", 400, ['app_info'=>$app_info]);	
		}

		$app_local_id = $app_info['appid'];
		$app_local_status = $app_info['status'];
		if ( !empty($app_local_id) ) {
			$app_local->update($app_local_id, ['status'=>'installing']);
		}
		
		// 删除之前应用目录
		if ( is_dir($target) ) {
			Utils::rmdir($target);
		}

		// 解压缩源码
		$this->extract($source, $target);

		// 重新扫描应用
		$app_local->scan();
		if ( !empty($app_local_id) ) {
			$app_local->update($app_local_id, ['status'=>$app_local_status]);
		}

		// 新应用信息
		$app_info = $app_local->getBySlug("{$org}/{$name}");
		$app_local_id = $app_info['appid'];

		// 应用未安装
		if ( $app_info['status'] == 'uninstalled') {
			$resp = $app_local->setup($app_local_id, "install");
		} else if  ( $app_info['status'] == 'installed') {
			$resp = $app_local->setup($app_local_id, "upgrade" );
		}


		// 清空商店缓存
		$this->clearCache();
		$this->clearDownloadCache($appid);

		return $app_info;
	}



	/**
	 * 解压缩源码
	 * @param  [type] $source [description]
	 * @param  [type] $target [description]
	 * @return [type]         [description]
	 */
	function extract( $source, $target ) {
		
		$zippy = Zippy::load();
		$archive = $zippy->open( $source, 'tar.gz' );
		
		// 创建应用目录
		if ( !is_dir($target) ) {
			if ( mkdir($target, 0777, true) === false ) {
				throw new Excp("无法创建应用目录", 400, ['target'=>$target, 'source'=>$source]);	
			}
		}

		$archive->extract($target);
		return true;
	}




	/**
	 * 计算应用状态
	 * @param  [type] $app   [description]
	 * @param  array  $host  [description]
	 * @param  array  $local [description]
	 * @return [type]        [description]
	 */
	function status( & $app, $host =[], $local = [] ) {

		// 尚未购买 unpay
		if ( empty($host) ) {
			$app['status'] = 'unpay';
			return $app;
		}

		// Local appid
		if ( !empty($local) ) {
			$app['local_appid'] = $local["appid"];
		}

		$app['params'] = empty($host['params']) ? [] : $host['params'];

		// 应用下载中 downloading  ( 锁定操作
		$appid = $app['appid'];
		if ( $this->isDownloading($appid) ) {
			$app['status'] = 'downloading';
			return $app;
		}

		// 应用已下载 
		if ( $this->isDownloadComplete($appid, $status) ) {
			if ($status == 'done') {
				$app['status'] = 'downloaded';
				return $app;
			}
		}

		// 应用被封禁 close
		if ( $host['status'] != 'active' ) {
			$app['status'] = 'close';
			return $app;
		}

		// 应用已过期 expired
		if ( !empty($app['params']['expired_at']) ) {
			$expired = strtotime($app['params']['expired_at']);
			$now = time();
			if ( $now > $expired ) {
				$app['status'] = 'expired';
				return $app;
			}
		}
		
		// 已购买，未安装 uninstalled / complete
		if ( !empty($host) ) {
			$app['status'] = 'uninstalled';

			// license
			if ( $app['type'] == 'license' ) {
				$app['status'] = 'complete';
			} 
		}

		// 已购买，已安装  installed/new_version
		if ( !empty($local) ) {
			$app['status'] = 'installed';
			// 已购买，已安装, 新版本未安装  new_version
			if ( $local['version'] != $app['version'] ) {
				$app['status'] = 'new_version';
			}
		}


		return $app;
	}


	/**
	 * 根据应用ID查询
	 * @param  [type]  $appid   [description]
	 * @param  boolean $nocache [description]
	 * @return [type]           [description]
	 */
	function getApp( $appid, $nocache=false ) {
		
		$cache = 'detail:' .  $appid;
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data)  && $nocache === false) {
			return $data;
		}

		$query  = ['cmd'=>'getapp', 'id'=>$appid];
		$data = $this->post('/getapp', $query);
		$this->cache->setJSON( $cache, $data, 7200 ); // 缓存7200秒
		return $data;
	}


	/**
	 * 根据应用字符串查询
	 * @param  string $slug 
	 * @return 
	 */
	function getAppid( $slug, $nocache=false) {

		$cache = 'slugmap:' .  $slug;
		$appid = $this->cache->get( $cache );
		if ( $appid !== false  && $nocache === false) {
			return $appid;
		}
		$query  = ['slug'=>$slug];
		$data = $this->post('/getid', $query);
		$appid = $data['appid'];
		if ( !empty($appid) ) {
			$this->cache->set( $cache, $appid ); 
		}
		return $appid;
	}



	/**
	 * 查询商品
	 * @return
	 */
	function search( $query, $nocache=false ) {

		$cache = 'search:' .  md5( implode('', $query) );
		$data = $this->cache->getJSON( $cache );
		if ( is_array($data)  && $nocache === false) {
			return $data;
		}
		$query  = array_merge( $query, ['cmd'=>'search']);
		$data = $this->post('/search', $query);
		$this->cache->setJSON( $cache, $data, 7200 ); // 缓存7200秒
		return $data;
	}


	/**
	 * IsOnline
	 * @return 
	 */
	function isOnline($nocache=false ) {

		$cache = 'status:isonline';
		$data = $this->cache->getJSON( $cache );

		if ( $data !== false && $nocache === false) {
			if ( $data['code'] === 0 ) {
				return true;
			}
			return false;
		}

		$data = $this->post('/ping', ['cmd'=>'ping'] );
		$this->cache->setJSON( $cache, $data, 7200 ); // 缓存7200秒
		if ( $data['code'] === 0 ) {
			return true;
		}
		return false;
	}


	/**
	 * 调用API
	 * @param  [type] $api    [description]
	 * @param  [type] $params [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function post( $api, $params=[], $data=[] ) {
		$api = $this->api . $api;
		unset($params['n'],$params['c'],$params['a']);

		$query = getSecretQuery( $params);
		if ( empty($data['home']) ) {
			$data['home'] = Utils::getHome();
		}
		return Utils::Request('POST', $api, ['query'=>$query, 'data'=>$data]);
	}


}
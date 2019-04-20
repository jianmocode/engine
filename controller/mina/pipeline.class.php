<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Mina\Cache\Redis as Cache;
use \Mina\Pages\Api\Article;

use \Exception as Exception;

/**
 * 数据管道 
 * 用于 MINA Server 与实例之间通信
 */
class minaPipelineController extends minaBaseController {

	public $fonts = [];

	function __construct() {
		parent::__construct();
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		$this->cache = new Cache( [
			"prefix" => '_pipeline:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);
	}


	function up() {
		Utils::cliOnly();
		
		$sc = new \Xpmse\Secret();
		$keypair = $sc->getFirstKeypair();
		$appid = $keypair['appid'];

		$params = ['cmd'=>'download'];
		$signs = $sc->signature($params, $keypair['secret'], $keypair['appid']);
		$query = array_merge($params, $signs);

		Utils::upload("/data/pages.tar.gz", "https://dev.xpmsns.com/_a/mina/pipeline/transfer", ['query'=>$query, 'data'=>['name'=>'/xpmsns/pages-1.1.0.tar.gz']], null,
			function( $resp, $opt ) {
				echo "====== complete === \n";
				print_r($resp);
				echo "\n";
			}
		);
	}


	function test() {

		Utils::cliOnly();

		$url = "https://dev.xpmsns.com/_a/mina/pipeline/transfer";

		$sc = new \Xpmse\Secret();
		$keypair = $sc->getFirstKeypair();
		$appid = $keypair['appid'];

		$params = ['cmd'=>'search'];
		$signs = $sc->signature($params, $keypair['secret'], $keypair['appid']);
		$query = array_merge($params, $signs);

		if ( $_GET['r'] == "1" ) {

			$querystring = http_build_query( $query , null, '&' , PHP_QUERY_RFC3986);
			$url = $url . '?' . $querystring;
			echo "<a href='$url'target='_blank'>$url</a>";
			return;
		}


		$resp = Utils::Request( 'GET', $url , [
			'query' => $query
		]);

		Utils::out($resp);

	}


	/**
	 * 从服务器端，推送数据
	 * @return
	 */
	function transfer() {

		$query = $_GET;
		$data = $_POST;

		if ( empty($query['appid']) || empty($query['nonce']) ||  empty($query['timestamp']) ||  empty($query['signature']) ){
			throw new Excp("非法请求", 402, ['query'=>$query]);
		}

		// 根据 appid 选取 secret
		$sc = new \Xpmse\Secret();
		$secret = $sc->getSecret( $query['appid'] );
		if ( empty($secret) ) {
			throw new Excp("非法Appid", 402, ['query'=>$query]);
		}

		// 校验请求签名
		$params = $query;
		unset( $params['appid'],   $params['signature'], $params['n'], $params['c'], $params['a'] );// 删除非签名数据

		if ( $params['cmd'] == 'download' ) {
			$eftTime = 7200; // 有效期 2 小时
		} else {
			$eftTime = 10; // 其他情况 5秒
		}

		$isEffect = $sc->signatureIsEffect( $query['signature'], $params, $secret, $eftTime );

		if ( $isEffect === -1 ) {
			throw new Excp("请求已过期", 402, ['query'=>$query, 'params'=>$params]);
		}

		if ( $isEffect === false ) {
			throw new Excp("非法请求签名", 402, ['query'=>$query, 'params'=>$params]);
		}

		// === 放行，接收数据 & 处理指令 === 
		switch ($params['cmd']) {

			case 'ping':
				$this->ping();
				break;
			
			case 'download':
				$this->download();
				break;

			case 'updateLicense':
				$this->updateLicense();
				break;

			case 'search':
				$this->search();
				break;

			case 'getapp':
				$this->getapp();
				break;
				
			default:
				throw new Excp("非法指令", 402, ['query'=>$query, 'params'=>$params]);
				break;
		}
	}


	/**
	 * 更新 XpmSE License
	 * @return [type] [description]
	 */
	private function updateLicense() {

		$cache = 'license';
		$this->cache->set("{$cache}:code", $_POST['license'], $_POST['expires_at']) ;
		$this->cache->set("{$cache}:text", $_POST['license_text'], $_POST['expires_at']);
		Utils::out($_POST);
	}


	/**
	 * 接收应用数据，根据应用数据处理数据信息校验
	 * @return 
	 */
	private function getapp() {

		$app = json_decode($_POST['response'], true);
		$fullname = $app['fullname'];
		$installed = M('App')->getInstalled( true );
		$appstore = M('Appstore');
		$host = empty($app['host']) ? [] : $app['host'];
		$local = empty($installed['map'][$fullname]) ? [] : $installed['map'][$fullname];
		$appstore->status( $app, $host, $local );
		// unset($app['host']);
		Utils::out($app);
	}



	/**
	 * 接收应用数据，根据应用数据处理数据信息校验
	 * @return 
	 */
	private function search() {

		$resp = json_decode($_POST['response'], true);
		$appstore = M('Appstore');
		// Utils::out( $response );

		// 根据传回数据计算应用状态
		$installed = M('App')->getInstalled( true );
		foreach ($resp['data'] as & $app ) {
			$fullname = $app['fullname'];
			$host = empty($resp['host'][$fullname]) ? [] : $resp['host'][$fullname];
			$local = empty($installed['map'][$fullname]) ? [] : $installed['map'][$fullname];
			$appstore->status( $app, $host, $local );
		}

		unset($resp['host']);
		Utils::out($resp);
	}



	/**
	 * 接收数据, 并保存到数据临时目录 ( 支持分段续传
	 * @return [type] [description]
	 */
	private function download() {
			
		// 应用ID
		$id = $_GET['id'];
		$appstore = M('Appstore');

		$tf = current($_FILES);
		$name = empty($_POST['name']) ? $tf['name'] : rawurldecode($_POST['name']);
		$target = sys_get_temp_dir() . '/.xpmse/' . $name;

		if ( !empty($_POST['error']) ) {
			$appstore->downloadStatus( $id, ["progress"=>100, "message"=>$_POST['error'], "target"=>"", 'status'=>'failure']);
			throw new Excp($_POST['error'], 500, ['target'=>$target, 'post'=>$_POST, 'files'=>$_FILES]);
		}
		


		if ( $_POST['chunk'] == 0 ) {
			mkdir(dirname($target), 0777, true);
			@unlink( $target );
		}

		$blob = file_get_contents( $tf['tmp_name'] );
		$resp = file_put_contents( $target, $blob, FILE_APPEND | LOCK_EX);
		if ( $resp === false ) {
			throw new Excp("文件保存失败", 500, ['resp'=>intval($resp), 'target'=>$target, 'post'=>$_POST, 'files'=>$_FILES]);
		}

		// 更新下载信息状态
		$app = $appstore->getApp($id);
		$size = filesize( $target );
		$total = ( intval($app["size"]) == 0 ) ? 1 : intval($app["size"]) ;
		$progress = [
			"progress" => intval($size/$total * 100),
			"message"  =>  Utils::readableFilesize($size) . " / " . Utils::readableFilesize( $total ),
			"target" => $target,
			"status" => ( $size == $total || ($_POST['chunk'] +1) == $_POST['chunks'] ) ?  'done' : 'downloading'
		];
		$appstore->downloadStatus( $id, $progress );

		// 清空缓存
		if ( $progress['status'] === 'done' ) {
			$appstore->clearCache();
		}

		// 返回结果
		echo json_encode( ['code'=>0, "id"=>$id, 'target'=>$target, 'size'=>$resp, 'progress'=>$progress, 'chunk'=>$_POST['chunk'], 'chunks'=>$_POST['chunks']] );
	}



	/**
	 * 数据管道心跳检查 (用于校验域名是否合法)
	 * @return
	 */
	private function ping() {
		echo json_encode(['code'=>0, 'message'=>'服务正常']);
	}

}
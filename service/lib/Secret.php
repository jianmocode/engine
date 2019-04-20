<?php
namespace Xpmse;

/**
 * 
 * 密钥模型
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Secret
 *
 * USEAGE: 
 *
 */


use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;


class Secret extends Model {

	/**
	 * 密钥数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'], $driver );
		$this->table('secret');

		// $driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		// parent::__construct($param , $driver );
		// $this->table('secret');
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {

			// 外部应用 ID 
			$this->putColumn( 'appid', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

			// 外部应用 Secret
			->putColumn( 'appsecret', $this->type('string', [ "null"=>false,'length'=>128] ) )

			// 所属员工 ID
			->putColumn( 'userid', $this->type('string', ['length'=>128] ) )

			// 当前令牌
			->putColumn( 'token', $this->type('string', ['length'=>128] ) )

			// 令牌有效期
			->putColumn( 'expires_at', $this->type('timestamp') )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


	/**
	 * 生成API KEY & SECK
	 * @return [type] [description]
	 */
	function genKeyPair() {

		$appid = time() . rand(10000,99999); 
		$appsecret = hash('md4',  uniqid(mt_rand(),1) );
		return ['appid'=>$appid,'appsecret'=>$appsecret];
	}


	/**
	 * 读取 Secret 秘钥
	 * @param  [type] $appid [description]
	 * @return [type]        [description]
	 */
	function getSecret( $appid ) {

		$secret = $this->getVar('appsecret', "WHERE appid=? LIMIT 1", [$appid]);
		return $secret;
	}


	function getFirstKeypair() {
		$data = $this->getSecrets()['data'];
		return current( $data );
	}


	/**
	 * 读取所有私钥
	 */
	function getSecrets() {
		
		$data = $this->query()
					 ->select("appid", "appsecret as secret")
					 ->get()
					 ->toArray();
		$map = [];
		foreach ($data as $rs ) {
			$map[$rs['appid']] = $rs['secret'];
		}

		return ['map'=>$map, 'data'=>$data];
	}



	/**
	 * 校验 appid & appsecret 是否有效
	 * @param  [type]  $appid     [description]
	 * @param  [type]  $appsecret [description]
	 * @return boolean            [description]
	 */
	function isSecretEffect( $appid, $appsecret ) {

		$cache = "secret:$appid";
		$mem = new Mem(false,'API:');
		$result = $mem->get($cache);
		if ( $result !== false ) {
			return ($appsecret == $result);
		}

		$resp = $this->select("WHERE appid=? AND appsecret=? LIMIT 1", [], [$appid, $appsecret]);

		if ( !isset($resp['total']) || !isset($resp['data']) ) {
			throw new Excp('返回结果异常', '500', ['appid'=>$appid] );
		}

		
		if ( $resp['total'] == 0 ) {
			return false;
		}

		$rs = end( $resp['data'] );
		$mem->set($cache, $rs['appsecret'], 86400);
		return ($rs['appsecret'] == $appsecret);
	}



	/**
	 * 生成签名
	 * @param  [type] $param  [description]
	 * @param  [type] $appid  [description]
	 * @param  [type] $secret [description]
	 * @return [type]         [description]
	 */
	function signature( $params, $secret=null, $appid=null) {

		if ( empty($secret) ) {
			
			if ( empty($appid) ) {
				$rs = $this->getFirstKeypair();
				$appid = $rs['appid'];
				$secret =  $rs['secret'];
			} else {
				$secret = $this->getSecret( $appid );
			}
		}


		$params['nonce'] = Utils::genStr(6);
		$params['timestamp'] = time();
		foreach($params as &$datum) if($datum===null) $datum='';

		ksort($params);
		$string = http_build_query( $params , null, null , PHP_QUERY_RFC3986). $secret;
		$signature = sha1($string);
		
		// echo "<signature>\n";
		// echo "ID:$appid & SC:$secret\n";
		// echo "<origin>$string</origin>\n";
		// echo "<params>\n";
		// var_dump($params);
		// echo "</params>\n";
		// echo "</signature>\n";

		return [
			"appid" => $appid,
			"nonce" => $params['nonce'],
			"timestamp" => $params['timestamp'],
			"signature" => $signature
		];

	}


	function signatureIsEffect( $signature, $params, $secret, $expire_seconds=30 ) {
			
		$params['timestamp'] = intval($params['timestamp']);
		$expire_seconds= intval($expire_seconds);

		if (( time() - $params['timestamp']) > $expire_seconds ) {
			return -1;
		}

		foreach($params as &$datum) if($datum===null) $datum='';
		ksort($params);
		$string = http_build_query( $params , null, null , PHP_QUERY_RFC3986). $secret;
		$s = sha1($string);

		// echo "<check>\n";
		// echo "<origin>{$string}</origin>\n";
		// echo "$signature = $s\n";
		// echo "SC:$secret\n";
		// echo "<params>\n";
		// var_dump($params);
		// echo "</params>\n";
		// echo "</check>\n";


		if ( $signature === $s ) {
			return true;
		}
		return false;
	}



	/**
	 * 生成Token
	 * @return [type] [description]
	 */
	function genToken( $appid, $appsecret ) {
		
		$cache = "token:$appid";

		$expires_at = date('Y-m-d H:i:s',time() + 604800);
		$appid = $appid;
		$token = 'TK_'. md5(uniqid(mt_rand(),1));

		$resp = $this->select("WHERE appid=? AND appsecret=? LIMIT 1", [], [$appid, $appsecret]);

		if ( !isset($resp['total']) || !isset($resp['data']) ) {
			throw new Excp('返回结果异常', '500', ['appid'=>$appid] );
		}

		if ( $resp['total'] == 0 ) {
			return new Err('404', '应用不存在或appsecret不正确', ['appid'=>$appid] );
		}


		$_id = $resp['data'][0]['_id'];
		$api =  $this->update($_id, ['token'=>$token, 'expires_at'=>$expires_at]);
		
		if ( $api !== false ) { // 更新缓存信息
			$mem = new Mem(false,'API:');
			$end = strtotime($expires_at);
			$now = time();
			$exp = intval($end)-intval($now);
			$mem->set($cache, $token, $exp );
		}

		return $api;
	}


	/**
	 * 校验Token 是否有效
	 * @param  [type]  $appid [description]
	 * @param  [type]  $token [description]
	 * @return boolean        [description]
	 */
	function isTokenEffect( $appid, $token ) {

		$cache = "token:$appid";
		$mem = new Mem(false,'API:');
		$result = $mem->get($cache);
		if ( $result !== false ) {
			return ($token == $result) ? true : false;
		}

		$now = date('Y-m-d H:i:s');
		$resp = $this->select("WHERE appid=? AND token=? LIMIT 1", [], [$appid, $token]);

		if ( $appid == "" ) {
			return false;
		}

		if ( !isset($resp['total']) || !isset($resp['data']) ) {
			throw new Excp('返回结果异常', '500', ['appid'=>$appid] );
		}

		if ( $resp['total'] == 1) {

			$end = strtotime($resp['data'][0]['expires_at']);
			$now = time();
			$exp = intval($end)-intval($now);

			if ( $exp <= 0 ) {
				return false;
			}

			$mem->set($cache, $resp['data'][0]['token'], $exp);
			return true;
		}

		return false;
	}

	function cleanCache() {
		$appid ="2";
		$cacheList = ["init:need","token:$appid"];
	}




	/**
	 * 初始化API (废弃)
	 * @return  
	 */
	function apiInit( $conf = null ) {
		return $this;
	}

	/**
	 * 是否需要初始化部门信息 (废弃)
	 * @return [type] [description]
	 */
	function apiNeedInit() {
		return false;
	}
	
}


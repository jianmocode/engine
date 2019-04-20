<?php

namespace Xpmse;

require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/utils-lib/Validatecode.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;


/**
 * XpmSE应用API
 */
class App {

	private $option = [];

	/**
	 * 构造
	 * @param array $option
	 *              $option['host']    XpmSE Server 路径
	 *              $option['app']     XpmSE 应用名称 mina/pages
	 *              $option['api']     XpmSE API 名称
	 *
	 * 				// {host}/_api/{app}/{api}
	 *              $option["url"]    按API地址 "https://tdm.domain.com/_api/mina/pages/article"
	 *              
	 * 				$option['appid']   XpmSE  appid
	 *              $option['secret']  XpmSE  secret
	 *
	 * 				$option['method']  调用方法，默认 NULL
	 */
	function __construct( $option ) {
		$this->option = $option;
	}

	public function __call($method, $args) {

		if ( function_exists($this->$method) ) {
			return $this->$method(...$args);
		} else {
			return $this->call( $method, ...$args );
		}

	}


	private function call( $method, $query=[], $data=[], $files=[] ) {

		$opt = $this->option;
		$url = !empty($opt['url']) ?  $opt['url'] : "{$opt['host']}/_api/{$opt['app']}/{$opt['api']}";
		$url = "$url/{$method}";

		$request_method = $opt['method'];
		if ( empty($request_method) && empty($data) && empty($files) ) {
			$request_method = "GET";
		} else if ( empty($request_method) && (!empty($data) || !empty($files) ) ) {
			$request_method = "POST";
		}

		if ( !empty($opt['appid']) ) {

			$sc = new \Xpmse\Secret;
			$params = $query;
			if ( is_array($data) ) {
				$params = array_merge($query, $data);
			}

			$sign = $sc->signature( $params, $opt['secret'],$opt['appid']);
			$query = array_merge($query, $sign);
		}

		if ( !empty($files)) {
			$data['__files'] = $files;
		}

		$debug = !array_key_exists('debug', $opt) ? false: $opt['debug'];

		$resp = Utils::Request($request_method, $url, [
			"debug" => $debug,
			"query" => $query, 
			"data"  => $data,
			"datatype" => "json",
		]);

		return $resp;
	}


}
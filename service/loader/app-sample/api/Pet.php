<?php
namespace {{org}}\{{name}}\Api;

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;
use \Xpmse\Option;
use \Xpmse\Wechat;


/**
 * Pet API接口
 */
class Pet extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {
		parent::__construct();
	}



	protected function get( $query, $data ) {

		// 校验 _secret ( 必须为 https 请求 )
		$this->authSecret($query['_secret']);

	}


	protected function search( $query, $data ) {
		
		// 校验请求签名
		$this->auth($query);
	}



	/**
	 * 文件上传接口
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @param  [type] $files [description]
	 * @return [type]        [description]
	 */
	protected function upload( $query, $data, $files ) {

		$this->authSecret($query['_secret']);
		$fname = $files['image']['tmp_name'];
		$host = Utils::getHome(empty(App::$APP_HOME_LOCATION) ? Utils::getLocation() : App::$APP_HOME_LOCATION);
		$media = new \Xpmse\Media(["host" => $host]);
		$ext = $media->getExt($fname);
		if ( !in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
			$ext = 'jpg';
		}
		$rs = $media->uploadImage($fname, $ext);
		return $rs;
	}

}
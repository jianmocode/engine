<?php 
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );




use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;


class coresystemsettingapiController extends privateController{


	/**
	 * 各种服务配置测试
	 * @return [type] [description]
	 */
	function tryit() {

		$se = (isset($_GET['se']) )? trim($_GET['se']) : 'redis';


		$method = null;


		if ( method_exists($this, "check_{$se}")) {
			$method = "Check_{$se}";
		}



		if ($method == null ) {
			echo json_encode(['code'=>500, 'message'=>'非法请求', 'extra'=>$_POST]);
			exit;	
		}



		$resp = $this->$method( $_POST );
		
		if ( $resp !== true ) {
			echo json_encode($resp);
			exit;
		}

		echo json_encode(['code'=>1, 'message'=>'success']);
	}


	/**
	 * 各种服务配置临时存盘
	 * @return [type] [description]
	 */
	function saveit() {

		$se = (isset($_GET['se']) )? trim($_GET['se']) : 'redis';

		$id = (isset($_GET['id']) )? trim($_GET['id']) : '';

		$method = null; $checkMethod = null;
		
		// 验证以下类是否存在

		if ( method_exists($this, "Save_{$se}")) {
			$method = "Save_{$se}";
		}


		if ( method_exists($this, "Check_{$se}")) {
			$checkMethod = "Check_{$se}";
		}

		if ($method == null || $checkMethod == null ) {
			echo json_encode(['code'=>500, 'message'=>'非法请求', 'extra'=>$_POST]);
			exit;	
		}

		// 校验服务器配置
		$warning = null;

		$resp = $this->$checkMethod( $_POST );
		if ( $resp !== true ) {
			if  ( isset($resp['code']) && $resp['code'] == 201 ) {
				$warning = $resp;
			} else {

				echo json_encode($resp);
				exit;
			}
		}


		// 验证无错误的时候进行
		if($warning == null ) {

			// // 返回正确的代号
			$resp = $this->$method($_POST,$id);

			//返回为什么错误
			echo json_encode($resp);

		} else {
			echo json_encode($warning);
		}


	}

	/**
	 * 系统标签验证
	 * @return [type] [description]
	 */
	function Check_general( $option ){
		
		$homepage = (isset($option['homepage']))? trim($option['homepage']) : null;
		$name = (isset($option['name']))? trim($option['name']) : null;
		$short = (isset($option['short']))? trim($option['short']) : null;
		$company = (isset($option['company']))? trim($option['company']) : null;
		$logo_path = (isset($option['logo_path']))? trim($option['logo_path']) : null;
		
		if ( empty($homepage) ) {
			return ['code'=>404, 'message'=>'未填写系统地址', 'extra'=>$option];
		}

		if ( empty($name) ) {
			return ['code'=>404, 'message'=>'未填写系统名称', 'extra'=>$option];
		}

		if ( empty($short) ) {
			return ['code'=>404, 'message'=>'未填写系统简称', 'extra'=>$option];
		}

		if ( empty($company) ) {
			return ['code'=>404, 'message'=>'未填写公司名称', 'extra'=>$option];
		}

		if ( empty($logo_path) ) {
			return ['code'=>404, 'message'=>'未上传系统图标', 'extra'=>$option];
		}

		return true;

	}


	/**
	 * 系统标签验证
	 * @return [type] [description]
	 */
	function Check_app(){




	}

	/**
	 * 钉钉验证
	 * @return [type] [description]
	 */

	function Check_dingtalk(){



	}






	/**
	 * 微信验证
	 * @return [type] [description]
	 */
	function Check_wechat(){



	}


	/**
	 * 通知验证
	 * @return [type] [description]
	 */
	function  Check_mobile(){




	}


	/**
	 * 日志验证
	 * @return [type] [description]
	 */
	function Check_log(){




	}



	/**
	 * redis验证
	 * @return [type] [description]
	 */
	function Check_redis( $option ){

		// 获取
		$ip = $host = (isset($option['host']))? trim($option['host']) : null;
		$port = (isset($option['port']))? $option['port'] : null;
		$passwd = (isset($option['password']))? $option['password'] : null;

		// 判定为空的时候

		if ( $host == null || $port == null ){
			return ['code'=>500, 'message'=>'非法请求 ( Host/Port不能为空 )', 'extra'=>$option];
		}

		// 域名解析（匹配是否为ip）
		if ( !preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip) ) {

			// 获取主机名
			$ip = @gethostbyname($host);
			if ( !preg_match('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)($|(?!\.$)\.)){4}$/', $ip ) ) {
				$option['ip'] = $ip;
				// 查看redis文件是否存在
				return ['code'=>500, 'message'=>'连接失败 ( '.$host.' 无法解析 )', 'extra'=>$option];
			}

		}

		// 连接校验 
		$option['ip'] = $ip;
		$redis = new Redis();
		try {
			$resp = $redis->connect( $ip, $port, 1.0, NULL, 500);
		} catch ( RedisException  $e ){
			$message = $e->getMessage();
			return ['code'=>500, 'message'=>'连接失败 ( '. $message . ' )'  , 'extra'=>$option];
		}



		if ( $resp === false ){  // 连接失败
			try {
				$err = $redis->getLastError();
			} catch ( RedisException  $e ){
				$message = $e->getMessage();
				return ['code'=>500, 'message'=>'连接失败 ( '. $message . ' )'  , 'extra'=>$option];
			}

			return ['code'=>500, 'message'=>'连接失败 ( '. $err  . ') ', 'extra'=>$option];
		}

		return true;

	}


  	/**
	 * storage验证
	 * @return [type] [description]
	 */
 	function Check_storage( $option ){

		$public_home = (isset($option['public_home']))? trim($option['public_home']) : null;

		$public_root = (isset($option['public_root']))? trim($option['public_root']) : null;
		$private_root = (isset($option['private_root']))? $option['private_root'] : null;
		$composer = (isset($option['composer']))? $option['composer'] : null;
		$engine = (isset($option['engine']))? $option['engine'] : null;

		if ( $public_home == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 访问地址不能为空 )', 'extra'=>$option];
		}

		if ( $public_root == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 公开目录不能为空 )', 'extra'=>$option];
		}

		if ( $private_root == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( 私密目录不能为空 )', 'extra'=>$option];
		}


		if ( !is_dir($public_root) ) {
			return ['code'=>404, 'message'=>'公开目录不存在 ( ' . $public_root . ' )', 'extra'=>$option];
		}

		if ( !is_dir($private_root) ) {
			return ['code'=>404, 'message'=>'私密目录不存在 ( ' . $private_root . ' )', 'extra'=>$option];
		}

		if ( !is_dir($composer) ) {
			return ['code'=>404, 'message'=>'Composer 目录不存在 ( ' . $composer . ' )', 'extra'=>$option];
		}


		if ( !is_writable($public_root) ) {
			return ['code'=>403, 'message'=>'公开目录不可写入 ( ' . $public_root . ' )', 'extra'=>$option];
		}

		if ( !is_writable($private_root) ) {
			return ['code'=>403, 'message'=>'私密目录不可写入 ( ' . $private_root . ' )', 'extra'=>$option];
		}



		if ( !is_writable($composer) ) {
			return ['code'=>403, 'message'=>'Composer 目录不可写入 ( ' . $composer . ' )', 'extra'=>$option];
		}


		// 校验访问地址
		$now = time();
		$code = $now . rand(10000,99999);
		$name = "storCheck_{$now}.txt";
		// 将文件写入变量
		@file_put_contents( "$public_root/$name", $code);
		$check_code = @file_get_contents("$public_home/$name", false,  stream_context_create(['method'=>"GET",'timeout'=>1]) );
		// 将文件 删除
		@unlink( "$public_root/$name" );
		if ($check_code != $code ) {
			return ['code'=>403, 'message'=>'访问地址不正确 ( ' . $public_home . ' )', 'extra'=>$option];
		}
		return true;
	}

	
	/**
  	 * supertable验证
  	 * @return [type] [description]
  	 */
  	function Check_supertable( $option ){
		
		$es_engine = (isset($option['es_engine']))? trim($option['es_engine']) : null;
		$es_host = (isset($option['es_host']))? trim($option['es_host']) : null;
		$es_port = (isset($option['es_port']))? trim($option['es_port']) : null;
		
		if ( $es_host == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( ElasticSearch 服务 Host 不能为空 )', 'extra'=>$option];
		}

		if ( $es_port == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( ElasticSearch 服务 Port 不能为空 )', 'extra'=>$option];
		}

		// 校验服务器
		$es_text = @file_get_contents("http://{$es_host}:{$es_port}", false,  stream_context_create(['method'=>"GET",'timeout'=>1]) );
		$es_data = json_decode($es_text, true);

		if ( !is_array($es_data) || !is_array($es_data['version'])   ) {
			$option['es_text'] = $es_text;
			$option['es_data'] = $es_data;
			return ['code'=>403, 'message'=>'无法访问 ElasticSearch 服务 ( http://' . $es_host . ':'. $es_port . ' )', 'extra'=>$option];
		}

		if ( $es_data['version']['number'] != '1.7.3' ){
			$option['es_text'] = $es_text;
			$option['es_data'] = $es_data;
			return ['code'=>403, 'message'=>'不支持的 ElasticSearch 版本 ( ' . $es_data['version']['number'] . ' )', 'extra'=>$option];
		}


		// 校验 MySQL 
		$st_engine = (isset($option['st_engine']))? trim($option['st_engine']) : null;
		$st_host = (isset($option['st_host']))? trim($option['st_host']) : null;
		$st_port = (isset($option['st_port']))? trim($option['st_port']) : null;
		$st_user = (isset($option['st_user']))? trim($option['st_user']) : null;
		$st_pass = (isset($option['st_pass']))? trim($option['st_pass']) : null;
		$st_dbname = (isset($option['st_dbname']))? trim($option['st_dbname']) : null;

		if ( $st_host == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL Host 不能为空 )', 'extra'=>$option];
		}

		if ( $st_port == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL Port 不能为空 )', 'extra'=>$option];
		}

		if ( $st_user == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL User 不能为空 )', 'extra'=>$option];
		}

		if ( $st_dbname == null ) {
			return ['code'=>500, 'message'=>'非法请求 ( MySQL 数据库名称不能为空 )', 'extra'=>$option];
		}

		// 校验 MySQL 连接
		$mysqli = @new mysqli($st_host, $st_user, $st_pass, $st_dbname);
		if (mysqli_connect_error()) {
		    $message = 'Connect Error ' . mysqli_connect_errno() . ': ' . mysqli_connect_error();
		    return ['code'=>500, 'message'=>'MySQL 服务连接失败 ( '. $message . ' )'  , 'extra'=>$option];
		}

		return true;
	}
	
	/**
	 * 系统标签存储
	 * @return [type] [description]
	 */
	function save_general( $option,$id ){

		$genresults = $this->save_data($option,$id);




		 return $genresults;
	
	}


	/**
	 * 系统标签存储
	 * @return [type] [description]
	 */
	function save_app(){






	}


	/**
	 * 应用存储
	 * @return [type] [description]
	 */
	function save_dingtalk(){



	}






	/**
	 * 微信存储
	 * @return [type] [description]
	 */
	function save_wechat(){



	}


	/**
	 * 通知存储
	 * @return [type] [description]
	 */
	function  save_mobile(){




	}


	/**
	 * 日志存储
	 * @return [type] [description]
	 */
	function save_log(){




	}



	/**
	 * redis存储
	 * @return [type] [description]
	 */
	function save_redis(){




	}
  	


  	/**
	 * storage存储
	 * @return [type] [description]
	 */
  	function save_storage(){



  	}



  	/**
  	 * supertable存储
  	 * @return [type] [description]
  	 */
  	function save_supertable(){





  	}

  	/**
  	 * 存入方法
  	 * @return [type] [description]
  	 */
  	function save_data($option,$id){

		$data = OM('Core::Option');

		// 查看表里面有没有数据
		
		$alldata = $data->select();


		// 如果没有数据创建数据
		if(empty($alldata['data'])){


			$resp = $data->create($option);

			// 创建异常返回错误	
			// 提交数据异常
			if ( $resp  === false ) { 


				$extra = [];
				$errors = (is_array($data->errors)) ? $data->errors : [];
				 foreach ($errors as $cname=>$error ) {
				 	$error = (is_array($error)) ? end($error) : [];
				 	$field = (isset($error['field'])) ? $error['field'] : 'error';
				 	$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
				 	$extra[] = ['_FIELD'=>$field,'message'=>$message];
				 }

				$e = new Excp(  $extra['0']['message'], '500', $extra);
				$error = $e->log();	
				return $e->error;
			
			}else{
				
				return  ['code'=>1, 'message'=>'success'];
			}

		}else{

			// 存在更新数据
			$resp = $data->update($id,$option);	


			// 如果数据返回异常返回错误


			if ( $resp  === false ) { 

				// return ['code'=>404, 'message'=>'未填写系统地址', 'extra'=>$option];
				$extra = [];
				$errors = (is_array($data->errors)) ? $data->errors : [];

				foreach ($errors as $cname=>$error ) {
					$error = (is_array($error)) ? end($error) : [];
					$field = (isset($error['field'])) ? $error['field'] : 'error';
					$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
					$extra[] = ['_FIELD'=>$field,'message'=>$message];
				}

				$e = new Excp( $extra['0']['message'], '500', $extra);
				$e->log();
				return  $e->error;
			}else{
				
				return  ['code'=>1, 'message'=>'success'];
			}
		}
	}

}



 ?>
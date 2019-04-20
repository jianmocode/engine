<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Mina\Pages\Api\Article;


/**
 * 本程序应该只允许后端用户访问
 */

class minaUploaderController extends minaBaseController {


	private $option = [];

	function __construct() {
		
		parent::__construct();

		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';

		if ( $_REQUEST['private'] == "1" ) {
			$this->option['private'] = true;
		} 

		if ( !empty($_REQUEST['fingerprint']) ) {
			$this->option['fingerprint'] = true;
		}

		if ( !empty($_REQUEST['host']) ) {
			$this->option['host'] = $_REQUEST['host'];
		}

		if ( empty($this->option['host']) ) {
			$this->option['host'] = Utils::getHome();
		}


		$u = M('User');
		$user = $u->getLoginInfo();

		// 校验请求 Token
		if ( $user === false && $_GET['a'] !== 'private_file' && $_GET['a'] !== 'briage' ) {

			$sc = M('Secret');
			$secret = $sc->getSecret($_GET['appid']);

			// Token 鉴权许可
			$params = [
				"action" => $_REQUEST['a'],
				"nonce" => $_REQUEST['nonce'],
				"timestamp" => $_REQUEST['timestamp']
			];

			$params = $_GET;
			unset($params['signature'], $params['appid']);


			$signature = $_REQUEST['signature'];
			$ret = $sc->signatureIsEffect($signature, $params, $secret );

			if ( $ret === -1 ) {
				throw new Excp("请求已过期", 403, ['params'=>$params, 'signature'=>$signature]);
			} else if ( $ret === false ) {
				throw new Excp("请求签名错误", 403, ['params'=>$params, 'signature'=>$signature]);
			}
		}
	}



	/**
	 * 302 转发
	 * @return [type] [description]
	 */
	function briage() {
		
		$cache = new \Mina\Cache\Redis([
			"prefix" => '_mediaStorageBriage:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);

		$time = $_GET['_time'];

		$host = $cache->get('host' . $time);
		$appid = $cache->get('appid'. $time);
		$secret = $cache->get('secret'. $time);

		$action = $_REQUEST['_action'];

		if ( $action == 'briage') {
			throw new Excp('错误的转向地址', 403);
		}

		$method = "GET";
		$requestType = "form";
		$data = $_POST;
		$query = $_GET;
		unset($query['_action'], $_REQUEST['_action'], $query['_time'], $_REQUEST['_time']);

		$_REQUEST['a'] = $query['a'] = $action;

		if (!empty($_POST) ) {
			$method = 'POST';
		}

		if ( !empty($_FILES)) {
			$requestType = 'media';
			$method = 'POST';
			foreach ($_FILES as $n => $f ) {
				$data['__files'][$n]['name'] = $n;
				$data['__files'][$n]['filename'] = $f['name'];
				$data['__files'][$n]['mimetype'] = $f['type'];
				$data['__files'][$n]['data'] = file_get_contents($f["tmp_name"]);
			}
		}

		// 计算签名
		$sc = new \Xpmse\Secret;
		$sign = $sc->signature($query, $secret, $appid);

		$resp = Utils::request($method, $host . "/_a", [
			// "debug" => true,
			"query" =>array_merge($query,$sign),
			"data" => $data,
			"type" => $requestType,
			"datatype" => "json"
		]);

		Utils::out($resp);

	}



	/**
	 * 显示私有文件
	 * @return [type] [description]
	 */
	function private_file() {


        // 校验管理员登
        $staff = \Xpmse\User::info();
        $user_id = $staff["user_id"];
        if ( empty($user_id) ) {
             throw new Excp("无文件查看权限", 403, []);
        }
		
		$sc = M('Secret');
		$secret = $sc->getSecret($_GET['appid']);

		// Token 鉴权许可
		$params = [
			"path" => $_REQUEST['path'],
			"nonce" => $_REQUEST['nonce'],
			"timestamp" => $_REQUEST['timestamp'],
			"mime" => $_REQUEST['mime']
		];

		$signature = $_REQUEST['signature'];
		$ret = $sc->signatureIsEffect($signature, $params, $secret );

		// if ( $ret === -1 ) {
		// 	throw new Excp("请求已过期", 403, ['params'=>$params, 'signature'=>$signature]);
		// } else if ( $ret === false ) {
		// 	throw new Excp("请求签名错误", 403, ['params'=>$params, 'signature'=>$signature]);
		// }


		// 临时解决方案 ( Word )
		$mime = $_REQUEST['mime'];
		if ( empty($mime) && strtolower(substr($_REQUEST['path'], -3)) == 'ocx' ) {
			$mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
		}
		
		$media = new \Xpmse\Media(['private'=>true]);
		$blob = $media->stor()->getBlob( $_REQUEST['path'] );

		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = $mime;
		header("Content-type: {$mime}");
		echo $blob;
		
		// Utils::out( ['path'=>$_REQUEST['path'], 'mime'=>$mime, 'blob'=>base64_encode($blob ), 'ret'=>$ret]);
	}





	/**
	 * ======= 向下兼容 =================================================
	 */

	/**
	 * 兼容旧的 file-uploader 控件
	 */
	function upload() {
		
		$action = empty($_REQUEST['action']) ? "upload" :  trim($_REQUEST['action']);
		$type = empty($_REQUEST['type']) ? 'file' : $_REQUEST['type'] ;

		if ( $action == 'upload' ) {
			
			$this->action_upload($type, $_REQUEST);
			return;

		} else if ( $action == 'crop' && $type == 'image' ) {
			
			$this->action_crop($_REQUEST);
			return;

		} else if ( $action == 'delete' ) {
			
			$this->action_delete($_REQUEST);
			return;
		} 

		throw new Excp("未知请求", 402, ['query'=>$_REQUEST] );
	}


	private function action_upload( $type, $query ) {

		if ( $_FILES['file']['error'] ||   $_FILES['file']['tmp_name'] == "" ) {
			throw new Excp("文件上传失败", 500, ['_FILES'=>$_FILES]);
		}

		$media = M('Media', $this->option );
		$ext = $media->getExt($_FILES['file']['name']);

		if ( $type == 'image' ) {
			
			$rs = $media->uploadImage($_FILES['file']['tmp_name'], $ext);
			echo json_encode(['url'=>$rs['origin'],  'path'=> $rs['path']]);
			return;

		} else if ( $type == 'file' ) {
			
			$name = empty($_GET['name']) ? $rs['name'] : $_GET['name'];

			$this->fileupload();

			return;
			$rs = $media->uploadFile($_FILES['file']['tmp_name'], $ext);
			echo json_encode(['url'=>$rs['small'], 'path'=> $rs['path'], "type"=>$ext, 'placeholder'=>$name]);
			
			return;

		} else if ( $type == 'video') {

			$rs = $media->uploadVideo($_FILES['file']['tmp_name'],$ext);
			echo json_encode(['url'=>$rs['small'], 'path'=> $rs['path']]);
			return;
		}

		throw new Excp("未知文件类型", 500, ['type'=>$type, 'query'=>$query, '_FILES'=>$_FILES]);
		
	}



	private function action_crop( $query ) {

		$width = intval($query['width']); 
		$height = intval($query['height']); 
		$x = intval($query['x']);
		$y = intval($query['y']);
		$path = $query['path'];	

		$media = M('Media', $this->option );
		$rs = $media->cropByPath($path, $x, $y, $width, $height );
		echo json_encode(['url'=>$rs['origin'], 'path'=> $rs['path']]);

	}

	private function action_delete( $query ) {
		$path = $query['path'];	
		$media = M('Media', $this->option );
		$rs = $media->rmByPath($path);
		echo json_encode(['ret'=>'complete', 'msg'=>'删除成功']);
	}


	/**
	 * ======= END 向下兼容 =================================================
	 */


	/**
	 * 裁切图片
	 * @return [type] [description]
	 */
	function imagecrop(){

		/**
		 * id:0e3e0a52860298b1ccc4dc2e4b340e66
			origin:/static-file/media/2017/07/12/0e3e0a52860298b1ccc4dc2e4b340e66.jpeg
			x:328.72156862745095
			y:1602.7607843137253
			width:1797.2705882352939
			height:1423.8117647058823
			rotate:0
			scaleX:1
			scaleY:1
		 */
		// $origin = $_POST['origin'];
		$media_id = $_POST['id'];
		$width = intval($_POST['width']);
		$height = intval($_POST['height']);
		$x = floatval( $_POST['x']);
		$y = floatval( $_POST['y']);
		$scaleX = floatval( $_POST['scaleX']);
		$scaleY = floatval( $_POST['scaleY']);
		$rotate = floatval( $_POST['rotate']);


		$media = M('Media', $this->option);
		$resp = $media->crop( $media_id, $x, $y, $width, $height );
		Utils::out($resp);
		return;
	}


	/**
	 * 调整图片大小
	 * @return [type] [description]
	 */
	function imageresize() {

		// $origin = $_POST['origin'];
		$media_id = $_POST['id'];
		$width = $_POST['width'];
		$height = $_POST['height'];
		$media = M('Media', $this->option);
		$resp = $media->resize( $media_id, $width, $height );
		Utils::out($resp);

		return;
	}



	/**
	 * 列出最近上传的图片清单
	 * @return
	 */
	function imagelatest() {

		$media = M('Media', $this->option);
		$perpage = 8;
		$page  = empty($_GET['__page']) ? 1 : abs(intval($_GET['__page']));
		$qb = $media->query()
					  ->where('type', '=', 'image')
					  ->whereNull('origin_id')
					  ->orderBy('created_at', 'desc');
		
		if ( !isset($_GET['hidden']) ) {
			$qb->where('hidden', '=', 0);
		} else if ( intval($_GET['hidden']) == 1 ) {
			$qb->where('hidden', '=', 1 );
		}

		$resp = $qb
					->select('media_id as id', 'origin_id', 'title', 'mimetype', 'path', 'small', 'tiny', 'extra', 'param', 'created_at')
					->pgArray($perpage, ['media_id as id'], '__page', $page);

		foreach ($resp['data'] as $idx => $rs) {
			$media->format( $resp['data'][$idx]);
			unset($resp['data'][$idx]['extra']);
		}

		if ( $_GET['debug'] ) {
			$resp['_sql'] = $qb->getSQL();
		}

		Utils::out( $resp );
		return;
	}


	/**
	 * 上传图片
	 * @return [type] [description]
	 */
	function imageupload() {
		
		$blob = file_get_contents($_FILES["file"]["tmp_name"]);
		if ( $blob === false ) {
			throw new Excp("读取文件出错 ({$_FILES["file"]["tmp_name"]})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES]);
		}

		$media = M('Media', $this->option);

		$tmp_name  =  $media->tmpName($_POST['name']);
		if ( $_POST['chunk'] == 0  ) {
			@unlink($tmp_name);
		}

		if (file_put_contents( $tmp_name, $blob, FILE_APPEND ) === false) {
			throw new Excp("读取文件出错 ({$tmp_name})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES, "tmp_name"=>$tmp_name]
			);
		}

		// 删除临时文件
		@unlink($_FILES["file"]["tmp_name"]);
		if ( intval($_POST['chunks']) == intval($_POST['chunk']) + 1) {
			$mediaData = $media->uploadImage( $tmp_name );
			
			if ( isset($mediaData['extra']) ) {
				unset($mediaData['extra']);
			}

			Utils::out( $mediaData  );
			return;
		}

		throw new Excp("未知错误 ({$_FILES["file"]["tmp_name"]})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES]);
	}


	/**
	 * 列出所有视频文件清单
	 * @return
	 */
	function videolatest() {
		$media = M('Media', $this->option);
		$perpage = 8;
		$page  = empty($_GET['__page']) ? 1 : abs(intval($_GET['__page']));

		$qb = $media->query()
					  ->where('hidden', '=', 0)
					  ->where('type', '=', 'video')
					  ->whereNull('origin_id')
					  ->orderBy('created_at', 'desc');

		if ( !isset($_GET['hidden']) ) {
			$qb->where('hidden', '=', 0);
		} else if ( intval($_GET['hidden']) == 1 ) {
			$qb->where('hidden', '=', 1 );
		}

		$resp =  $qb
					->select('media_id as id', 'origin_id', 'title', 'mimetype', 'path', 'small', 'tiny', 'extra', 'param', 'created_at')
					->pgArray($perpage, ['media_id as id'], '__page', $page);

		foreach ($resp['data'] as $idx => $rs) {
			$media->formatAsVideo( $resp['data'][$idx]);
			unset($resp['data'][$idx]['extra']);
		}

		Utils::out( $resp );
		return;
	}

	/**
	 * 上传视频组件
	 * @return [type] [description]
	 */
	function videoupload() {

		$url = $_POST['url'];
		if ( !empty($url) ) {
			$url = urldecode($url);
			$media = M('Media', $this->option);
			$resp = $media->saveVideoUrl($url);
			Utils::out( $resp );
			return;
		}
		
		$blob = file_get_contents($_FILES["file"]["tmp_name"]);
		if ( $blob === false ) {
			throw new Excp("读取文件出错 ({$_FILES["file"]["tmp_name"]})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES]);
		}

		$media = M('Media', $this->option);

		$tmp_name  =  $media->tmpName($_POST['name']);
		if ( $_POST['chunk'] == 0  ) {
			@unlink($tmp_name);
		}

		if (file_put_contents( $tmp_name, $blob, FILE_APPEND ) === false) {
			throw new Excp("读取文件出错 ({$tmp_name})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES, "tmp_name"=>$tmp_name]
			);
		}

		// 删除临时文件
		@unlink($_FILES["file"]["tmp_name"]);

		// 上传视频文件
		if ( intval($_POST['chunks']) == intval($_POST['chunk']) + 1) {
			
			$mediaData = $media->uploadVideo( $tmp_name );
			if ( isset($mediaData['extra']) ) {
				unset($mediaData['extra']);
			}
			Utils::out( $mediaData  );
			return;
		}

		throw new Excp("未知错误 ({$_FILES["file"]["tmp_name"]})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES]);
	}



	/**
	 * 302 转向图片地址
	 * @return [type] [description]
	 */
	function url() {
		$path = trim( $_GET['path']);
		$url = isset( $_GET['url'] )? $_GET['url'] : true;

		$media = M('Media', $this->option);
		$uri = $media->get( $path );

		if ( $url ){
			header("Location: {$uri['url']}");
			return;
		}

		header("Location: {$uri['origin']}");
		return;
	}


	/**
	 * 列出最近上传文件清单
	 */
	function filelatest() {
		$media = M('Media', $this->option);
		$perpage = 12;
		$page  = empty($_GET['__page']) ? 1 : abs(intval($_GET['__page']));
		$mimetype = trim($_GET['mt']);
		$ext = trim($_GET['ext']);
		$type = trim($_GET['type']);

		$qb = $media->query()
					  ->whereNull('origin_id');

		if ( !empty($type) ) {
			$types = explode(',', $type);
			$qb->whereIn('type', $types);
		}

		if ( !empty($mimetype) ) {
			$mimetypes = explode(',', $mimetype);
			$qb->whereIn('mimetype', $mimetypes);
		}

		if ( !empty($ext) ) {
			$exts = explode(',', $ext);
			$qb->whereIn('ext', $exts);
		}

		if ( !isset($_GET['hidden']) ) {
			$qb->where('hidden', '=', 0);
		} else if ( intval($_GET['hidden']) == 1 ) {
			$qb->where('hidden', '=', 1 );
		}

		$resp = $qb->orderBy('created_at', 'desc')
		   ->where('hidden', '=', 0)
		   ->select('media_id as id',  'origin_id', 'title', 'mimetype', 'path', 'small', 'tiny', 'extra', 'param', 'created_at')
		   ->pgArray($perpage, ['media_id as id'], '__page', $page);

		foreach ($resp['data'] as $idx => $rs) {
			$media->formatAsFile( $resp['data'][$idx] );
			unset($resp['data'][$idx]['extra']);
		}

		Utils::out( $resp );
		return;
	}



	/**
	 * 上传文件
	 * @return [type] [description]
	 */
	function fileupload() {
		

		$media = M('Media', $this->option);

		// 从地址下载上传
		$url = $_POST['url'];
		if ( !empty($url) ) {
			$mediaData = $media->uploadFile( $url );
			Utils::out( $mediaData  );
			return;
		}

        $mimetype = $_FILES["file"]["type"];	// mimetype
        if ( !empty($mimetype) ) {
            // 文件名称
            $mimes = Utils::mimes();
            if ("application/x-zip-compressed" == $mimetype) {
                $mimetype = "application/zip";
            }
            $ext = $mimes->getExtension($mimetype);
        }

        if (empty($ext)) {
            $ext = !empty($_POST['name'] ) ? $media->getExt( $_POST['name']) : $media->getExt( $_FILES["file"]["name"] );
        }

		// 单文件上传
		if (!array_key_exists('chunk', $_POST) ) {
			$mediaData = $media->uploadFile( $_FILES["file"]["tmp_name"], $ext );
			Utils::out( $mediaData  );
			@unlink($_FILES["file"]["tmp_name"]);
			return;
		}

		$rest = false;
		if ( intval($_POST['chunk']) == 0 ) {
			$rest = true;
		}

		// 分段上传
		$blob = file_get_contents($_FILES["file"]["tmp_name"]);
		if ( $blob === false ) {
			throw new Excp("读取文件出错 ({$_FILES["file"]["tmp_name"]})", 500, 
				['_POST'=>$_POST, "_FILES"=>$_FILES]);
		}

		// 根据名称生成 media_id
		
		$filename = !empty($_POST['name'] ) ? $_POST['name'] . date('Y-m-d')  : $_FILES["file"]["name"] . date('Y-m-d');
		$name =  hash('md4', $filename ) . '.' . $ext;
		$mediaData = $media->appendFile( $name, $blob, $rest, $ext );
		@unlink($_FILES["file"]["tmp_name"]);

		// 上传完毕输出结果
		if ( intval($_POST['chunks']) == intval($_POST['chunk']) + 1) {
			Utils::out( $mediaData  );
			return;
		}

		Utils::out(['code'=>0, 'message'=>"{$name} 分段上传完毕 {$_POST['chunk']}/{$_POST['chunks']}"]);
	}



	/**
	 * 更新文件标题
	 */
	function filesave() {

		$data = $_POST['data'];
		$media = M('Media', $this->option);
		if ( is_array($data) ) {
			foreach ($data as $rs ) {
				$id = $rs['id'];
				$title = $rs['title'];
				$media->updateBy('media_id', ['media_id'=>$id, 'title'=>$title]);
			}
		}

		Utils::out( ['code'=>0, 'message'=>'保存成功']);

	}


	function videosave() {

		$data = $_POST['data'];
		$media = M('Media', $this->option);
		if ( is_array($data) ) {
			foreach ($data as $rs ) {
				$id = $rs['id'];
				$title = $rs['title'];
				$media->updateBy('media_id', ['media_id'=>$id, 'title'=>$title]);
			}
		}

		Utils::out( ['code'=>0, 'message'=>'保存成功']);

	}

	function imagesave() {

		$data = $_POST['data'];
		$media = M('Media', $this->option);
		if ( is_array($data) ) {
			foreach ($data as $rs ) {
				$id = $rs['id'];
				$title = $rs['title'];
				$media->updateBy('media_id', ['media_id'=>$id, 'title'=>$title]);
			}
		}

		Utils::out( ['code'=>0, 'message'=>'保存成功']);

	}


	// 下载图片
	function download() {

		$media_id = $_GET['media_id'];
		$name = $_GET['name'];
		
		$media = M('Media', $this->option);
		$media->download( $media_id, $name );
	}


}
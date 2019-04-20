<?php
namespace Xpmse\Loader;

use \Xpmse\Loader\App as App;
use \Xpmse\Err as Err;
use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Tab as Tab;


class Upload extends \Xpmse\Loader\Controller {


	private $bucket = null;

	function __construct( $bucket = null ) {
		
		parent::__construct();

		$this->bucket = $bucket;
		if ( $this->bucket == null ) {
			$this->bucket = ( Conf::G('defaults/storage/public') !== null) ? Conf::G('defaults/storage/public') : 'local://public';
		}

	}


	/**
	 * 百度编辑器  ================================================================================================================
	 */
	function ueditor() {
		
		$action = (!isset($_GET['action']) ||  empty($_GET['action'])) ?   'unknown' : trim($_GET['action']);
		$controller = "ueditor" . ucwords(strtolower($action));
		if ( method_exists($this, $controller) ) {
			$this->ueditorFiledb();
			$this->$controller();
			return;
		}

		echo json_encode(["code"=>404, "message"=>"命令不存在", 'extra'=>['action'=>$action, '_GET'=>$_GET, '_POST'=>$_POST]]);

	}


	/**
	 * 创建一个资料库保存数据
	 * @return [type] [description]
	 */
	private function ueditorFiledb() {
		$image = new Tab('ueditor', 'image', function($tab) {
			$tab->putColumn('path', $tab->type('BaseString', ['screen_name'=>'路径','required'=>1, 'unique'=>1]) );
			$tab->putColumn('url', $tab->type('BaseString', ['screen_name'=>'地址','required'=>1, 'unique'=>1]) );
			$tab->putColumn('type', $tab->type('BaseString', ['screen_name'=>'类型']) );
			$tab->putColumn('title', $tab->type('BaseString', ['screen_name'=>'标题']) );
			$tab->putColumn('original', $tab->type('BaseString', ['screen_name'=>'原图']) );
		});

		$file = new Tab('ueditor', 'file', function($tab) {
			$tab->putColumn('path', $tab->type('BaseString', ['screen_name'=>'路径','required'=>1, 'unique'=>1]) );
			$tab->putColumn('url', $tab->type('BaseString', ['screen_name'=>'地址','required'=>1, 'unique'=>1]) );
			$tab->putColumn('type', $tab->type('BaseString', ['screen_name'=>'类型']) );
			$tab->putColumn('title', $tab->type('BaseString', ['screen_name'=>'标题']) );
			$tab->putColumn('original', $tab->type('BaseString', ['screen_name'=>'原文']) );
		});
	}


	/**
	 * 配置信息
	 * @return [type] [description]
	 */
	protected function ueditorConfig() {

		echo json_encode([
			"imageUrlPrefix"=>'',
			"imageActionName"=>'uploadimage',
			"imageFieldName"=>"uefile",
			"imageMaxSize"=>2048000,
			"imageAllowFiles"=>[".png", ".jpg", ".jpeg", ".gif"],
			"imageManagerUrlPrefix"=>'',
			"imageManagerActionName"=>"listimage",
			"imageManagerListSize"=>20,
			"imageManagerMaxSize"=>2048000,
			"imageManagerAllowFiles"=>[".png", ".jpg", ".jpeg", ".gif"],

			"fileUrlPrefix"=>'',
			"fileActionName"=>'uploadfile',
			"fileFieldName"=>"uefile",
			"fileMaxSize"=>51200000,
			"fileAllowFiles"=>[
				".png", ".jpg", ".jpeg", ".gif", ".bmp",
		        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
		        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
		        ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
		        ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
			],
			"fileManagerUrlPrefix"=>'',
			"fileManagerActionName"=>"listfile",
			"fileManagerListSize"=>20,
			"fileManagerMaxSize"=>51200000,
			"fileManagerAllowFiles"=> [
				".png", ".jpg", ".jpeg", ".gif", ".bmp",
		        ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
		        ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
		        ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
		        ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
			],
		]);

	}


	/**
	 * 文件列表接口
	 */
	protected function ueditorListfile() {
		$size = (isset($_GET['size'])) ? intval($_GET['size']) : 20;
		$start = (isset($_GET['start'])) ? intval($_GET['start']) : 0;

		$tab = new Tab('ueditor', 'file');
		$rows = $tab->select("ORDER by _id DESC LIMIT $start,$size ");
		$data = $rows['data']; unset($rows['data']);

		$rows['state'] = 'SUCCESS';
		$rows['list'] = $data;
		$rows['start'] = $start;
		echo json_encode($rows);

	}


	/**
	 * 上传文件
	 */
	protected function ueditorUploadfile() {

		$stor = new Stor();
		$path = "/apps/".strtolower($this->headers['Xpmse-Appname']);
		$allow = [
			".png", ".jpg", ".jpeg", ".gif", ".bmp",
		    ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
		    ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
		    ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
		    ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
		];

		$content = base64_decode($_FILES['uefile']['content']);
		$type = $stor->mimetypeByData($content);

		foreach ( $allow as $idx => $allowtype ) {
			if ( $allowtype[0] == '.' ) {
				$allowtype = substr($allowtype, 1, strlen($allowtype));
				$allow[$idx] = $stor->mimetypeBySuffix($allowtype);
			}
		}

		if ( !in_array($type, $allow) ) {
			echo json_encode(['state'=>'文件类型不允许上传']);
			return;
		}

		$bucket = $this->bucket;
		$tmpfile =  $_FILES['uefile']['tmp_name'];
		$name  =  $_FILES['uefile']['name'];
		$suffix = $stor->suffix( $type );
		$n = $stor->genName( $suffix );
		$n['name'] =  "$path{$n['name']}";

		// 保存附件
		$ret = $stor->putData("$bucket::{$n['name']}", $content );
		if (Err::isError($ret) ) {
			 echo json_encode(['state'=>$ret->message]);
			 return ;
		}

		$url = $stor->getUrl("$bucket::{$n['name']}");
		$tab = new Tab('ueditor','file');
		$data = [
			"state"=>'SUCCESS',
			"title" => $name,
			"original" => $name,
			"type" => $suffix,
			"path" =>"$bucket::{$n['name']}",
			"url" =>$url
		];
		
		$tab->create($data);
		echo json_encode( $data );
		return;

	}



	/**
	 * 图片列表接口
	 */
	protected function ueditorListimage() {
		$size = (isset($_GET['size'])) ? intval($_GET['size']) : 20;
		$start = (isset($_GET['start'])) ? intval($_GET['start']) : 0;

		$tab = new Tab('ueditor', 'image');
		$rows = $tab->select("ORDER by _id DESC LIMIT $start,$size ");
		$data = $rows['data']; unset($rows['data']);

		$rows['state'] = 'SUCCESS';
		$rows['list'] = $data;
		$rows['start'] = $start;
		echo json_encode($rows);


	}




	/**
	 * 上传图片
	 */
	protected function ueditorUploadimage() {

		$stor = new Stor();
		$path = "/apps/".strtolower($this->headers['Xpmse-Appname']);
		$allow = [".png", ".jpg", ".jpeg", ".gif"];

		$content = base64_decode($_FILES['uefile']['content']);
		$type = $stor->mimetypeByData($content);

		foreach ( $allow as $idx => $allowtype ) {
			if ( $allowtype[0] == '.' ) {
				$allowtype = substr($allowtype, 1, strlen($allowtype));
				$allow[$idx] = $stor->mimetypeBySuffix($allowtype);
			}
		}

		if ( !in_array($type, $allow) ) {
			echo json_encode(['state'=>'文件类型不允许上传']);
			return;
		}


		$bucket = $this->bucket;
		$tmpfile =  $_FILES['uefile']['tmp_name'];
		$name  =  $_FILES['uefile']['name'];
		$suffix = $stor->suffix( $type );
		$n = $stor->genName( $suffix );
		$n['name'] =  "$path{$n['name']}";


		// 保存附件
		$ret = $stor->putData("$bucket::{$n['name']}", $content );
		if (Err::isError($ret) ) {
			 echo json_encode(['state'=>$ret->message]);
			 return ;
		}

		$url = $stor->getUrl("$bucket::{$n['name']}");
		$tab = new Tab('ueditor','image');
		$data = [
			"state"=>'SUCCESS',
			"title" => $name,
			"original" => $name,
			"type" => $suffix,
			"path" =>"$bucket::{$n['name']}",
			"url" =>$url
		];
		
		$tab->create($data);
		echo json_encode( $data );
		return;

	}




	/**
	 * END 百度编辑器  ================================================================================================================
	 */




	/**
	 * 上传附件API
	 * @return [type] [description]
	 */
	function attachment() {


		$path = "/apps/".strtolower($this->headers['Xpmse-Appname']);
		$action = trim($_POST['action']);
		$allow =  (isset($_POST['allow'])) ? explode(',',trim($_POST['allow'])) : [];



		if ( $action == 'upload' ) {  // 上传附件

			if ( $_FILES['file']['error'] ||  $_FILES['file']['tmp_name'] == ""  || $_FILES['file']['content'] == "" ) {
				echo json_encode(['errno'=>'100500', 'errmsg'=>'文件上传失败', 'extra'=>['_FILES'=>$_FILES, '_POST'=>$_POST]]);
				return;
			}

			$stor = new Stor();
			$content = base64_decode($_FILES['file']['content']);
			$type = $stor->mimetypeByData($content);

			foreach ( $allow as $idx => $allowtype ) {
				if ( $allowtype[0] == '.' ) {
					$allowtype = substr($allowtype, 1, strlen($allowtype));
					$allow[$idx] = $stor->mimetypeBySuffix($allowtype);
				}
			}

			if ( !in_array($type, $allow) ) {
				echo json_encode(['errno'=>'100403', 'errmsg'=>'文件类型不允许上传', 'extra'=>['type'=>$type, 'allow'=>$allow]]);
				return;
			}

			$bucket = $this->bucket;
			$tmpfile =  $_FILES['file']['tmp_name'];
			$name  =  $_FILES['file']['name'];
			$suffix = $stor->suffix( $type );
			$n = $stor->genName( $suffix );
			$n['name'] =  "$path{$n['name']}";


			// 保存附件
			$ret = $stor->putData("$bucket::{$n['name']}", $content );
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			$url = $stor->getUrl("$bucket::{$n['name']}");
			echo json_encode( ['url'=>$url, 'path'=>"$bucket::{$n['name']}", 'type'=>$suffix, 'placeholder'=>$name ] );
			return;


		} else if ($action == 'delete') { // 删除文件
			$file = trim($_POST['path']);

			if ( $file == "" ) {
				echo ['errno'=>'100600', 'errmsg'=>'提交数据异常'];
				return;
			}

			$ret = $stor->del("{$file}");
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			echo json_encode(['ret'=>'complete', 'msg'=>'删除成功']);
			return;
		}
		
		// 无效请求
		echo json_encode(['errno'=>'100100', 'errmsg'=>'未知请求']);

	}



	/**
	 * 上传图片组件 API
	 */
	function index() {

		$path = "/apps/".strtolower($this->headers['Xpmse-Appname']);
		$action = trim($_POST['action']);

		// 上传图片
		if ( $action == 'upload' ) {  // 上传图片
			if ( $_FILES['file']['error'] ||   $_FILES['file']['tmp_name'] == ""  || $_FILES['file']['content'] == "" ) {
				echo json_encode(['errno'=>'100500', 'errmsg'=>'图片上传失败', 'extra'=>['_FILES'=>$_FILES, '_POST'=>$_POST]]);
				return;
			}

			$stor = new Stor();
			$bucket = $this->bucket;
			$type = $_FILES['file']['type'];
			$tmpfile =  $_FILES['file']['tmp_name'];
			$suffix = $stor->suffix( $type );
			$n = $stor->genName( $suffix );
			$n['name'] =  "$path{$n['name']}";


			// 保存原始图片
			$ret = $stor->putData("$bucket::{$n['name']}", base64_decode($_FILES['file']['content']) );
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			// 按比例裁切
			$width = intval($_POST['width']); 
			$height = intval($_POST['height']);
			$previews = json_decode( trim($_POST['previews']), true );
			$needResize = [['width'=>$width, 'height'=>$height]];

			foreach ($previews as $preview) {
				array_push( $needResize, ['width'=>intval($preview['width']), 'height'=>intval($preview['height'])]);
			}

			if ( $height <= 0 ) {
				$w_h = 1;
			} else {
				$w_h = $width/$height;
			}

			$ret = $stor->fit("$bucket::{$n['name']}", "$bucket::{$n['name']}_fit", $w_h );
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			foreach ($needResize as $r ) {
				$ret = $stor->resize("$bucket::{$n['name']}_fit", "$bucket::{$n['name']}_fit_{$r['width']}_{$r['height']}", $r['width'], $r['height']);
				if (Err::isError($ret) ) {
					 echo json_encode( $ret); 
					 return ;
				}
			}
			$url = $stor->getUrl("$bucket::{$n['name']}_fit");
			echo json_encode( ['url'=>$url, 'path'=>"$bucket::{$n['name']}_fit"] );
			return;

		// 裁切图片
		} else if ( $action == 'crop' ) { 

			$file = trim($_POST['path']);
			if ( $file == "" ) {
				echo json_encode(['errno'=>'100600', 'errmsg'=>'提交数据异常']);
				return;
			}

			// 裁切图片
			$stor = new Stor();
			$width = intval($_POST['width']); 
			$height = intval($_POST['height']); 
			$x = intval($_POST['x']);
			$y = intval($_POST['y']);

			$ret = $stor->crop( $file, "{$file}_fit", $width, $height, $x, $y );
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			// 缩略图
			$_width = intval($_POST['_width']); 
			$_height = intval($_POST['_height']);
			$previews = json_decode( trim($_POST['_previews']), true );
			$needResize = [['width'=>$_width, 'height'=>$_height]];
			
			foreach ($previews as $preview) {
				array_push( $needResize, ['width'=>intval($preview['width']), 'height'=>intval($preview['height'])] );
			}

			foreach ($needResize as $r ) {
				$ret = $stor->resize("{$file}_fit", "{$file}_fit_{$r['width']}_{$r['height']}", $r['width'], $r['height']);
				if (Err::isError($ret) ) {
					 echo json_encode( $ret); 
					 return ;
				}
			}
			$url = $stor->getUrl("{$file}_fit");

			echo json_encode( array('url'=>$url, 'path'=>"{$file}_fit") );
			return;


		// 删除图片
		} else if ($action == 'delete') { // 删除图片
			$file = trim($_POST['path']);
			$file = str_replace('_fit', '', $file);

			if ( $file == "" ) {
				echo json_encode(['errno'=>'100600', 'errmsg'=>'提交数据异常']);
				return;
			}


			// 缩略图
			$stor = new Stor();
			$_width = intval($_POST['_width']); 
			$_height = intval($_POST['_height']);
			$previews = json_decode( trim($_POST['_previews']), true );
			$needResize = array(array('width'=>$_width, 'height'=>$_height) );
			
			foreach ($previews as $preview) {
				array_push( $needResize, ['width'=>intval($preview['width']), 'height'=>intval($preview['height'])] );
			}

			$errs = array();
			$ret = $stor->del("{$file}");
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			$ret = $stor->del("{$file}_fit");
			if (Err::isError($ret) ) {
				 echo json_encode( $ret); 
				 return ;
			}

			foreach ($needResize as $r ) {
				$ret = $stor->del("{$file}_fit_{$r['width']}_{$r['height']}");
				if (Err::isError($ret) ) {
					 echo json_encode( $ret); 
					 return ;
				}
			}

			if ( count($ret) == 0 ) {
				echo json_encode(['ret'=>'complete', 'msg'=>'删除成功']);
				return;
			} else {
				echo json_encode(['errno'=>'100102', 'ret'=>$errs]);
				return;
			}
		}

		// 无效请求
		echo json_encode(['errno'=>'100100', 'errmsg'=>'未知请求']);
	}
}

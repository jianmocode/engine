<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );
// include_once( AROOT . 'lib' . DS . 'stor.class.php' );




use \Xpmse\Stor as Stor;
use \Xpmse\Conf as Conf;

class coreStoragePrivateController extends privateController
{
	function __construct()
	{
		// 载入默认的
		parent::__construct([],'dashboard','dashboard');
	}

	function test() {

		$stor = new Stor();
		// $ret = $stor->resize("public::/a.jpeg", "public::/a_400_400.jpeg", 400, 400 );

		// $ret = $stor->put('public::/test.jpg', 'http://f.hiphotos.baidu.com/image/pic/item/37d12f2eb9389b50729b90da8735e5dde6116ed5.jpg');
		//var_dump( $ret );
		

		die();

		$ret = $stor->putData("local://public::/test.txt", $data );

		$data = $stor->getData("public::/test.txt");
		$url = $stor->getUrl("public::/test.txt");
		$del = $stor->del( "public::/test.txt");
	}


	function url(){

		$f = t(v('file'));
		$e = t(v('e'));
		$bucket = ( Conf::G('defaults/storage/private') !== null) ? Conf::G('defaults/storage/private') : 'local://private';

		$stor = new Stor();
		$mimetype = $stor->mimetype("$bucket::$f");
		$data = $stor->getData("$bucket::$f");
		if (Err::isError($data) )  { die(json_encode($data)); }
		
		header('Content-Type: $mimetype');
		echo $data;
	}


	/**
	 * 上传图片API
	 */
	function upload() {
		
		$action = t(v('action'));

		if ( $action == 'upload' ) {
			if ( $_FILES['file']['error'] ||   $_FILES['file']['tmp_name'] == "" ) {
				die(json_encode(array('errno'=>'100500', 'errmsg'=>'服务端上传失败')));
			}

			$stor = new Stor();
			// $bucket = C('storage')['private'];
			$bucket = ( Conf::G('defaults/storage/private') !== null) ? Conf::G('defaults/storage/private') : 'local://private';

			$type = $_FILES['file']['type'];
			$tmpfile =  $_FILES['file']['tmp_name'];
			$suffix = $stor->suffix( $type );
			$n = $stor->genName( $suffix );

			// 保存原始图片
			$ret = $stor->putData("$bucket::{$n['name']}", file_get_contents($tmpfile) );
			if (Err::isError($ret) ) die( json_encode( $ret) );

			// 按比例裁切
			$width = intval($_POST['width']); 
			$height = intval($_POST['height']);
			$previews = json_decode( v('previews'), true );
			$needResize = array(array('width'=>$width, 'height'=>$height) );

			foreach ($previews as $preview) {
				array_push( $needResize, array('width'=>intval($preview['width']), 'height'=>intval($preview['height'])) );
			}

			$ret = $stor->fit("$bucket::{$n['name']}", "$bucket::{$n['name']}_fit", $width/$height );
			if (Err::isError($ret) ) die( json_encode( $ret) );

			foreach ($needResize as $r ) {
				$ret = $stor->resize("$bucket::{$n['name']}_fit", "$bucket::{$n['name']}_fit_{$r['width']}_{$r['height']}", $r['width'], $r['height']);
				if (Err::isError($ret) ) die( json_encode( $ret) );
			}
			$url = $stor->getUrl("$bucket::{$n['name']}_fit");

			echo json_encode( array('url'=>$url, 'path'=>"$bucket::{$n['name']}_fit") );
			die();

		} else if ( $action == 'crop' ) {

			$file = v('path');
			if ( $file == "" ) {
				die(json_encode(array('errno'=>'100600', 'errmsg'=>'提交数据异常')));
			}

			// 裁切图片
			$stor = new Stor();
			$width = intval($_POST['width']); 
			$height = intval($_POST['height']); 
			$x = intval($_POST['x']);
			$y = intval($_POST['y']);

			$ret = $stor->crop( $file, "{$file}_fit", $width, $height, $x, $y );
			if (Err::isError($ret) ) die( json_encode( $ret) );

			// 缩略图
			$_width = intval($_POST['_width']); 
			$_height = intval($_POST['_height']);
			$previews = json_decode( v('_previews'), true );
			$needResize = array(array('width'=>$_width, 'height'=>$_height) );
			
			foreach ($previews as $preview) {
				array_push( $needResize, array('width'=>intval($preview['width']), 'height'=>intval($preview['height'])) );
			}

			foreach ($needResize as $r ) {
				$ret = $stor->resize("{$file}_fit", "{$file}_fit_{$r['width']}_{$r['height']}", $r['width'], $r['height']);
				if (Err::isError($ret) ) die( json_encode( $ret) );
			}
			$url = $stor->getUrl("{$file}_fit");

			echo json_encode( array('url'=>$url, 'path'=>"{$file}_fit") );
			die();
		} else if ($action == 'delete') {
			$file = v('path');
			$file = str_replace('_fit', '', $file);

			if ( $file == "" ) {
				die(json_encode(array('errno'=>'100600', 'errmsg'=>'提交数据异常')));
			}

			// 缩略图
			$stor = new Stor();
			$_width = intval($_POST['_width']); 
			$_height = intval($_POST['_height']);
			$previews = json_decode( v('_previews'), true );
			$needResize = array(array('width'=>$_width, 'height'=>$_height) );
			
			foreach ($previews as $preview) {
				array_push( $needResize, array('width'=>intval($preview['width']), 'height'=>intval($preview['height'])) );
			}

			$errs = array();
			$ret = $stor->del("{$file}");
			if (Err::isError($ret) ) array_push($errs, $ret);

			$ret = $stor->del("{$file}_fit");
			if (Err::isError($ret) ) array_push($errs, $ret);

			foreach ($needResize as $r ) {
				$ret = $stor->del("{$file}_fit_{$r['width']}_{$r['height']}");
				if (Err::isError($ret) ) array_push($errs, $ret);
			}

			if ( count($ret) == 0 ) {
				echo json_encode(array('ret'=>'complete', 'msg'=>'删除成功'));
			} else {
				echo json_encode(array('errno'=>'100102', 'ret'=>$errs ));
			}

		}


		echo json_encode(array('errno'=>'100100', 'errmsg'=>'未知请求'));

	}

} 
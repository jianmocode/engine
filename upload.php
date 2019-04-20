<?php
define( 'DS' , DIRECTORY_SEPARATOR );
define( 'AROOT' , dirname( __FILE__ ) . DS  );
define( 'SEROOT',  dirname( __FILE__ ) . DS . 'service');
include_once(__DIR__ . DS . '_lp' . DS . 'autoload.php' );

// include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Stor as Stor;
use \Xpmse\Conf as Conf;
use \Xpmse\Err as Err;

$action = trim($_POST['action']);

if ( $action == 'upload' ) {  // 上传图片
	if ( $_FILES['file']['error'] ||   $_FILES['file']['tmp_name'] == "" ) {
		die(json_encode(array('errno'=>'100500', 'errmsg'=>'服务端上传失败')));
	}

	$stor = new Stor();
	// $bucket = C('storage')['public'];
	$bucket = ( Conf::G('defaults/storage/public') !== null) ? Conf::G('defaults/storage/public') : 'local://public';

	$type = $_FILES['file']['type'];
	$tmpfile =  $_FILES['file']['tmp_name'];
	$suffix = $stor->suffix( $type );
	$n = $stor->genName( $suffix );
	$n = [
		"name"=>"/media/defaults/favicon.tmp.png",
		"suffix" => 'png',
		"basename"=>"favicon",
		"path" => "/media/defaults"
	];

	// 保存原始图片
	$ret = $stor->putData("$bucket::{$n['name']}", file_get_contents($tmpfile) );
	if (Err::isError($ret) ) die( json_encode( $ret) );

	// 按比例裁切
	$width = intval($_POST['width']); 
	$height = intval($_POST['height']);
	$previews = json_decode( trim('previews'), true );
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

	echo json_encode( array('url'=>$url . "?". time(), 'path'=>"$bucket::{$n['name']}_fit") );
	die();

} else if ( $action == 'crop' ) { // 裁切图片

	$file = 'local://public::/media/defaults/favicon.tmp.png';
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
	$previews = json_decode( trim('_previews'), true );
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


} else if ($action == 'delete') { // 删除图片
	$file = 'local://public::/media/defaults/favicon.tmp.png';
	$file = str_replace('_fit', '', $file);

	if ( $file == "" ) {
		die(json_encode(array('errno'=>'100600', 'errmsg'=>'提交数据异常')));
	}

	// 缩略图
	$stor = new Stor();
	$_width = intval($_POST['_width']); 
	$_height = intval($_POST['_height']);
	$previews = json_decode( trim('_previews'), true );
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
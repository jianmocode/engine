<?php
/**
 *  local 本地存储  存储引擎插件 
 *
 * CLASS 
 *
 * 	   localStoragePlugin
 *
 * USEAGE:
 *
 *   不要直接使用
 *
 * 
 */

namespace Xpmse;
require_once( __DIR__ . '/../Inc.php');
require_once( __DIR__ . '/../Err.php');
require_once( __DIR__ . '/../Excp.php');

use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;



class localStoragePlugin {

	private $_conf = array();

	function __construct( $conf ) {
		$this->_conf = $conf;	
	}


	function putData( $bucket, $file, $data, $replace ) {

		$root = $this->_conf['bucket'][$bucket]['root'];
		
		if ( !is_dir($root) ) {
			throw new Excp("路径不存在", '310404', ['bucket'=>$bucket, 'file'=>$file, 'data'=>$data, 'replace'=>$replace,'conf'=>$this->_conf]);	
		}

		if ( !is_writeable($root) ) {
			throw new Excp("路径不可写", '310403', ['bucket'=>$bucket, 'file'=>$file, 'data'=>$data, 'replace'=>$replace,'conf'=>$this->_conf]);	
		}

		$path_filename = $root . $file;

		if ( !$replace && file_exists($path_filename) ) {
			throw new Excp("文件已存在", '310400', ['bucket'=>$bucket, 'file'=>$file, 'data'=>$data, 'replace'=>$replace,'conf'=>$this->_conf]);	
		}

		if ( !is_dir(dirname($path_filename)) ) {
			if ( @mkdir(dirname($path_filename), 0777, true) === false ) {
				return new Err('310501', "创建文件目录出错", ['bucket'=>$bucket, 'file'=>$file, 'data'=>$data, 'replace'=>$replace,'conf'=>$this->_conf]);	
			}
		}

		if ( file_put_contents($path_filename, $data) === false ) {
			return new Err('310502', "写入文件出错", ['bucket'=>$bucket, 'file'=>$file, 'data'=>$data, 'replace'=>$replace,'conf'=>$this->_conf]);	
		}

		return true;
	}


	function getData( $bucket, $file ) {

		$root = $this->_conf['bucket'][$bucket]['root'];
		$path_filename = $root . $file;

		if ( !file_exists($path_filename) ) {
			return new Err('311404', "文件不存在", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);	
		}

		if ( !is_readable($path_filename) ) {
			return new Err('311403', "无文件读取权限", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);	
		}

		$data = file_get_contents($path_filename);
		if ( $data === false ) {
			return new Err('311501', "读取文件失败", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);	
		}

		return $data;

	}

	function getUrl( $bucket, $file ) {

		$root = $this->_conf['bucket'][$bucket]['root'];
		$home = $this->_conf['bucket'][$bucket]['home'];
		$path_filename = $root . $file;
		if ( !file_exists($path_filename) ) {
			return new Err('312404', "文件不存在", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}
		
		return $home . $file;
	}


	function delete( $bucket, $file ) {

		$root = $this->_conf['bucket'][$bucket]['root'];
		$path_filename = $root . $file;

		if ( !file_exists($path_filename) ) {
			return  new Err('312404', "文件不存在", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}

		if ( !is_writeable($path_filename) ) {
			return  new Err('313403', "无文件读取权限", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}

		if ( unlink($path_filename) === false ) {
			return  new Err('313501', "删除文件失败", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}

		return true;
	}


	function mimetype( $bucket, $file ) {

		$root = $this->_conf['bucket'][$bucket]['root'];
		$path_filename = $root . $file;

		if ( !file_exists($path_filename) ) {
			return  new Err('313404', "文件不存在", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}

		if ( !is_writeable($path_filename) ) {
			return  new Err('313403', "无文件读取权限", ['bucket'=>$bucket, 'file'=>$file, 'conf'=>$this->_conf]);
		}


		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_file($finfo, $path_filename);
		finfo_close($finfo);
		
		return $type;
	}
}

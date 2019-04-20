<?php
namespace Xpmse\Model;

/**
 * 
 * 后台执行任务库 ( 用来查看后台正在运行进程 )
 * XpmSE 1.4.7 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Task
 *
 * USEAGE: 
 *
 */

use \Xpmse\Media  as MediaModel;
class Media extends MediaModel {
 	function __construct( $param=[] ) {
 		parent::__construct($param);
 	}
 }


/**
 * 
 * 媒体文件模型 ( 媒体文件模型，本地媒体库 )
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Media
 *
 * USEAGE: 
 *
 */

// use \Xpmse\Model as Model;
// use \Xpmse\Mem as Mem;
// use \Xpmse\Excp as Excp;
// use \Xpmse\Err as Err;
// use \Xpmse\Conf as Conf;
// use \Xpmse\Stor as Stor;
// use \Xpmse\Utils as Utils;

// use Mina\Storage\Local;

// class Media extends Model {

// 	private $stor = null;


// 	/**
// 	 * 媒体数据表
// 	 * @param integer $company_id [description]
// 	 */
// 	function __construct( $param=[] ) {

// 		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
// 		parent::__construct($param , $driver );
// 		$this->table('media');

// 		$root = Conf::G("storage/local/bucket/public/root");
// 		$options = [
// 			"prefix" => $root . '/media',
// 			"url" => "/static-file/media",
// 			"origin" => "/static-file/media",
// 			"cache" => [
// 				"engine" => 'redis',
// 				"prefix" => '_mediaStorage:',
// 				"host" => Conf::G("mem/redis/host"),
// 				"port" => Conf::G("mem/redis/port"),
// 				"raw" =>3600,  // 数据缓存 1小时
// 				"info" => 3600   // 信息缓存 1小时
// 			]
// 		];
// 		$this->stor = new Local( $options );
// 	}


// 	/**
// 	 * 使用对象路径，换区对象信息
// 	 * @param  [type] $path [description]
// 	 * @return [type]       [description]
// 	 */
// 	function get( $path ) {
// 		return $this->stor->get($path);
// 	}


// 	/**
// 	 * 数据表结构
// 	 * @return [type] [description]
// 	 */
// 	function __schema() {
// 		// 数据结构
// 		try {
			
// 			// 媒体文件ID
// 			$this->putColumn( 'media_id', $this->type('string', [ "length"=>128,   'unique'=>1] ) )

// 			// 原始文件ID ( 如数值存在，则表示文件为处理后的文件 )
// 			->putColumn( 'origin_id', $this->type('string', ["length"=>128,  'index'=>1, 'default'=>0] ))

// 			// 媒体文件名称 ( 标题 )
// 			->putColumn( 'title', $this->type('string', [ "null"=>false, 'length'=>128, 'default'=>'未命名'] ) )

// 			// 媒体文件类型 image/video/audio/text/html/css/js/word/excel/ppt/pdf/zip/json/page
// 			->putColumn( 'type', $this->type('string', [ "null"=>false,  'index'=>1,  'length'=>128] ) )

// 			// 媒体文件类型 ( MIME Type )
// 			->putColumn( 'mimetype', $this->type('string', [ "null"=>false,  'length'=>128] ) )

// 			// 对象路径
// 			->putColumn( 'path', $this->type('string', [ 'index'=>1, 'length'=>128] ) )

// 			// 缩略图(封皮) 路径 ( 300 X 225 )
// 			->putColumn( 'small', $this->type('string', ['length'=>128] ) )

// 			// 缩略图标 (封皮) 路径 ( 64 X 64 )
// 			->putColumn( 'tiny', $this->type('string', ['length'=>128] ) )

// 			// 扩展信息 
// 			->putColumn( 'extra', $this->type('text', ['json'=>true] ) )		
			
// 			// 自定义查询条件
// 			->putColumn( 'param', $this->type('string', ['length'=>128,'index'=>1]) )

// 			;

// 		} catch( Exception $e ) {
// 			Excp::elog($e);
// 			throw $e;
// 		}
// 	}

// 	function media_id( $file_name ) {

// 		$nextid = $this->nextid();
// 		$param_string = "[{$nextid}]{$file_name}";

// 		// MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
// 		return hash('md4',  $param_string);   
// 	}

// 	function getPath( $media_id, $ext ) {
// 		$folder = date('/Y/m/d');
// 		return "{$folder}/{$media_id}.{$ext}";
// 	}

// 	function getExt( $file_name ) {
// 		$arr = explode('.',$file_name);
// 		$ext = strtolower(array_pop($arr));
// 		return $ext;
// 	}

// 	function tmpName( $name ) {
// 		$ext = $this->getExt($name);
// 		$dir = sys_get_temp_dir() . "/". date('Y-m-d');
// 		if ( !is_dir($dir) ) {
// 			mkdir($dir);
// 		}
// 		return $dir. "/". hash('md4',  $name) . ".{$ext}";
// 	}



// 	function format( & $rs ) {

// 		if ( is_array($rs['extra']) ) {
// 			$rs['width'] = $rs['extra']['width'];
// 			$rs['height'] = $rs['extra']['height'];
// 		}

// 		if ( isset( $rs['media_id']) ) {
// 			$rs['id'] =$rs['media_id'];
// 		}

// 		if ( isset( $rs['path']) ) {
// 			$info = $this->stor->get($rs['path']);
// 			$rs['origin'] = $info['origin'];
// 			$rs['url'] = $info['url'];
// 		}

// 		if ( isset( $rs['small']) ) {
// 			$info = $this->stor->get($rs['small']);
// 			$rs['small'] = $info['url'];
// 			// $rs['url'] = $info['url'];
// 		}

// 		if ( isset( $rs['tiny']) ) {
// 			$info = $this->stor->get($rs['tiny']);
// 			$rs['tiny'] = $info['url'];
// 		}

// 		return $rs;
// 	}


// 	/**
// 	 * 调整图片大小
// 	 * @param  [type] $origin [description]
// 	 * @param  [type] $width  [description]
// 	 * @param  [type] $height [description]
// 	 * @return [type]         [description]
// 	 */
// 	function resize( $origin_media_id, $width, $height ) {

// 		$origin = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$origin_media_id]);
// 		if ( empty($origin) ) {
// 			throw new Excp("原图片不存在 ( $origin_media_id )", 400 , ['origin_media_id'=>$origin_media_id]);
// 		}

// 		$media_id = $this->media_id( $origin['path'] . "_{$width}_{$height}" ); 
// 		$ext = !empty($ext)? trim($ext): $this->getExt( $origin['path'] );
// 		$dest = $this->getPath( $media_id, $ext );
// 		$width = intval( $width);
// 		$height = intval( $height );

// 		$fstat = $this->stor->resize( $origin['path'], $dest, ['width'=>$width, 'height'=>$height]);
// 		$extra = $origin['extra'];
// 		$extra['width'] = $fstat['width'];
// 		$extra['height'] = $fstat['height'];
// 		$extra['resize'] = true;
// 		$extra['origin'] = $origin['path'];
// 		$extra['origin_id'] = $origin_media_id;

// 		// 数据入库
// 		$data = [
// 			"media_id"=>$media_id,
// 			"path" => $dest,
// 			"small" => $origin['small'],
// 			"tiny" =>  $origin['tiny'],
// 			"mimetype"=> $fstat['mime'],
// 			"type" => "image",
// 			"origin_id" => $origin['media_id'],
// 			"extra" => $extra
// 		];

// 		$rs = $this->create($data);
// 		return $this->format($rs);
// 	}


// 	/**
// 	 * 裁切图片
// 	 * @param  [type] $origin_media_id [description]
// 	 * @param  [type] $x               [description]
// 	 * @param  [type] $y               [description]
// 	 * @param  [type] $width           [description]
// 	 * @param  [type] $height          [description]
// 	 * @return [type]                  [description]
// 	 */
// 	function crop( $origin_media_id, $x, $y, $width, $height ) {

// 		$origin = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$origin_media_id]);
// 		if ( empty($origin) ) {
// 			throw new Excp("原图片不存在 ( $origin_media_id )", 400 , ['origin_media_id'=>$origin_media_id]);
// 		}

// 		$media_id = $this->media_id( $origin['path'] . "_{$x}_{$y}_{$width}_{$height}" ); 
// 		$ext = !empty($ext)? trim($ext): $this->getExt( $origin['path'] );
// 		$dest = $this->getPath( $media_id, $ext );
// 		$width = intval( $width);
// 		$height = intval( $height );

// 		$fstat = $this->stor->crop( $origin['path'], $dest, [
// 			'width'=>$width, 
// 			'height'=>$height,
// 			'x' => intval($x),
// 			'y' => intval($y)
// 		]);
// 		$extra = $origin['extra'];
// 		$extra['width'] = $fstat['width'];
// 		$extra['height'] = $fstat['height'];
// 		$extra['crop'] = true;
// 		$extra['origin'] = $origin['path'];
// 		$extra['origin_id'] = $origin_media_id;

// 		// 数据入库
// 		$data = [
// 			"media_id"=>$media_id,
// 			"path" => $dest,
// 			"small" => $origin['small'],
// 			"tiny" =>  $origin['tiny'],
// 			"mimetype"=> $fstat['mime'],
// 			"type" => "image",
// 			"origin_id" => $origin['media_id'],
// 			"extra" => $extra
// 		];

// 		$rs = $this->create($data);
// 		return $this->format($rs);
// 	}



// 	/**
// 	 * 上传图片
// 	 * @param  [type] $file [description]
// 	 * @return [type]       [description]
// 	 */
// 	function uploadImage( $file_name, $ext=null ) {
		
// 		if ( !file_exists($file_name) ) {
// 			throw new Excp('文件不存在', 404, ['file_name'=>$file_name, 'ext'=>$ext]);
// 		}

// 		if ( !is_readable($file_name) ) {
// 			throw new Excp('文件无法访问', 403, ['file_name'=>$file_name, 'ext'=>$ext]);
// 		}

// 		$blob = file_get_contents( $file_name );
// 		if ( $blob  === false) {
// 			throw new Excp('读取文件失败', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
// 		}

// 		// 将文件上传到存储空间
// 		$media_id = $this->media_id( $file_name ); 
// 		$ext = !empty($ext)? trim($ext): $this->getExt( $file_name );
// 		$path = $this->getPath( $media_id, $ext );

// 		$fstat = $this->stor->upload( $path, $blob );
// 		if ( !$this->stor->is_a($path, 'image') ) {
// 			throw new Excp('文件类型不是图片', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
// 		}

// 		$extra = $this->stor->info($path);

// 		// 生成图片缩略图
// 		$path_16v9 = $this->getPath( $media_id . "_16v9",  $ext );
// 		$path_320 = $this->getPath( $media_id . "_320",  $ext );
// 		$path_1v1 = $this->getPath( $media_id . "_1v1",  $ext );
// 		$path_64  = $this->getPath( $media_id . "_64",  $ext );

// 		$this->stor->crop( $path, $path_16v9,  ['ratio'=>4/3]);
// 		$this->stor->crop( $path, $path_1v1,  ['ratio'=>1]);
// 		$this->stor->resize( $path_16v9, $path_320, ['width'=>320, 'height'=>180]);
// 		$this->stor->resize( $path_1v1, $path_64, ['width'=>64, 'height'=>64]);
// 		$this->stor->remove( $path_16v9);
// 		$this->stor->remove( $path_1v1);

// 		// 数据入库
// 		$data = [
// 			"media_id"=>$media_id,
// 			"path" => $path,
// 			"small" => $path_320,
// 			"tiny" => $path_64,
// 			"mimetype"=> $fstat['mime'],
// 			"type" => "image",
// 			"extra" => $extra
// 		];

// 		$rs = $this->create($data);
// 		return $this->format($rs);
// 	}


// }
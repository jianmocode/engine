<?php
namespace Xpmse;

require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/Que.php');
require_once( __DIR__ . '/Model.php');



/**
 * 
 * 媒体文件模型 ( 媒体文件模型，本地媒体库 )
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *     \Xpmse\Media
 *
 * USEAGE: 
 *
 */

use \imagick as imagick;
use \ImagickPixel as ImagickPixel;
use \ImagickDraw as ImagickDraw;

use \Endroid\QrCode\QrCode as Qrcode;
use \Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;

use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;

use \Mina\Storage\Local;
use \Mina\Cache\Redis as Cache;
use \OSS\OssClient;
use \OSS\Core\OssException;

class Media extends Model {

	private $stor = null;
	private $options = [];

	/**
	 * 媒体数据表
	 * @param array $param 
	 *        boolean $param["private"] 默认 false 是否公开访问
	 *        string $param["host"] XpmSE实例主目录, 默认为空代表本实例
	 *        string $param["appid"] APPID
	 *        string $param["secret"] SECRET KEY
	 *        string $param["root"] 文件根目录，默认为系统指定的 public / pirvate 目录
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'], $driver );
		$this->table('media');

		$host = empty($param['host']) ? '' : $param['host'];
		$appid = empty($param['appid']) ? '' : $param['appid'];
		$secret = empty($param['secret']) ? '' : $param['secret'];

        $url_prefix = '/media';
        if ( $param['root'] == "{nope}" ) {
            $root = "";
            $url_prefix = "/";
        }

		$root = Conf::G("storage/local/bucket/public/root");
        $root = empty($param['root']) ? $root : trim($param['root']);

        if ( !empty($host) ) {
            // $host = str_replace(["https:", "http:"], ["", ""], $host);
        }
        
		$options = [
			"prefix" => "$root{$url_prefix}",
			"url" => "{$host}/static-file{$url_prefix}",
			"origin" => "{$host}/static-file{$url_prefix}",
			"cache" => [
				"engine" => 'redis',
				"prefix" => '_mediaStorage:',
				"host" => Conf::G("mem/redis/host"),
				"port" => Conf::G("mem/redis/port"),
				"passwd"=> Conf::G("mem/redis/password"),
				"raw" =>3600,  // 数据缓存 1小时
				"info" => 3600   // 信息缓存 1小时
			]
        ];
        

		$root_private = Conf::G("storage/local/bucket/private/root");
        $root_private = empty($param['root']) ? $root_private : $param['root'];
		$options_private = [
			"prefix" => "$root_private{$url_prefix}",
			"url" => "{$host}/private-file",
			"origin" => "{$host}/private-file",
			"appid" => $appid,
			"secret" => $secret,
			"cache" => [
				"engine" => 'redis',
				"prefix" => '_mediaStoragePrivate:',
				"host" => Conf::G("mem/redis/host"),
				"port" => Conf::G("mem/redis/port"),
				"passwd"=> Conf::G("mem/redis/password"),
				"raw" =>0,  // 数据缓存 1小时
				"info" => 0   // 信息缓存 1小时
			]
		];



		// 私密访问
		$opts = ($param['private'] == true ) ? $options_private : $options;
		$this->options = $opts;
		$this->options['private'] = $param['private'];
		$this->options['fingerprint'] = empty($param['fingerprint']) ? false : true;
		

		try {
			$optionInst=new \Xpmse\Option;
			$this->options['oss']=$optionInst->get('dashboard');
		} catch( Excp $e ){ }



		// var_dump('fingerprint');
		// var_dump($param['fingerprint']);
		// var_dump($this->options['fingerprint']);
		// var_dump($this->options);exit;

		// // 生成访问 Token
		// if( $params['private'] === true ) {

		// }
		$this->stor = new Local( $opts );

		$this->defaultIcons = [
			"aep"=>"/static/defaults/images/icons/filetype/AEP.svg",
			"css"=>"/static/defaults/images/icons/filetype/CSS.svg",
			"html"=>"/static/defaults/images/icons/filetype/HTML.svg",
			"htm"=>"/static/defaults/images/icons/filetype/HTML.svg",
			"new"=>"/static/defaults/images/icons/filetype/NEW.svg",
			"ppt"=>"/static/defaults/images/icons/filetype/PPT.svg",
			"pptx"=>"/static/defaults/images/icons/filetype/PPT.svg",
			"txt"=>"/static/defaults/images/icons/filetype/TXT.svg",
			"ai"=>"/static/defaults/images/icons/filetype/AI.svg",
			"doc"=>"/static/defaults/images/icons/filetype/DOC.svg",
			"docx"=>"/static/defaults/images/icons/filetype/DOC.svg",
			"jpg"=>"/static/defaults/images/icons/filetype/JPEG.svg",
			"jpeg"=>"/static/defaults/images/icons/filetype/JPEG.svg",
			"pdf"=>"/static/defaults/images/icons/filetype/PDF.svg",
			"psd"=>"/static/defaults/images/icons/filetype/PSD.svg",
			"url"=>"/static/defaults/images/icons/filetype/URL.svg",
			"avi"=>"/static/defaults/images/icons/filetype/AVI.svg",
			"eps"=>"/static/defaults/images/icons/filetype/EPS.svg",
			"mov"=>"/static/defaults/images/icons/filetype/MOV.svg",
			"php"=>"/static/defaults/images/icons/filetype/PHP.svg",
			"rar"=>"/static/defaults/images/icons/filetype/RAR.svg",
			"xls"=>"/static/defaults/images/icons/filetype/XLS.svg",
			"xlsx"=>"/static/defaults/images/icons/filetype/XLS.svg",
			"cdr"=>"/static/defaults/images/icons/filetype/CDR.svg",
			"gif"=>"/static/defaults/images/icons/filetype/GIF.svg",
			"mp3"=>"/static/defaults/images/icons/filetype/MP3.svg",
			"png"=>"/static/defaults/images/icons/filetype/PNG.svg",
			"ttf"=>"/static/defaults/images/icons/filetype/TTF.svg",
			"ttc"=>"/static/defaults/images/icons/filetype/TTF.svg",
			"zip"=>"/static/defaults/images/icons/filetype/ZIP.svg",
			"tar"=>"/static/defaults/images/icons/filetype/ZIP.svg",
			"unknown"=>"/static/defaults/images/icons/filetype/UKN.svg"
		];
	}



	/**
	 * 生成一个压缩包
	 * @param  array $data 文件清单
	 * 
	 *               示例:
	 *               $data = [
	 *               	"/path/of/img1.jpg"  => "/数据目录/图片/图片1.jpg",
	 *               	"/path/of/img2.jpg"  => "/数据目录/图片/图片2.jpg",
	 *               	"/path/of/img3.jpg"  => "/数据目录/图片/图片3.jpg"
	 *               ];
	 *
	 * @param array $option 压缩选项，默认 []
	 *              boolean $option['output'] 是否直接在浏览器端输出
	 *              string  $option['name']   下载的压缩包名称 (浏览器输出时有效)
	 *              string  $option['password'] 解压密码, 默认没有密码
	 *              
	 * @return array $rs 返回文件媒体数据
	 *               $rs['path'] 压缩包路径
	 *               $rs['origin'] 压缩包下载原始地址
	 *               $rs['url']  压缩包下载地址 (CDN)
	 *               $rs['media_id'] 媒体ID 
	 *               ...
	 */
	function zip( $data, $option = [] ) {

		$name = empty($option['name']) ? time() : $option['name'];
		$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . date('Y-m-d');
		$workdir = $base . DIRECTORY_SEPARATOR . $name;
		$zipfile = $base . DIRECTORY_SEPARATOR .  $name   . ".zip";

		if ( !is_dir($workdir) ) {
			mkdir($workdir, 0777, true);
		}

		$stor = $this->stor;
		$zip = Utils::zip();

		foreach ($data as $path => $dir ) {
			$dstfile = $workdir . $dir;
			$dstdir = dirname($dstfile);
			if ( !is_dir($dstdir) ) {
				mkdir($dstdir, 0777, true);
			}
			$blob = $stor->getBlob( $path );
			file_put_contents( $dstfile, $blob );
		}

		$zip->addDirRecursive( $workdir );

		// 访问密码
		if ( is_string($option['password']) ) {
			$zip->withNewPassword($option['password']);
		}

		// 是否直接下载到浏览器
		$rs = null;
		if ( $option['output'] == true ){
			$zip->outputAsAttachment( $name   . ".zip");

		} else {
			// 上传到Media
			$blob = $zip->outputAsString();
			$rs = $this->uploadFileBlob($blob, "zip");
		}

		// 删除临时数据
		Utils::rmdir($workdir);
		
		return $rs;
	}





	/**
	 * 下载文件
	 * @param  string $media_id 媒体文件地址
	 * @param  [type] $name     [description]
	 * @return [type]           [description]
	 */
	function download( $media_id, $name=null ) {
		
		$rs = $this->getLine("WHERE media_id=?", ['*'], [$media_id]); 
		if ( empty($rs) ) {
			throw new Excp("媒体文件不存在", 404,  ['media_id'=>$media_id]);
		}

		$mimetype = $rs['mimetype'];
		$ext = $this->getExt($rs['path']);

		if ( empty($name) ) {
			$name = basename($rs['path']);
		} else {
			$name = "{$name}.{$ext}";
		}

		header("Content-type: {$mimetype}");
		header("Content-Disposition: attachment; filename=\"{$name}\"");
		echo $this->stor->getBlob( $rs['path'] );
		exit;
	}






	function getImageUrl( $media_id, $size = null ) {
		$cache = new Cache( $this->options['cache'] );
		$cname = 'display:' . $media_id;
		$rs = $cache->getJSON($cname);

		if ( $rs === false ) {
			$rs = $this->getLine("WHERE media_id=?", 
				['*'], 
				[$media_id] ); 
			if ( $rs == null) {
				throw new Excp('未找到媒体资源', 404, ['media_id'=>$media_id, 'size'=>$size]);
			}
			
			$this->format($rs);
			$cache->setJSON($cname, $rs, 3600);
		}

		if ( $rs['type'] != 'image') {
			throw new Excp('媒体类型不是图片', 500, ['media_id'=>$media_id, 'size'=>$size, 'rs'=>$rs]);
		}

		if (intval($size) == 320 || $size == 'small') {
			return $rs['small'];
		} else if (intval($size) == 64 || $size == 'tiny') {
			return $rs['tiny'];
		} else if (intval($size) == 1 || $size == 'origin') {
			return $rs['origin'];
	    } else {
			return $rs['url'];
		}
	}


	/**
	 * 显示图片
	 * @param  [type] $media_id [description]
	 * @param  [type] $size     [description]
	 * @return [type]           [description]
	 */
	function displayImage( $media_id, $size=null ) {

		$url = $this->getImageUrl($media_id, $size);
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$url}");
	}



	/**
	 * 使用对象路径，换区对象绝对路径
	 * @param  [type] $path [description]
	 * @return [type]       [description]
	 */
	// function getrealpath( $path ) {

	// }


	/**
	 * 返回 Storage 对象
	 * @return [type] [description]
	 */
	public function stor()  {
		return $this->stor;
	}


	/**
	 * 使用对象路径，换取对象文件内容
	 */
	function blob( $path ) {
		return $this->stor->getBlob($path);
	}


	/**
	 * 根据对象路径，读取对象完整信息
	 * @param  string $path 对象路径
	 * @param  boolean $nocache TRUE 不启用缓存, FALSE 启用缓存
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	function get( $path, $nocache = false ) {
        
		if ( is_numeric($path) ) {
			return parent::get( $path );
		}
		$info = $this->stor->get($path, $nocache );

		if ($this->options['private'] === true ) {
			$info['origin'] = $this->privateURL($info['path']);
			$info['url'] = $this->privateURL($info['path']);
		}
		return $info;
	}
	


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// 媒体文件ID
			$this->putColumn( 'media_id', $this->type('string', [ "length"=>128,   'unique'=>1] ) )
			
			// 媒体文件指纹 (用于排重)
			->putColumn( 'fingerprint', $this->type('string', ['length'=>128, 'unique'=>1]) )

			// 原始文件ID ( 如数值存在，则表示文件为处理后的文件 )
			->putColumn( 'origin_id', $this->type('string', ["length"=>128,  'index'=>1] ))

			// 媒体文件名称 ( 标题 )
			->putColumn( 'title', $this->type('string', ['length'=>128] ) )

			// 媒体文件类型 image/video/audio/text/html/css/js/word/excel/ppt/pdf/zip/json/page/unknown
			->putColumn( 'type', $this->type('string', [ "null"=>false,  'index'=>1,  'length'=>128] ) )

			// 媒体文件类型 ( MIME Type )
			->putColumn( 'mimetype', $this->type('string', ['index'=>1,  'length'=>128] ) )

			// 媒体文件后缀
			->putColumn( 'ext', $this->type('string', [ 'index'=>1,  'length'=>20] ) )

			// 对象路径
			->putColumn( 'path', $this->type('string', [ 'index'=>1, 'length'=>128] ) )

			//CDN
			->putColumn( 'cdn', $this->type('string', [ 'index'=>1, 'length'=>400] ) )

			// 缩略图(封皮) 路径 ( 300 X 225 )
			->putColumn( 'small', $this->type('string', ['length'=>128] ) )

			// 缩略图标 (封皮) 路径 ( 64 X 64 )
			->putColumn( 'tiny', $this->type('string', ['length'=>128] ) )

			// 扩展信息 
			->putColumn( 'extra', $this->type('text', ['json'=>true] ) )		
			
			// 自定义查询条件
			->putColumn( 'param', $this->type('string', ['length'=>128,'index'=>1]) )

			// 是否隐藏
			->putColumn( 'hidden', $this->type('boolean', ['default'=>false, 'index'=>1]) )

			// 存储引擎
			->putColumn( 'storage',  $this->type('string', ['length'=>128,'index'=>1, 'default'=> 'local']) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


	function url_media_id( $url ) {
		$param_string = "$url";
		// MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return hash('md4',  $param_string);   
	}


	function fingerprint_media_id( $fingerprint ) {

		$param_string = "$fingerprint";
		// MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return hash('md4',  $param_string);   
	}

	function fingerprint( $blob  ) {
		return hash('md4',  $blob);
	}

	function media_id( $file_name ) {

		$nextid = $this->nextid();
		$param_string = "[{$nextid}]{$file_name}";

		// MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return hash('md4',  $param_string);   
	}

	function getPath( $media_id, $ext ) {
		$folder = date('/Y/m/d');
		return "{$folder}/{$media_id}.{$ext}";
	}


	function getPathHash( $filename ) {
		$hash = hash('md4',  $filename );
		$folder = "/".substr( $hash, 0,2) . "/" . substr( $hash, 2,2);
		return "{$folder}/{$filename}";
	}


	function getExt( $file_name ) {
		// $arr = explode('.',$file_name);
		// $ext = strtolower(array_pop($arr));
		$ext = strtolower(substr($file_name, -3));
		if ( $ext === 'peg' ) {
			$ext = "j$ext";
		} else if ( $ext === 'ocx' ) {
			$ext = 'docx';
		} else if ( $ext === 'lsx' ) {
			$ext = 'xlsx';
		} else if ( $ext === 'ptx' ) {
			$ext = 'pptx';
		}
		return $ext;

		// return $ext;
	}

	function tmpName( $name ) {
		$ext = $this->getExt($name);
		$dir = sys_get_temp_dir() . "/". date('Y-m-d');
		if ( !is_dir($dir) ) {
			mkdir($dir);
		}
		return $dir. "/". hash('md4',  $name) . ".{$ext}";
	}



	function format( & $rs ) {

		if ( is_array($rs['extra']) ) {
			$rs['width'] = $rs['extra']['width'];
			$rs['height'] = $rs['extra']['height'];
		}

		if ( isset( $rs['media_id']) ) {
			$rs['id'] =$rs['media_id'];
		}

		if ( isset( $rs['path']) ) {

			if ( $this->options['private'] === true ) {

				$rs['url'] = $this->privateURL($rs['path']);
				$rs['origin'] = $this->privateURL($rs['path']);
				
				$info = $this->stor->get($rs['path']);
				$rs['local'] = $info['local'];

			} else {
				$info = $this->stor->get($rs['path']);
				$rs['origin'] = $info['origin'];
				$rs['url'] = $info['url'];
				$rs['local'] = $info['local'];
			}
		}

		if ( isset( $rs['small']) ) {

			if ( $this->options['private'] === true ) {
				$rs['small'] = $this->privateURL($rs['small']);
			} else {

				$info = $this->stor->get($rs['small']);
				$rs['small'] = $info['url'];
			}
		}

		if ( isset( $rs['tiny']) ) {

			if ( $this->options['private'] === true ) {
				$rs['tiny'] = $this->privateURL($rs['tiny']);
			} else {
				$info = $this->stor->get($rs['tiny']);
				$rs['tiny'] = $info['url'];
			}
		}

		if ( empty($rs['title'])) {
			$rs['title'] = $this->guessTitle( $rs['local'], $rs['mimetype'] );

			if ( empty($rs['title']) )  {
				$rs['title'] = '未命名';
			}
		}

		return $rs;
	}


	function formatAsVideo( & $rs ) {

		if ( is_array($rs['extra']) ) {
			$rs['width'] = $rs['extra']['width'];
			$rs['height'] = $rs['extra']['height'];
		}

		if ( is_array($rs['extra']['cover']) ) {
			
			$rs['gif_path'] = $rs['extra']['cover']['gif'];

			if ( $this->options['private'] === true ) {
				$rs['gif'] = $this->privateURL($rs['gif_path']);
			} else {
				$gif_info = $this->stor->get($rs['gif_path']);
				$rs['gif'] = $gif_info['url'];
			}

			$rs['cover_path'] = $rs['extra']['cover']['jpg'];

			if ( $this->options['private'] === true ) {
				$rs['cover'] =  $this->privateURL($rs['cover_path']);
			} else {
				$cover_info = $this->stor->get($rs['cover_path']);
				$rs['cover'] = $cover_info['url'];
			}


			$rs['origin'] = $rs['extra']['origin'];
		}


		if ( isset( $rs['media_id']) ) {
			$rs['id'] =$rs['media_id'];
		}

		if ( isset( $rs['path']) ) {
			
			if ( $rs['origin'] == 'upload' ) {


				if ( $this->options['private'] === true ) {
					$rs['url'] = $this->privateURL($rs['path']);
					$rs['video'] = $this->privateURL($rs['path']);
					
					$info = $this->stor->get($rs['path']);
					$rs['local'] = $info['local'];

				} else {
					$info = $this->stor->get($rs['path']);
					$rs['video'] = $info['url'];
					$rs['url'] = $info['url'];
					$rs['local'] = $info['local'];
				}
				// $info = $this->stor->get($rs['path']);
				// $rs['url'] = $info['url'];
				// $rs['video'] = $info['url'];
				// $rs['local'] = $info['local'];

			} else {
				$rs['url'] = $rs['path'];
				$rs['video'] = $rs['path'];
				$rs['code'] = $rs['extra']['code'];
			}
		}

		if ( isset( $rs['small']) && $rs['origin'] == 'upload' ) {

			if ( $this->options['private'] === true ) {
				$rs['small'] = $this->privateURL($rs['small']);
			} else {
				$info = $this->stor->get($rs['small']);
				$rs['small'] = $info['url'];
			}
		}

		if ( isset( $rs['tiny']) && $rs['origin'] == 'upload' ) {

			if ( $this->options['private'] === true ) {
				$rs['tiny'] = $this->privateURL($rs['tiny']);
			} else {
				$info = $this->stor->get($rs['tiny']);
				$rs['tiny'] = $info['url'];
			}
		}


		// if ( isset( $rs['small']) && $rs['origin'] == 'upload' ) {
		// 	$info = $this->stor->get($rs['small']);
		// 	$rs['small'] = $info['url'];
		// }

		// if ( isset( $rs['tiny']) && $rs['origin'] == 'upload' ) {
		// 	$info = $this->stor->get($rs['tiny']);
		// 	$rs['tiny'] = $info['url'];
		// }

		if ( empty($rs['code']) ) {
			$rs['code'] =
				'<video id="my-video" class="video-js" controls preload="auto" 
					 width="'.$rs['width'].'" height="'.$rs['height'].'" 
					 poster="'.$rs['cover'].'" data-setup="{}">
					<source src="'.$rs['url'].'" type="'.$rs['mimetype'].'">
					<p class="vjs-no-js">未加载编辑器</p>
				</video>';
		}


		if ( empty($rs['title'])) {
			$rs['title'] = $this->guessTitle( $rs['local'], $rs['mimetype'] );

			if ( empty($rs['title']) )  {
				$rs['title'] = '未命名';
			}
		}

		return $rs;
	}



	function formatAsFile( & $rs, $icons=[] ) {

		if ( isset( $rs['media_id']) ) {
			$rs['id'] =$rs['media_id'];
		}

		if ( empty($icons) ) {
			$icons = $this->defaultIcons;
		}

		$ext = 'unknown';
		if ( isset( $rs['ext']) ) {
			$ext= trim($rs['ext']);
		}

		if ( array_key_exists('path', $rs )) {
			// $info = $this->stor->get($rs['path']);
			// $rs['origin'] = $info['origin'];
			// $rs['url'] = $info['url'];
			// $rs['local'] = $info['local'];

			if ( $this->options['private'] === true ) {
				$rs['url'] = $this->privateURL($rs['path']);
				$rs['origin'] = $this->privateURL($rs['path']);
				
				$info = $this->stor->get($rs['path']);
				$rs['local'] = $info['local'];

			} else {
				$info = $this->stor->get($rs['path']);
				$rs['origin'] = $info['origin'];
				$rs['url'] = $info['url'];
				$rs['local'] = $info['local'];
			}


			$ext= end(explode('.', $rs['path']));
		}

		if ( !isset($icons[$ext]) ) {
			$ext = 'unknown';
		}

		if ( array_key_exists('small', $rs )) {
			$rs['small'] = $icons[$ext];
		}

		if ( array_key_exists( 'tiny', $rs ) ) {
			$rs['tiny'] = $icons[$ext];
		}

		if ( empty($rs['title'])) {
			$rs['title'] = $this->guessTitle( $rs['local'], $rs['mimetype'] );

			if ( empty($rs['title']) )  {
				$rs['title'] = '未命名';
			}
		}
		return $rs;
	}



	/**
	 * 猜测文件名
	 * @param  [type] $rs [description]
	 * @return [type]     [description]
	 */
	function guessTitle( $local, $mimetype ) {

		// 字体文件
		if ( $mimetype == 'application/x-font-ttf' ){

			if( file_exists($local) ) {
				$name = Utils::getFontName( $local );
				return $name ;
			}

		}

		return null;

	}	


	/**
	 * 抓取文件内容 
	 * @param  [type] $mixurl  [description]
	 * @param  [type] $content [description]
	 * @return boolean true / false
	 */
	function getContent( $mixurl, & $content ) {

		if( substr($mixurl, 0, 4) == 'http' || is_readable($mixurl) ) {
			$content = file_get_contents($mixurl);
		} else {
			$location = Utils::getLocation();
			$home = Utils::getHome( $location );
			$content = file_get_contents($home.$mixurl);
		}

		if ( $content  === false ) {
			return false;
		}

		return true;
	}


	function copy( $mixurl, $dstpath, $replace= true ) {
	
		if ( $replace === false && file_exists($dstpath) ) {
			return -1;
		}

		$this->getContent($mixurl, $content);
		$resp = file_put_contents( $dstpath, $content );

		if ( $resp === false ){
			return -2;
		}

		return 1;
	}


	function copyText( $option, $dstpath, $replace = true) {
		
		if ( $replace === false && file_exists($dstpath) ) {
			return -1;
		}

		$this->text($option, $content);
		$resp = file_put_contents( $dstpath, $content );
		if ( $resp === false ){
			return -2;
		}

		return 1;
	}

	function copyQrcode( $option, $dstpath, $replace = true) {
		
		if ( $replace === false && file_exists($dstpath) ) {
			return -1;
		}

		$this->qrcode($option, $content);
		$resp = file_put_contents( $dstpath, $content );
		if ( $resp === false ){
			return -2;
		}

		return 1;
	}


	function resize( $origin_media_id, $width, $height ) {
		$origin = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$origin_media_id]);
		if ( empty($origin) ) {
			throw new Excp("原图片不存在 ( $origin_media_id )", 400 , ['origin_media_id'=>$origin_media_id]);
		}

		return $this->_resize($origin, $width, $height );
	}


	function resizeByPath( $path, $width, $height ) {
		$origin = $this->getLine("WHERE path=? LIMIT 1", ['*'], [$path]);
		if ( empty($origin) ) {
			throw new Excp("原图片不存在 ( $path )", 400 , ['path'=>$path]);
		}

		return $this->_resize($origin, $width, $height );
	}




	/**
	 * 调整图片大小
	 * @param  [type] $origin [description]
	 * @param  [type] $width  [description]
	 * @param  [type] $height [description]
	 * @return [type]         [description]
	 */
	private function _resize( $origin, $width, $height ) {

		$media_id = $this->media_id( $origin['path'] . "_{$width}_{$height}" ); 
		$ext = !empty($ext)? trim($ext): $this->getExt( $origin['path'] );
		$dest = $this->getPath( $media_id, $ext );
		$width = intval( $width);
		$height = intval( $height );

		$fstat = $this->stor->resize( $origin['path'], $dest, ['width'=>$width, 'height'=>$height]);
		$extra = $origin['extra'];
		$extra['width'] = $fstat['width'];
		$extra['height'] = $fstat['height'];
		$extra['resize'] = true;
		$extra['origin'] = $origin['path'];
		$extra['origin_id'] = $origin['media_id'];;

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $dest,
			"small" => $origin['small'],
			"tiny" =>  $origin['tiny'],
			"mimetype"=> $fstat['mime'],
			"type" => "image",
			"origin_id" => $origin['media_id'],
			"extra" => $extra
		];

		$rs = $this->create($data);
		return $this->format($rs);
	}


	function crop( $origin_media_id, $x, $y, $width, $height ) {

		$origin = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$origin_media_id]);
		if ( empty($origin) ) {
			throw new Excp("原图片不存在 ( $origin_media_id )", 400 , ['origin_media_id'=>$origin_media_id]);
		}

		return $this->_crop( $origin, $x, $y, $width, $height );
	}



	function cropByPath( $path, $x, $y, $width, $height ) {

		$origin = $this->getLine("WHERE path=? LIMIT 1", ['*'], [$path]);
		if ( empty($origin) ) {
			throw new Excp("原图片不存在 ( $path )", 400 , ['path'=>$path]);
		}

		return $this->_crop( $origin, $x, $y, $width, $height );
	}


	/**
	 * 裁切图片
	 * @param  [type] $origin_media_id [description]
	 * @param  [type] $x               [description]
	 * @param  [type] $y               [description]
	 * @param  [type] $width           [description]
	 * @param  [type] $height          [description]
	 * @return [type]                  [description]
	 */
	private function _crop( $origin, $x, $y, $width, $height ) {

		$media_id = $this->media_id( $origin['path'] . "_{$x}_{$y}_{$width}_{$height}" ); 
		$ext = !empty($ext)? trim($ext): $this->getExt( $origin['path'] );
		$dest = $this->getPath( $media_id, $ext );
		$width = intval( $width);
		$height = intval( $height );

		$fstat = $this->stor->crop( $origin['path'], $dest, [
			'width'=>$width, 
			'height'=>$height,
			'x' => intval($x),
			'y' => intval($y)
		]);
		$extra = $origin['extra'];
		$extra['width'] = $fstat['width'];
		$extra['height'] = $fstat['height'];
		$extra['crop'] = true;
		$extra['origin'] = $origin['path'];
		$extra['origin_id'] = $origin['media_id'];

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $dest,
			"small" => $origin['small'],
			"tiny" =>  $origin['tiny'],
			"mimetype"=> $fstat['mime'],
			"type" => "image",
			"origin_id" => $origin['media_id'],
			"extra" => $extra
		];

		$rs = $this->create($data);
		return $this->format($rs);
	}


	function rm( $media_id ) {
		$media = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$media_id]);
		if ( empty($media) ) {
			throw new Excp("媒体资源不存在 ( $media_id )", 400 , ['media_id'=>$media_id]);
		}
		return $this->_rm( $media );
	}


	function rmByPath( $path ) {
		$media = $this->getLine("WHERE path=? LIMIT 1", ['*'], [$path]);
		if ( empty($media) ) {
			throw new Excp("媒体资源不存在 ( $path )", 400 , ['path'=>$path]);
		}
		return $this->_rm( $media );
	}


	/**
	 * 删除媒体资源
	 * @param  [type] $media [description]
	 * @return [type]        [description]
	 */
	private function _rm( $media ) {
		$this->stor->remove( $media['path']);
		return $this->remove($media['media_id'], 'media_id');
	}


	function privateURLByID( $media_id , $secret=null, $appid=null) {
		$media = $this->getLine("WHERE media_id=? LIMIT 1", ['*'], [$media_id]);
		if ( empty($media) ) {
			throw new Excp("媒体资源不存在 ( $media_id )", 400 , ['media_id'=>$media_id]);
		}

		return  $this->_privateURL( $media['path'], $secret, $appid );
	}


	/**
	 * 获取私有文件访问地址
	 * 
	 * @param  string $path   文件目录
	 * @param  string $secret XpmSE secret 
	 * @param  string $appid  XpmSE appid
	 * @return string url
	 */
	function privateURL( $path , $secret=null, $appid=null) {
		$appid = !empty($appid) ? $appid : $this->options['appid'];
		$secret = !empty($secret) ? $secret : $this->options['secret'];

		// echo "==== pirvate ====";
		// print_r($this->options);
		// echo "==== pirvate ====\n";
		return  $this->_privateURL( $path, $secret, $appid );
	}
	

	// Token 鉴权许可
	private function _privateURL( $path, $secret=null, $appid=null ) {

		$sc = new \Xpmse\Secret;
		$info = $this->stor->get($path);
		$sign = $sc->signature([
			"path" => $path,
			"mime" => $info['mime']
		], $secret, $appid);

		$sign['mime'] = $info['mime'];

		
		return $info['origin'] . '?' . http_build_query($sign) . '&';
	}


	
	/**
	 * 获取文件上传API路由通道 (用于上传文件到其他XpmSE实例中)
	 * @param  string $action 控制器 (@see 核心代码 /controller/mina/uploader.class.php )
	 * @param  string $query  GET 参数
	 * @param  string $host   XpmSE实例地址
	 * @param  string $secret XpmSE实例的 Secret
	 * @param  string $appid  XpmSE实例的 Appid
	 * 
	 * @return string url /_a/mina/uploader/briage API路由地址
	 */
	public static function briage( $action, $query, $host, $secret, $appid ) {

		$cache = new Cache([
			"prefix" => '_mediaStorageBriage:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);

		$time = time() . rand(10000,99999);
		
		$cache->set('host' . $time, $host);
		$cache->set('appid'. $time, $appid);
		$cache->set('secret'. $time, $secret);


		return "/_a/mina/uploader/briage?_action=$action&_time=$time&" . http_build_query($query);
	}


	/**
	 * 关闭文件上传API路由通道
	 * 调用 briage() 任务处理完成后，需要调用 briageclose() 关闭通道。
	 *（否则该通道一直有效)
	 * @return 
	 */
	public static function briageclose() {
		$cache = new Cache([
			"prefix" => '_mediaStorageBriage:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);

		$cache->delete('host');
		$cache->delete('appid');
		$cache->delete('secret');
	}




	/**
	 * 制作二维码/小程序码图片
	 * @param  array $params 参数表
	 *         string $params["text"]        二维码内容
	 *         string $params["type"]        二维码类型 默认 url 网址, url: 网址 wxapp: 小程序码 wechat: 带参二维码(暂未实现)
	 *            int $params["appid"]       微信应用 appid
	 *            int $params["secret"]      微信应用 secret
	 *            int $params["config"]      XpmSE配置项目ID (填写这个无需填写 appid/secret)
	 *            int $params["width"]       二维码宽度
	 *            int $params["logo"]        嵌入的LOGO 图标地址 ( url 有效 )
	 *            int $params["logowidth"]   LOGO 宽度 
	 *         string $params["color"]       前景色颜色代码 RGBA格式 "rgba(0,0,0,1)"
	 *         string $params["background"]  背景颜色代码 RGBA格式 "rgba(254,254,254,0.6)" 默认 rgba(255,255,255,0)	 
	 * 
	 * @return blob $image
	 */
	function qrcode( $params, & $resp ) {

		$type = !empty($params['type']) ? $params['type'] : 'url'; 

		if ( $type == 'wxapp' ) {  // 小程序码

			if ( empty($params['appid']) || empty($params['secret']) ) {
				$conf = Utils::getConf();
				$groups = $conf['_groups'];
				$map  = $conf['_map'];

				if ( empty($params['config']) && empty($params['appid']) ) {
					throw new Excp('参数错误', 402, ['params'=>$params]);
				}

				if ( !empty($params['appid']) ){
					$cfg  = $map[$params['appid']];
				} else {
					$cfg  = $groups[$params['config']];
				}


				if ( !is_array($cfg) || $cfg['type'] != 3 ) {
					throw new Excp('参数错误', 402, ['params'=>$params]);
				}
				$params['appid'] = $cfg['appid'];
				$params['secret'] = $cfg['secret'];
			}

			if ( empty($params['appid']) || empty($params['secret'])) {
				throw new Excp('参数错误', 402, ['params'=>$params]);
			}

			$color =  empty($params['color']) ?  [ 0,  0, 0, 1] : explode(',',$params['color']);
			$color = [
				'r'=>$color[0], 
				'g'=>$color[1], 
				'b'=>$color[2]
			];

			$page = $params['text'];
			$size =  $params['width'];
		
			$wxapp = new \Xpmse\Wxapp(["appid"=>$params['appid'], "secret"=>$params['secret']]);
			$wxres = $wxapp->getWxacode($page, $size, $color);
			$resp = $wxres['body'];
			return $wxres['body'];

		} else if ( $type == 'wechat' ) {  // 带参数二维码

			if ( empty($params['appid']) || empty($params['secret']) ) {
				$conf = Utils::getConf();
				$groups = $conf['_groups'];
				$map  = $conf['_map'];

				if ( empty($params['config']) && empty($params['appid']) ) {
					throw new Excp('参数错误', 402, ['params'=>$params]);
				}

				if ( !empty($params['appid']) ){
					$cfg  = $map[$params['appid']];
				} else {
					$cfg  = $groups[$params['config']];
				}

				if ( !is_array($cfg) ) {
					throw new Excp('参数错误', 402, ['params'=>$params]);
				}

				$params['appid'] = $cfg['appid'];
				$params['secret'] = $cfg['secret'];
			}

			if ( empty($params['appid']) || empty($params['secret'])) {
				throw new Excp('参数错误', 402, ['params'=>$params]);
			}
		}

		$option = [
			'text' => $params['text'],
			'size' => $params['width'],
			'padding' => !empty($params['padding']) ? $params['padding'] : 0,
			'label' => isset($params['label']) ? $params['label'] : null,
			'fontsize' => !empty($params['fontsize']) ? $params['fontsize'] : 14,
			'color' =>  str_replace(')',str_replace('rgba(', '', $params['color'])),
			'background' => str_replace(')',str_replace('rgba(', '', $params['background'])),
			'font' =>Utils::getFontPath(1),
			'logo' => $params['logo'],
			'logosize' => $params['logowidth']
		];

		$option['color'] = empty($option['color']) ?  [ 0,  0, 0, 1] : explode(',',$option['color']);
		$option['background']= empty($option['background']) ?  [ 255,  255, 255, 0] : explode(',',$option['background']);

		$option['color'] = [
			'r'=>$option['color'][0], 
			'g'=>$option['color'][1], 
			'b'=>$option['color'][2], 
			'a'=>$option['color'][3]
		];

		$option['background'] = [
			'r'=>$option['background'][0], 
			'g'=>$option['background'][1], 
			'b'=>$option['background'][2], 
			'a'=>$option['background'][3]
		];

		$qr = new QrCode();
		$qr ->setWriterByName('png')
		    ->setText($option['text'])
		    ->setSize($option['size'])
		    ->setMargin( $option['padding'] )
		    ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
		    ->setForegroundColor($option['color'])
		    ->setBackgroundColor($option['background'])
		    ->setValidateResult(false);

		if ( !empty($option['label']) ) {
			$qr->setLabel(
		    	$option['label'], $option['fontsize'],  
		    	Utils::getFontPath(1), 
		    	LabelAlignment::CENTER );
		}
			
		if ( !empty($option['logo']) ) {

			$logo = $option['logo'];
			$logosize = $option['logosize'];

			$logoBlob = null;
			if( substr($logo, 0, 4) == 'http' || is_readable($logo) ) {
				$logoBlob = file_get_contents($logo);
			} else {
				$location = Utils::getLocation();
				$home = Utils::getHome( $location );
				$logoBlob = file_get_contents($home.$logo);
			}

			if ( $logosize > $option['size'] * 0.2 ) {
				$logosize = $option['size'] * 0.2;
			}

			if ( !empty($logoBlob) ) {
				$logopath = sys_get_temp_dir() . "/" . time() . ".logo";
				file_put_contents($logopath, $logoBlob);
				$qr->setLogoPath( $logopath);
				$qr->setLogoWidth( $logosize );
			}
		}

		$resp = $qr->writeString();
	}



	/**
	 * 制作文本域图片
	 * @param  array $option 参数表
	 * 
	 *     int/string $option["width"]       文本域宽度  默认 100 像素 (auto 为根据文本自动计算)
	 *     int/string $option["height"]      文本域高度  默认 100 像素 (auto 为根据文本自动计算)
	 *            int $option["size"]        字体大小
	 *         string $option["text"]        文本正文
	 *         string $option["font"]        字体名称 
	 *         string $option["color"]       颜色代码 RGBA格式 "rgba(0,0,0,1)"
	 *         string $option["background"]  背景颜色代码 RGBA格式 "rgba(254,254,254,0.6)" 默认 rgba(255,255,255,0)
	 *         string $option["type"]        排列方式 默认 horizontal 横排 horizontal  竖排  vertical
	 *         string $option["dir"]         文字方向 默认 ltr 左 → 右    ltr: 左 → 右 rtl 左 ← 右
	 *         string $option["line"]        文字行高 与字体大小对比值  横排默认 1.5 竖排 2.0
	 *         string $option["space"]       文字间距 与字体大小对比值  横排默认 0.2
	 * 
	 * @return Imagick $image
	 */
	function text( $option, & $image ) {

		$_DIR = [
			"ltr" => \imagick::GRAVITY_NORTHWEST,  // 左 → 右
			"rtl" => \Imagick::GRAVITY_NORTHEAST   // 左 ← 右
			// "north" => \Imagick::ALIGN_RIGHT
		];

		$image = new Imagick();
		$w = !empty($option['width']) ? $option['width'] : 100;
		$h = !empty($option['height']) ? $option['height'] : 100;
		$size = $option['size'];
		$text = $option['text'];
		$color = $option['color'];
		$line = $option['line'];
		$space= $option['space'];
		$type = !empty($option['type']) ? $option['type'] : 'horizontal';  // 横排 horizontal  竖排  vertical

		$background = !empty($option['background']) ? $option['background'] : 'rgba(255,255,255,0)';
		$dir = !empty($option['dir']) ? strtolower(trim($option['dir'])) : 'ltr';
		$dir = !empty($_DIR[$dir]) ? $_DIR[$dir] : \Imagick::GRAVITY_NORTHWEST;
		


		$font = Utils::getFontPath($option['font'],1,20);

		if ( $w == 'auto' ) {
			$w = $size * mb_strlen($text) ;
		}

		if ( $h == 'auto' ) {
			$h = $size * mb_strlen($text) ;
		}

	
		$image->newImage($w, $h, new ImagickPixel($background) );
		$image->setImageFormat('png');
	

		$draw = new ImagickDraw(); 
		$draw->setFont($font);
		$draw->setFontSize($size);
		$draw->setTextEncoding('utf8');
		$draw->setFillColor(new ImagickPixel($color));
			

		if ( empty($line) ) {
			if ( $type == 'horizontal')  {
				$line = 1.5;
			} else {  // 竖排
				$line = 2.0;
			} 
		}

		if ( empty($space) ) {
			if ( $type == 'horizontal')  {
				$space = 0.2;
			} else {  // 竖排
				$space = 0.2;
			}
		}

		// 排列文字
		$this->fitText($image, $draw, $text, 
			["space"=>$space, "line"=>$line, "size"=>$size,  "width"=>$w, "height"=>$h ],
		 $dir, $type );

		$image->drawImage($draw);
		// return $image;
		// header('Content-type: image/png');
		// echo $image;
	}


	private function fitText( & $image, & $draw, $text, $options=[], $gravity=\imagick::GRAVITY_NORTHWEST, $type="horizontal" ) {

		// 拆分文字
		$chars = [];
		$len = mb_strlen($text);
		for( $i=0; $i<$len; $i++ ) {
			array_push($chars, mb_substr($text, $i, 1));
		}

		// 原点位置
		$draw->setGravity( $gravity ); 


		// 文字排版
		$len = count($chars);
		$maxHeight = $options['height'];
		$maxWidth  = $options['width'];
		$size = $options['size'];
		$width =0;  
		$height =0;
		$x=0; $y=0;
		$space = !empty($options['space']) ? floatval($options['space']) : 0.2;  // 文字间距
		$line = !empty($options['line']) ? floatval($options['line']) : 1.2;  // 行高

		if ( $gravity == \imagick::GRAVITY_NORTH ) {
			$maxWidth = $maxWidth /2;
		} else if ( $gravity == \imagick::GRAVITY_CENTER ) {
			$maxWidth = $maxWidth /2;
			$maxHeight = $maxHeight /2;
		}


		foreach ($chars as $i => $char ) {
			
			if ( $type == 'horizontal') { // 横排

				$draw->annotation( $x, $y, $char ) ;

				// 下一个个文字位置
				$x = $size + $x +  ($size * $space);
				
				if ( $x + $size >= $maxWidth || ($char == "\n" || $char == "\r") ) { // 换行
					$x=0; 
					$y = $y + $size*$line;
				}

				// 超出屏幕高度
				if ( $y + $size > $maxHeight ) {
					break;
				}

			} else {  // 竖排

				$draw->annotation( $x, $y, $char ) ;

				// 下一个个文字位置
				$y = $size + $y +  ($size * $space );

				// 超出屏幕高度
				if ( $y + $size > $maxHeight || ($char == "\n" || $char == "\r") ) {
					$y =0;
					$x = $x + $size*$line;
				}

				// 超出屏幕宽度
				if ( $x + $size > $maxWidth ) {
					break;
				}

			}
		}

	}



	/**
	 * 追加写入文件 （ 一般用于分段上传
	 * @param  [type]  $file_name [description]
	 * @param  [type]  $path      [description]
	 * @param  integer $hidden    [description]
	 * @return [type]             [description]
	 */
	function appendFile( $file_name, $blob=null, $rest=false, $ext=null, $hidden=0 ) {

		if ( empty($blob) ) {
			$blob = file_get_contents( $file_name );
		}

		if ( $blob  === false) {
			throw new Excp('读取文件失败', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		if ( empty( $ext) ) {
			$ext = $this->getExt($file_name);
		}



		// 将文件追加到存储空间
		$media_id = $this->url_media_id($file_name);
		$path = $this->getPath( $media_id, $ext );

		// REST
		if ( $rest == true  ) {
			try { $this->rmByPath($path); } catch(Excp $e){}
		}

		// echo "将文件追加到存储空间 {$path} \n" ;

		$fstat = $this->stor->append( $path, $blob );

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $path,
			"title" => null,
			"small" => null,
			"tiny" => null,
			"mimetype"=> $fstat['mime'],
			"type" => "file",
			"ext" => $ext,
			"hidden" => $hidden,
			"extra" => $extra
		];

		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->formatAsFile($rs);

	}



	/**
	 * 上传文件
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	function uploadFile( $file_name, $ext=null, $override=true, $hidden=0) {

		$uri = parse_url($file_name);
		if ( $uri['scheme'] != 'http' &&  $uri['scheme'] != 'https') {

			if ( !file_exists($file_name) ) {
				throw new Excp('文件不存在', 404, ['file_name'=>$file_name, 'ext'=>$ext]);
			}

			if ( !is_readable($file_name) ) {
				throw new Excp('文件无法访问', 403, ['file_name'=>$file_name, 'ext'=>$ext]);
			}

		} else {
			$media_id = $this->url_media_id( $file_name ); 
			
			if ( $override === false ){
				$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
				if ( !empty($rs) ) {
					return $rs;
				}
			}
		}


		$blob = file_get_contents( $file_name );
		if ( $blob  === false) {
			throw new Excp('读取文件失败', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}


		if ( $this->options['fingerprint'] ){
			$size = strlen($blob);
			if ( $size <= 256 ){
				$fingerprint = $this->fingerprint( $blob );
			} else {
				$from = intval($size/2);
				$fingerprint = $this->fingerprint( substr($blob, $from, 128) );
			}
		} else { 
			$fingerprint = uniqid();
		}



		$media_id = $this->getVar('media_id', 'WHERE fingerprint=?', [$fingerprint]);
		if ( !empty($media_id) ){
			$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
			return $this->formatAsFile($rs);
		}

		// 将文件上传到存储空间
		$media_id = $this->fingerprint_media_id( $fingerprint );
		$ext = !empty($ext)? trim($ext): $this->getExt( $file_name );
		$path = $this->getPath( $media_id, $ext );
		$fstat = $this->stor->upload( $path, $blob );
	

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $path,
			"title" => null,
			"small" => null,
			"tiny" => null,
			"mimetype"=> $fstat['mime'],
			"type" => "file",
			"ext" => $ext,
			"hidden" => $hidden,
			"extra" => $extra,
			"cdn" => $cdnUrl,
		];
		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->formatAsFile($rs);
	}



	public function uploadFileBlob( $blob, $ext, $extra=[], $hidden=0 ) {
		if ( $this->options['fingerprint'] ){
			$size = strlen($blob);
			if ( $size <= 256 ){
				$fingerprint = $this->fingerprint( $blob );
			} else {
				$from = intval($size/2);
				$fingerprint = $this->fingerprint( substr($blob, $from, 128) );
			}
		} else { 
			$fingerprint = uniqid();
		}



		$media_id = $this->getVar('media_id', 'WHERE fingerprint=?', [$fingerprint]);
		if ( !empty($media_id) ){
			$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
			return $this->formatAsFile($rs);
		}


		// 将文件上传到存储空间
		$media_id = $this->fingerprint_media_id( $fingerprint );
		$path = $this->getPath( $media_id, $ext );
		$fstat = $this->stor->upload( $path, $blob );
	
		
		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $path,
			"title" => null,
			"small" => null,
			"tiny" => null,
			"mimetype"=> $fstat['mime'],
			"type" => "file",
			"ext" => $ext,
			"hidden" => $hidden,
			"extra" => $extra
		];

		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->formatAsFile($rs);

	}


	function saveVideoUrl( $url, $hidden=0 ) {

		$info = $this->getVideoInfo( $url );
		$media_id = $this->url_media_id( $url ); 

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $info['url'],
			"small" => $info['small'],
			"tiny" => $info['tiny'],
			"mimetype"=> "site/{$info['origin']}",
			"type" => "video",
			"hidden" => $hidden,
			"extra" => array_merge($info['extra'],[
				"code"=>$info['code']
			])
		];

		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->formatAsVideo($rs);
	}




	/**
	 * 根据视频地址，读取视频格式
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 */
	function getVideoInfo( $url ) {

		$codetpls = [
			"youku"=>[
				"reg"=>["/.*youku.com\/.*id_([0-9a-zA-Z]+)[=]*/"],
				"fetch" => true,
				"code_reg" => "/<iframe height=([0-9\\\"\']+) width=([0-9\\\"\']+) src='http\:\/\/player.youku.com\/embed\/({{1}})=='/",
				"code" => "<iframe height={{1}} width={{2}} src='//player.youku.com/embed/{{3}}==' frameborder=0 allowfullscreen></iframe>",
				"small" => '/static/defaults/images/youku_320v2.jpg',
				"tiny" => '/static/defaults/images/youku_64.jpg'
			],
			"qq"=>[
				"reg"=>[
					"/.*qq.com\/x\/cover\/([0-9a-zA-Z]+)\/([0-9a-zA-Z]+)\.html/",
					"/.*qq.com\/x\/(page)\/([0-9a-zA-Z]+)\.html/"
				],
				"fetch" => true,
				"code_reg" => "/({{2}})/",
				"code" => '<iframe frameborder="0" width="640"  src="//v.qq.com/iframe/player.html?vid={{1}}&tiny=0&auto=0" allowfullscreen></iframe>',
				"small" => '/static/defaults/images/qq_320v2.jpg',
				"tiny" => '/static/defaults/images/qq_64.jpg'
			]
		];

		
		$resp = [];
		foreach ($codetpls as $origin => $tpl ) {
			$regs = $tpl['reg'];
			$code = $tpl['code'];
			$width = 640;
			$height = 360;
			foreach ($regs as $reg) {
				
				if ( preg_match( $reg, $url, $match) ) {

					if ( $tpl['fetch'] == true ) {
						$code_reg = $tpl['code_reg'];
						foreach ($match as $idx => $val ) {
							$code_reg = str_replace('{{'.$idx.'}}', $val, $code_reg);
						}

						$content = file_get_contents($url);
						

						if ( preg_match($code_reg, $content, $match_contents) ){
							foreach ($match_contents as $idx => $val ) {
								$code = str_replace('{{'.$idx.'}}', $val, $code);
							}
						}

					} else {
						foreach ($match as $idx => $val ) {
							$code = str_replace('{{'.$idx.'}}', $val, $code);
						}
					}

					if ( preg_match("/height=([0-9]+)/", $code, $match) ) {
						$height = $match[1];
					}

					if ( preg_match("/width=([0-9]+)/", $code, $match) ) {
						$width = $match[1];
					}

					$resp['small'] = $tpl['small'];
					$resp['tiny'] = $tpl['tiny'];
					$resp['width'] = $width;
					$resp['height'] = $height;
					$resp['code'] = $code;
					$resp['origin'] = $origin;
					$resp['url'] = $resp['video'] = $url;
					$resp['extra'] = [
						"origin" => $origin,
						"width" => $width,
						"height" => $height
					];
					break;
				}
			}
		}

		if ( empty($resp) ) {
			throw new Excp("无法获取视频信息", 500, ["file_name"=>$file_name,  'media_id'=>$media_id]);
		}


		return $resp;
	}



	/**
	 * 截取视频文件，并生成缩写略图
	 * @param  [type] $media_id  [description]
	 * @param  [type] $file_name [description]
	 * @param  [type] $ext       [description]
	 * @return [type]            [description]
	 */
	function getVideoCover( $file_name,  $media_id = null, $from=null, $to=null ) {

		$media_id = !empty($media_id) ?  $media_id : $this->media_id($file_name); 

		$path_cover_gif = $this->getPath( $media_id . "_cover",  'gif' );
		$path_cover_jpg = $this->getPath( $media_id . "_cover",  'jpg' );
		$tmp_name_gif = $this->tmpName( "{$media_id}_cover.gif" );
		$tmp_name_jpg = $this->tmpName( "{$media_id}_cover.jpg" );

		try {

			$ffprobe = \FFMpeg\FFProbe::create();
			$duration = $ffprobe
							->format($file_name)
							->get('duration');

			

			$dimension = $ffprobe
							->streams($file_name)
							->videos()
							->first()
							->getDimensions();

		} catch( \Exception $e ){
			throw new Excp("无法获取视频信息", 500, ["file_name"=>$file_name,  'media_id'=>$media_id]);
		}

		$width = $dimension->getWidth();
		$height = $dimension->getHeight();


		if ( $from == null ) {
			$from = 2;
			if ( $from > $duration ) {
				$from = $duration;
			}
		}


		// 制作封皮
		try {

			$ffmpeg = \FFMpeg\FFMpeg::create();
			$video = $ffmpeg->open($file_name);
			$video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($from) )
				  ->save($tmp_name_jpg);

			$video->gif(
						\FFMpeg\Coordinate\TimeCode::fromSeconds($from), 
						new \FFMpeg\Coordinate\Dimension(320, $height),
						0.5)
				  ->save( $tmp_name_gif );


		} catch( \Exception $e ) {

			throw new Excp("生成主题图失败", 500, ["file_name"=>$file_name,  'media_id'=>$media_id]);
		}

		// 上传到存储区域
		$fstat = $this->stor->upload( $path_cover_jpg, file_get_contents($tmp_name_jpg) );
		$fstat = $this->stor->upload( $path_cover_gif, file_get_contents($tmp_name_gif) );

		@unlink($tmp_name_jpg);
		@unlink($tmp_name_gif);

		return ["jpg"=>$path_cover_jpg, "gif"=>$path_cover_gif];

	}



	/**
	 * 上传视频
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	function uploadVideo( $file_name, $cover_path=null,  $ext=null, $override=true, $hidden=0 ) {
		
		if ( !file_exists($file_name) ) {
			throw new Excp('文件不存在', 404, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		if ( !is_readable($file_name) ) {
			throw new Excp('文件无法访问', 403, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		$blob = file_get_contents( $file_name );
		if ( $blob  === false) {
			throw new Excp('读取文件失败', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		if ( $this->options['fingerprint'] ){
			$size = strlen($blob);
			if ( $size <= 256 ){
				$fingerprint = $this->fingerprint( $blob );
			} else {
				$from = intval($size/2);
				$fingerprint = $this->fingerprint( substr($blob, $from, 128) );
			}
		} else { 
			$fingerprint = uniqid();
		}



		$media_id = $this->getVar('media_id', 'WHERE fingerprint=?', [$fingerprint]);
		if ( !empty($media_id) ){
			$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
			return $this->formatAsVideo($rs);
		}


		// 将文件上传到存储空间
		$media_id = $this->fingerprint_media_id( $fingerprint );
		$ext = !empty($ext)? trim($ext): $this->getExt( $file_name );
		$path = $this->getPath( $media_id, $ext );

		$fstat = $this->stor->upload( $path, $blob );
		if ( !$this->stor->is_a($path, 'video') ) {
			throw new Excp('文件类型不是视频', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		// 截取视频缩略图
		$cover = [];
		if ( empty($cover_path) ) {
			$cover = $this->getVideoCover( $file_name, $media_id );
			$cover_path = $cover['jpg'];
		}

		$extra = $this->stor->info($cover_path);
		$extra['cover'] = $cover;
		$extra['origin'] = 'upload';

		// 生成图片缩略图
		$path_16v9 = $this->getPath( $media_id . "_16v9",  'jpg' );
		$path_320 = $this->getPath( $media_id . "_320",  'jpg' );
		$path_1v1 = $this->getPath( $media_id . "_1v1",  'jpg' );
		$path_64  = $this->getPath( $media_id . "_64",  'jpg' );

		$this->stor->crop( $cover_path, $path_16v9,  ['ratio'=>4/3]);
		$this->stor->crop( $cover_path, $path_1v1,  ['ratio'=>1]);
		$this->stor->resize( $path_16v9, $path_320, ['width'=>320, 'height'=>180]);
		$this->stor->resize( $path_1v1, $path_64, ['width'=>64, 'height'=>64]);
		$this->stor->remove( $path_16v9);
		$this->stor->remove( $path_1v1);

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $path,
			"small" => $path_320,
			"tiny" => $path_64,
			"ext" => $ext,
			"mimetype"=> $fstat['mime'],
			"type" => "video",
			"hidden" => $hidden,
			"extra" => $extra
		];

		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->formatAsVideo($rs);
	}






	/**
	 * 上传图片
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	function uploadImage( $file_name, $ext=null, $override=true, $hidden=0 ) {
		
		// if ( !file_exists($file_name) ) {
		// 	throw new Excp('文件不存在', 404, ['file_name'=>$file_name, 'ext'=>$ext]);
		// }

		// if ( !is_readable($file_name) ) {
		// 	throw new Excp('文件无法访问', 403, ['file_name'=>$file_name, 'ext'=>$ext]);
		// }

		$uri = parse_url($file_name);
		if ( $uri['scheme'] != 'http' &&  $uri['scheme'] != 'https') {

			if ( !file_exists($file_name) ) {
				throw new Excp('文件不存在', 404, ['file_name'=>$file_name, 'ext'=>$ext]);
			}

			if ( !is_readable($file_name) ) {
				throw new Excp('文件无法访问', 403, ['file_name'=>$file_name, 'ext'=>$ext]);
			}
			// $media_id = $this->media_id( $file_name ); 

		} else {
			$media_id = $this->url_media_id( $file_name ); 
			
			if ( $override === false ){
				$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
				if ( !empty($rs) ) {
					return $rs;
				}
			}
		}


		$blob = file_get_contents( $file_name );

		if ( $blob  === false) {
			throw new Excp('读取文件失败', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		if ( $this->options['fingerprint'] ){
			$size = strlen($blob);
			if ( $size <= 256 ){
				$fingerprint = $this->fingerprint( $blob );
			} else {
				$from = intval($size/2);
				$fingerprint = $this->fingerprint( substr($blob, $from, 128) );
			}
		} else { 
			$fingerprint = uniqid();
		}


		$media_id = $this->getVar('media_id', 'WHERE fingerprint=?', [$fingerprint]);
		if ( !empty($media_id) ){
			$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
			return $this->format($rs);
		}



		// 将文件上传到存储空间
		$media_id = $this->fingerprint_media_id( $fingerprint ); 
		$ext = !empty($ext)? trim($ext): $this->getExt( $file_name );
		$path = $this->getPath( $media_id, $ext );

		$fstat = $this->stor->upload( $path, $blob );
		if ( !$this->stor->is_a($path, 'image') ) {
			throw new Excp('文件类型不是图片', 500, ['file_name'=>$file_name, 'ext'=>$ext]);
		}

		$extra = $this->stor->info($path);

		// 生成图片缩略图
		$path_16v9 = $this->getPath( $media_id . "_16v9",  $ext );
		$path_320 = $this->getPath( $media_id . "_320",  $ext );
		$path_1v1 = $this->getPath( $media_id . "_1v1",  $ext );
		$path_64  = $this->getPath( $media_id . "_64",  $ext );

		$this->stor->crop( $path, $path_16v9,  ['ratio'=>4/3]);
		$this->stor->crop( $path, $path_1v1,  ['ratio'=>1]);
		$this->stor->resize( $path_16v9, $path_320, ['width'=>320, 'height'=>180]);
		$this->stor->resize( $path_1v1, $path_64, ['width'=>64, 'height'=>64]);
		$this->stor->remove( $path_16v9);
		$this->stor->remove( $path_1v1);

		// 数据入库
		$data = [
			"media_id"=>$media_id,
			"path" => $path,
			"small" => $path_320,
			"tiny" => $path_64,
			"ext" => $ext,
			"mimetype"=> $fstat['mime'],
			"type" => "image",
			"fingerprint" => $fingerprint,
			"hidden" => $hidden,
			"extra" => $extra
		];

		$this->createorupdate($data);
		$rs = $this->getLine('WHERE media_id=?', ['*'], [$media_id]);
		return $this->format($rs);
	}


	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		if ( $mark_only === true ) {
			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"media_id"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `media_id`))", 
				"fingerprint"=>null
			]);

			if ( $row['deleted_at'] == $time ) {
				
				return true;
			}
			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
	}


	/**
	 * 如文件不存在则创建，如文件存在则插入
	 * @param  [type] $path   [description]
	 * @param  [type] $option [description]
	 * @return [type]         [description]
	 */
	function insert( $source, $target,  $option=[] ){

		//  处理参数
		$from = empty($option['from']) ? 0 : intval($option['from']);
		$to = empty($option['to']) ? null : intval($option['to']);
		$total = empty($option['total']) ? null : intval($option['total']);
		$type = empty($option['type']) ? 'type' : $option['type'];

		// 插入数据
		$blob = file_get_contents($source);
		$path = $this->getPathHash( $target );
		$fstat = $this->stor->insert( $path, $blob, $from, $total );

		return $fstat;
	}


}
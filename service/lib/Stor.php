<?php
// 即将废弃
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');

use \Exception as Exception;
use \Intervention\Image\ImageManagerStatic as Image;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;


class Stor {

	/**
	 * 存储引擎信息
	 * @var array 存储引擎配置信息
	 */
	private $_engine = null;

	/**
	 * 存储引擎处理对象
	 */
	private $_plugin = null;


	/**
	 * 存储引擎前缀 ( Core/APP_NAME)
	 */
	private $_prefix = null;

	private $_mimetypemap = [];
	private $_mimetype = [

		//自己加几个  
		'rss'=>'application/xml',
		'json'=>'application/json',

		//下面是网上的  
		'ez'	=> 'application/andrew-inset',  
		'hqx'	=> 'application/mac-binhex40',  
		'cpt'	=> 'application/mac-compactpro',  
		'doc'	=> 'application/msword',
		'ocx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'bin'	=> 'application/octet-stream',
		'dms'	=> 'application/octet-stream',
		'lha'	=> 'application/octet-stream',
		'lzh'	=> 'application/octet-stream',
		'exe'	=> 'application/octet-stream',
		'class'	=> 'application/octet-stream',
		'so'	=> 'application/octet-stream',
		'dll'	=> 'application/octet-stream',
		'oda'	=> 'application/oda',
		'pdf'	=> 'application/pdf',  //了解  
		'ai'	=> 'application/postscript',
		'eps'	=> 'application/postscript',
		'ps'	=> 'application/postscript',
		'smi'	=> 'application/smil',
		'smil'	=> 'application/smil',
		'mif'	=> 'application/vnd.mif',
		'xls'	=> 'application/vnd.ms-excel',
		'ppt'	=> 'application/vnd.ms-powerpoint',  //了解  
		'wbxml'	=> 'application/vnd.wap.wbxml',
		'wmlc'	=> 'application/vnd.wap.wmlc',
		'wmlsc'	=> 'application/vnd.wap.wmlscriptc',
		'bcpio'	=> 'application/x-bcpio',
		'vcd'	=> 'application/x-cdlink',
		'pgn'	=> 'application/x-chess-pgn',
		'cpio'	=> 'application/x-cpio',
		'csh'	=> 'application/x-csh',
		'dcr'	=> 'application/x-director',
		'dir'	=> 'application/x-director',
		'dxr'	=> 'application/x-director',
		'dvi'	=> 'application/x-dvi',
		'spl'	=> 'application/x-futuresplash',
		'gtar'	=> 'application/x-gtar',
		'hdf'	=> 'application/x-hdf',
		'js'	=> 'application/x-javascript',  //了解  
		'skp'	=> 'application/x-koan',
		'skd'	=> 'application/x-koan',
		'skt'	=> 'application/x-koan',
		'skm'	=> 'application/x-koan',
		'latex'	=> 'application/x-latex',
		'nc'	=> 'application/x-netcdf',
		'cdf'	=> 'application/x-netcdf',
		'sh'	=> 'application/x-sh',
		'shar'	=> 'application/x-shar',
		'swf'	=> 'application/x-shockwave-flash',  //了解  
		'sit'	=> 'application/x-stuffit',
		'sv4cpio'	=> 'application/x-sv4cpio',
		'sv4crc'	=> 'application/x-sv4crc',
		'tar'	=> 'application/x-tar',  //了解  
		'tcl'	=> 'application/x-tcl',
		'tex'	=> 'application/x-tex',
		'texinfo'	=> 'application/x-texinfo',
		'texi'	=> 'application/x-texinfo',
		't'		=> 'application/x-troff',
		'tr'	=> 'application/x-troff',
		'roff'	=> 'application/x-troff',
		'man'	=> 'application/x-troff-man',
		'me'	=> 'application/x-troff-me',
		'ms'	=> 'application/x-troff-ms',
		'ustar'	=> 'application/x-ustar',
		'src'	=> 'application/x-wais-source',
		'xhtml'	=> 'application/xhtml+xml',  //了解  
		'xht'	=> 'application/xhtml+xml',
		'zip'	=> 'application/zip',  //了解  

		'au'	=> 'audio/basic',
		'snd'	=> 'audio/basic',
		'mid'	=> 'audio/midi',
		'midi'	=> 'audio/midi',
		'kar'	=> 'audio/midi',
		'mpga'	=> 'audio/mpeg',
		'mp2'	=> 'audio/mpeg',
		'mp3'	=> 'audio/mpeg',  //了解  
		'aif'	=> 'audio/x-aiff',
		'aiff'	=> 'audio/x-aiff',
		'aifc'	=> 'audio/x-aiff',
		'm3u'	=> 'audio/x-mpegurl',
		'ram'	=> 'audio/x-pn-realaudio',
		'rm'	=> 'audio/x-pn-realaudio',
		'rpm'	=> 'audio/x-pn-realaudio-plugin',
		'ra'	=> 'audio/x-realaudio',
		'wav'	=> 'audio/x-wav',  //了解  

		'pdb'	=> 'chemical/x-pdb',
		'xyz'	=> 'chemical/x-xyz',

		'bmp'	=> 'image/bmp',  //了解  
		'gif'	=> 'image/gif',  //了解  
		'ief'	=> 'image/ief',
		'jpe'	=> 'image/jpeg',
		'jpeg'	=> 'image/jpeg',  //了解  
		'jpg'	=> 'image/jpeg',  //了解  
		'png'	=> 'image/png',  //了解  
		'tiff'	=> 'image/tiff',
		'tif'	=> 'image/tiff',
		'djvu'	=> 'image/vnd.djvu',
		'djv'	=> 'image/vnd.djvu',
		'wbmp'	=> 'image/vnd.wap.wbmp',
		'ras'	=> 'image/x-cmu-raster',
		'pnm'	=> 'image/x-portable-anymap',
		'pbm'	=> 'image/x-portable-bitmap',
		'pgm'	=> 'image/x-portable-graymap',
		'ppm'	=> 'image/x-portable-pixmap',
		'rgb'	=> 'image/x-rgb',
		'xbm'	=> 'image/x-xbitmap',
		'xpm'	=> 'image/x-xpixmap',
		'xwd'	=> 'image/x-xwindowdump',

		'igs'	=> 'model/iges',
		'iges'	=> 'model/iges',
		'msh'	=> 'model/mesh',
		'mesh'	=> 'model/mesh',
		'silo'	=> 'model/mesh',
		'wrl'	=> 'model/vrml',
		'vrml'	=> 'model/vrml',
		'css'	=> 'text/css',  //了解  
		'html'	=> 'text/html',  //了解  
		'htm'	=> 'text/html',
		'asc'	=> 'text/plain',
		'txt'	=> 'text/plain',  //了解  
		'rtx'	=> 'text/richtext',
		'rtf'	=> 'text/rtf',
		'sgml'	=> 'text/sgml',
		'sgm'	=> 'text/sgml',
		'tsv'	=> 'text/tab-separated-values',
		'wml'	=> 'text/vnd.wap.wml',
		'wmls'	=> 'text/vnd.wap.wmlscript',
		'etx'	=> 'text/x-setext',
		'xsl'	=> 'text/xml',
		'xml'	=> 'text/xml',

		'mpeg'	=> 'video/mpeg',  //了解  
		'mpg'	=> 'video/mpeg',  //了解  
		'mpe'	=> 'video/mpeg',
		'qt'	=> 'video/quicktime',
		'mov'	=> 'video/quicktime',
		'mxu'	=> 'video/vnd.mpegurl',
		'avi'	=> 'video/x-msvideo',
		'movie'	=> 'video/x-sgi-movie',

		'ice'	=> 'x-conference/x-cooltalk'
	];

	/**
	 * 根据文件类型允许的操作
	 * @var array
	 */
	private $_image_allow_function = array(
			'image/jpeg'=>array('crop'=>true, 'fit'=>true, 'resize'=>true, 'suffix'=>'jpg' ),
			'image/pjpeg' => array('crop'=>true, 'fit'=>true , 'resize'=>true , 'suffix'=>'jpg'),
			'image/png' => array('crop'=>true, 'fit'=>true  , 'resize'=>true, 'suffix'=>'png'),
			'image/x-png' => array('crop'=>true, 'fit'=>true  , 'resize'=>true, 'suffix'=>'png'),
			'image/gif' => array('crop'=>true, 'fit'=>true  , 'resize'=>true, 'suffix'=>'gif')
		);



	/**
	 * 上传信息列表
	 * @var array
	 */
	private $_list = array();

	/**
	 * 构造函数 
	 * @param string $engine 存储引擎名称
	 */
	function __construct() {

		// 数据转换
		$mem = new Mem;
		$this->_mimetypemap = array_flip($this->_mimetype );

		$name = "core";
		$path_info = dirname($_SERVER['SCRIPT_FILENAME']);
		if ( strpos($path_info, _XPMAPP_ROOT) !== false ) {
			$path =  str_replace(_XPMAPP_ROOT . '/', '',  $path_info);	
			$info = explode('/', $path);
			$name = $info[0];  // APP NAME
		}

		if( $name != "core" ) {
			$this->_prefix = $name;
		}

	}


	



	/**
	 * 保存图片
	 * @param  [type]  $wrapper [description]
	 * @param  [type]  $url     [description]
	 * @param  boolean $replace [description]
	 * @return [type]           [description]
	 */
	public function put( $wrapper, $url, $replace=true ) {
		$data = file_get_contents($url);
		if ( $data  === false ) {
			return new Err('308404', "无法读取文件", ['wrapper'=>$wrapper, 'url'=>$url, 'replace'=>$replace] );
		}

		return $this->putData( $wrapper, $data, $replace );
	}



	/**
	 * 将数据写入文件
	 * @param  [type]  $wrapper 
	 * @param  [type]  $data    文件数据
	 * @param  boolean $replace 如果文件存在是否替换，默认替换
	 * @return [type]  true / cblError  成功返回 true , 失败返回 cblError
	 */
	public function putData( $wrapper, $data, $replace=true ) {

		$w = $this->wrapperParse( $wrapper );
		$this->engine( $w['engine'] );

		if ( !$this->bucketExists( $w['bucket']) ) {
			return new Err('306404', "缺少配置信息", ['wrapper'=>$wrapper, 'data.md5'=>md5($data), 'replace'=>$replace, 'Bucket'=>$w['bucket']] );
		}


		// 检查路径
		if ( $this->_prefix != null ) {
			$path_info = dirname($w['file']);
			if ( strpos( strtolower($path_info), strtolower("/apps/{$this->_prefix}")) === false ) {
				return new Err('306503', "没有写入权限，只有应用目录可写 /apps/{$this->_prefix}", ['wrapper'=>$wrapper, "apps_path"=>"/apps/{$this->_prefix}",'data.md5'=>md5($data), 'replace'=>$replace, 'Bucket'=>$w['bucket']] );
			}
		}

		return $this->_plugin->putData( $w['bucket'], $w['file'], $data, $replace );
	}





	/**
	 * 读取文件数据
	 * @param  [type] $wrapper 
	 * @return [type] 成功返回 文件访问地址 失败返回 cblError
	 */
	public function getData( $wrapper ) {

		$w = $this->wrapperParse( $wrapper );
		$this->engine( $w['engine'] );

		if ( !$this->bucketExists( $w['bucket']) ) {
			return new Err('309404', "缺少配置信息", ['wrapper'=>$wrapper, 'Bucket'=>$w['bucket']] );
		}

		return $this->_plugin->getData( $w['bucket'], $w['file'] );

	}

	/**
	 * 读取文件访问地址
	 * @param  [type] $wrapper 
	 * @return [type] 成功返回 文件访问地址 失败返回 cblError
	 */
	public function getUrl( $wrapper ) {
		$w = $this->wrapperParse( $wrapper );
		$this->engine( $w['engine'] );

		if ( !$this->bucketExists( $w['bucket']) ) {
			return new Err('308404', "缺少配置信息", ['wrapper'=>$wrapper, 'Bucket'=>$w['bucket']] );
		}

		return $this->_plugin->getUrl( $w['bucket'], $w['file'] );
	}

	

	/**
	 * 快速返回访问地址
	 * @param  [type] $wrapper [description]
	 * @return [type]          [description]
	 */
	public static function url( $wrapper ) {
		$s = new Stor();
		return $s->getUrl($wrapper);
	}


    /**
     * 读取路径
     */
    static function path( $wrapper ) {
        $s = new Self();
        $info = $s->wrapperParse( $wrapper );
        if ( $info["engine"] != "local" ){
            return null;
        }

        return $info["file"];
    }


	/**
	 * 拷贝文件
	 * @param  [type] $src_wrapper [description]
	 * @param  [type] $dst_warpper [description]
	 * @return [type]              [description]
	 */
	public function cp( $src_wrapper, $dst_warpper, $replace = true ) {
		$data = $this->getData( $src_wrapper );
		return $this->putData( $dst_warpper, $data , $replace );
	}



	/**
	 * 移动文件
	 * @param  [type]  $src_wrapper [description]
	 * @param  [type]  $dst_warpper [description]
	 * @param  boolean $replace     [description]
	 * @return [type]               [description]
	 */
	public function mv( $src_wrapper, $dst_warpper, $replace = true ) {
		$this->cp( $src_wrapper, $dst_warpper, $replace );
		return $this->del( $src_wrapper );
	}



	/**
	 * 转换为Media结构体
	 * @param  [type] $wrapper [description]
	 * @return [type]          [description]
	 */
	public function toMedia( $wrapper, $name='media' ) {
		$url = $this->getUrl( $wrapper );
			 if ( is_a($url, '\Xpmse\Err') ) return $url;
		
		$data = $this->getData( $wrapper );
			 if ( is_a($data, '\Xpmse\Err') ) return $data;

		$mimetype = $this->mimetype($wrapper);
			 if ( is_a($data, '\Xpmse\Err') ) return $data;

		$filename = basename($url);
		return ['name'=>$name, 'filename'=>$filename, 'mimetype'=>$mimetype, 'data'=>$data ];
	}




	/**
	 * 删除文件
	 * @param  [type] $wrapper 
	 * @return [type] 成功返回 true / 失败返回 cblError
	 */
	public function del( $wrapper ) {

		$w = $this->wrapperParse( $wrapper );
		$this->engine( $w['engine'] );

		if ( !$this->bucketExists( $w['bucket']) ) {
			return new Err('307404', "缺少配置信息", ['wrapper'=>$wrapper, 'Bucket'=>$w['bucket']] );
		}

		return $this->_plugin->delete( $w['bucket'], $w['file'] );

	}

	/**
	 * 读取文件类型
	 * @param  [type] $wrapper [description]
	 * @return [type]          [description]
	 */
	public function mimetype( $wrapper ) {

		$w = $this->wrapperParse( $wrapper );
		$this->engine( $w['engine'] );

		if ( !$this->bucketExists( $w['bucket']) ) {
			return new Err('307404', "缺少配置信息", ['wrapper'=>$wrapper, 'Bucket'=>$w['bucket']] );
		}

		return $this->_plugin->mimetype( $w['bucket'], $w['file'] );

	}

	/**
	 * 读取文件Data
	 * @param  [type] $wrapper [description]
	 * @return [type]          [description]
	 */
	public function mimetypeByData( $data ) {
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		return $finfo->buffer($data);
	}




	/**
	 * 根据给定文件后缀，生成唯一文件名
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	public function genName( $suffix, $type='date' ) {
		$time = time() . rand(10000,99999);
		$year = date('Y');
		$month = date('m');
		$day = date('d');
		if ( $suffix[0] == '.' ) {
			$suffix = substr($suffix, 1, strlen($suffix));
		}
		return array(
			'name'=>"/$year/$month/$day/$time.$suffix",
			'suffix' => $suffix,
			'basename' => $time,
			'path' => "/$year/$month/$day"
		);
	}

	/**
	 * 根据mimetype获取文件扩展名
	 * @param  [type] $mimetype [description]
	 * @return [type]           [description]
	 */
	public function suffix( $mimetype ) {
		if ( isset($this->_mimetypemap[$mimetype]) ) {
			return $this->_mimetypemap[$mimetype];
		}
		return 'tdm';
	}

	/**
	 * 根据扩展名获取 mimetype
	 * @param  [type] $suffix [description]
	 * @return [type]         [description]
	 */
	public function mimetypeBySuffix( $suffix ) {

		if ( $suffix[0] == '.' ) {
			$suffix = substr($suffix, 1, strlen($suffix));
		}

		if ( isset($this->_mimetype[$suffix]) ) {
			return $this->_mimetype[$suffix];
		}
		return 'unknown';
	}

	public function suffixByFile( $file ) {
		return end(explode('.', $mimetype));
	}



	/**
	 *  ========== 图片处理 ========== ========== ========== ========== ========== ========== ========== ========== ========== ========== ==========
	 */


	/**
	 * 裁切图片
	 * @param  [type]  $src_wrapper 
	 * @param  [type]  $dst_warpper 
	 * @param  [type]  $width       图片宽度
	 * @param  [type]  $height      图片高度
	 * @param  integer $x           x 轴坐标
	 * @param  integer $y           y 轴坐标
	 * @param  boolean $replace     [description]
	 * @return [type]               [description]
	 */
	public function crop( $src_wrapper, $dst_warpper, $width, $height, $x=0, $y=0, $replace=true ) {

		// 格式化输出
		$options['width'] = intval($width);
		$options['height'] = intval($height);
		$options['x'] = intval($x);
		$options['y'] = intval($y);

		return $this->image( 'crop', $src_wrapper, $dst_warpper, $options, $replace,  function( $img, $opts ) {
			$img->crop( $opts['width'], $opts['height'], $opts['x'], $opts['y']);
		} );

	}


	/**
	 * 按比例裁切
	 * @param  [type]  $src_wrapper
	 * @param  [type]  $dst_warpper
	 * @param  float  $ratio       宽高比
	 * @param  boolean $replace    是否替换
	 * @return [type]               [description]
	 */
	public function fit( $src_wrapper, $dst_warpper, $ratio=1,  $replace=true ) {

		return $this->image( 'fit', $src_wrapper, $dst_warpper, array('ratio'=>$ratio), $replace,  function( $img, $opts ) {
			
			$ratio = (floatval($opts['ratio']) == 0) ? 1:floatval($opts['ratio']);


			$w = $img->width();
			$h = $img->height();

			$fit_w = $h * $ratio;
			$fit_h = $w / $ratio;

			// echo "<pre>\nBefore: ratio:$ratio , w: $w , h: $h , fit_w: $fit_w, fit_h: $fit_h\n";

			if ( $fit_w > $w ) {
				$fit_w = $w;
			}

			if ( $fit_h > $h ) {
				$fit_h = $h;
			}

			$img->fit(intval($fit_w), intval($fit_h) );
		
			//  echo "After: ratio:". floatval($fit_w/$fit_h) ." ,  w: $w , h: $h , fit_w: $fit_w, fit_h: $fit_h\n";

		});
	}


	/**
	 * 压缩图片
	 * @param  [type]  $src_wrapper [description]
	 * @param  [type]  $dst_warpper [description]
	 * @param  integer $width       [description]
	 * @param  integer $height      [description]
	 * @param  boolean $replace     [description]
	 * @return [type]               [description]
	 */
	public function resize( $src_wrapper, $dst_warpper, $width=100, $height=100,  $replace=true ) {

		$width = intval($width);
		$height = intval($height);

		return $this->image( 'resize', $src_wrapper, $dst_warpper, array('width'=>$width, 'height'=>$height), $replace,  function( $img, $opts ) {
			$img->resize($opts['width'], $opts['height']);
		});

	}



	/**
	 * 调用图片处理函数
	 * @param  [type]  $api         [description]
	 * @param  [type]  $src_wrapper [description]
	 * @param  [type]  $dst_warpper [description]
	 * @param  [type]  $options     [description]
	 * @param  boolean $replace     [description]
	 * @param  [type]  $callback    [description]
	 * @return [type]               [description]
	 */
	private function image( $api, $src_wrapper, $dst_warpper,  $options, $replace=true, $callback ) {

		$type = $this->mimetype( $src_wrapper );
		if ( is_a($type, '\Xpmse\Err') ) { 
			return $type; 
		}

		if( !isset($this->_image_allow_function[$type]) ) {
			return new Err('340403', "文件类型不允许{$api}操作", ['api'=>$api, 'src_wrapper'=>$src_wrapper, 'dst_warpper'=>$dst_warpper, 'options'=>$options, 'replace'=>$replace, 'callback'=>$callback] );
		}
		if ( !$this->_image_allow_function[$type][$api] ) {
			return new Err('340403', "文件类型不允许{$api}操作", ['api'=>$api, 'src_wrapper'=>$src_wrapper, 'dst_warpper'=>$dst_warpper, 'options'=>$options, 'replace'=>$replace, 'callback'=>$callback] );

		}

		$data = $this->getData( $src_wrapper );
		if (  is_a($type, '\Xpmse\Err') ) {
			return $data;
		}

		$img = Image::make( $data );
		$callback( $img, $options );
		$dst_data = (string) $img->encode();
		$img->destroy();

		// 输出保存
		return $this->putData( $dst_warpper, $dst_data, $replace );

	}





	/**
	 * wrapper协议解析
	 * 	   协议: [engine://]bucket::/file_path_name
	 * 	   示例: bucket::/upload/2015/10/10/19381836.png
	 * 	  		local://bucket::/upload/2015/10/10/19381836.png
	 * 	  		sae://bucket::/upload/2015/10/10/19381836.png
	 * 	  		qiniu://bucket::/upload/2015/10/10/19381836.png
	 * 	  		sina://bucket::/upload/2015/10/10/19381836.png
	 * 	  		
	 * 
	 * @param  [type] $wrapper [description]
	 * @return [type]          [description]
	 */
	public function wrapperParse( $wrapper ) {

		$wrapper = trim($wrapper);
		$expEngine = "/^([a-zA-Z]+):\/\//";
		$expBucket = "/([0-9a-zA-Z_]+)\:\:([0-9a-zA-Z\/_\.]+)$/";

		$info = array( 'engine'=>'local' );
		if ( preg_match($expEngine, $wrapper, $match) ) {
			$info['engine'] = $match[1];
		}

		if ( preg_match($expBucket, $wrapper, $match) ) {
			$info['bucket'] = $match[1];
			$info['file'] = $match[2];
		} else {
			throw new Excp("Wrapper格式错误", '301500', ['wrapper'=>$wrapper]);	
		}


		// SAE Storage 引擎
		if( defined('SAE_APPNAME') && $info['engine'] == 'local' ) { 
			$info['engine'] = 'sae';
		}

		return $info;
    }
    



	/**
	 * 检查Bucket是否存在
	 * @param  [type] $bucket Bucket 名称
	 * @param  [type] $engine 存储引擎名称
	 * @return [type] 存在返回 true, 失败返回 false;
	 */
	public function bucketExists( $bucket, $engine=null ) {

		if ( $engine != null ) { 
			$this->engine( $engine );
		}

		if ($this->_engine == null ) {
			throw new Excp("未指定存储引擎", '302500', ['bucket'=>$bucket, 'engine'=>$engine]);	
		}

		$conf = $this->_engine['conf'];

		if ( isset( $conf['bucket'][$bucket] ) ) {
			return true;
		}

		return false;
	}


	/**
	 * 选择存储引擎
	 * @param  [type] $engine [description]
	 * @return [type]         [description]
	 */
	public function engine( $engine=null ) {

		if ( $engine == null ) {
			return $this->_engine;
		}

		$conf = $this->conf();
		if ( !is_array($conf) ) {
			throw new Excp("未配置存储引擎", '300404', ['conf'=>$conf, 'engine'=>$engine]);	
		}

		if ( !isset($conf[$engine])) {
			throw new Excp("存储引擎不存在", '300404', ['conf'=>$conf, 'engine'=>$engine]);	
		}

		$this->_engine = array('name'=>$engine, 'conf'=>$conf[$engine]);
		return $this->init();
	}


	public static function C() {
		$stor = new Stor();
		return $stor->conf();
	}


	/**
	 * 读取配置文件
	 */
	public function conf() {

		$conf = Conf::G("storage");
		if ( empty($conf) ) {
			throw new Excp("配置文件不存在", '308404', ['conf'=>$conf]);
		}

		// Home Map
		foreach ($conf as $engine => $value ) {
			foreach ($conf[$engine]['bucket'] as $name=>$val) {
				$home = $val['home'];
				$conf[$engine]['bucket'][$name][$home] = $name;
			}
		}
		return $conf;
	}



	/**
	 * 读取存储插件
	 * @return [type] [description]
	 */
	private function init() {

		$class_file = dirname(__FILE__) . '/storage-plugin/'. strtolower($this->_engine['name']) . '.plugin.php';
		$class_name = "\\Xpmse\\{$this->_engine['name']}StoragePlugin";


		if (!file_exists($class_file)) {
			throw new Excp("存储引擎插件文件不存在", '303404', ['class_name'=>$class_name, 'class_file'=>$class_file]);
		}

		require_once( $class_file );

		if ( !class_exists($class_name) ) {
			throw new Excp("存储引擎插件类不存在", '304404', ['class_name'=>$class_name, 'class_file'=>$class_file]);
		}

		$this->_plugin = new $class_name( $this->_engine['conf'] );
		return true;
	}


} 
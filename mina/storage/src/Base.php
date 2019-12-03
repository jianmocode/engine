<?php
/**
 * MINA Pages 对象存储基类
 * 
 * @package      \Mina\Storage
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Storage;

use \Exception;
use Mina\Storage\Obj as MinaObject;
use Intervention\Image\ImageManager;  // @see http://image.intervention.io/


class Base implements MinaObject {

    protected static $mimeBuilder = null;
	protected $options = [];
	protected $prefix = "";
	protected $image = null; 
	protected $cache = null;
	protected $inst  = null;
	protected $mime = null;
	protected $type = [];

	/**
	 * 对象存储
	 * @param array $options 配置选项
	 *         	     string  ["prefix"]  对象存储前缀，默认为""
	 *         	     string  ["image"]   图片处理选项
	 *         	         string  ["image"]['driver'] 图片处理引擎, 默认为 "gd", 有效值 gd/imagick
	 *         	         string  ["image"]['memory_limit'] 图片处理引擎, 默认为 PHP ini 中的配置数值
	 *      		 array   ["cache"]   缓存配置选项
	 *      		     string  ["cache"]["engine"] 引擎名称 有效值 Redis/Apcu, 默认为 null, 不启用缓存。
	 *      		     string  ["cache"]["prefix"] 缓存前缀，默认为空
	 *      		     string  ["cache"]["host"] Redis 服务器地址  默认 "127.0.0.1"
	 *      		        int  ["cache"]["port"] Redis 端口 默认 6379
	 *      		     string	 ["cache"]["passwd"] Redis 鉴权密码 默认为 null
	 *      		        int  ["cache"]["db"] Redis 数据库 默认为 1
	 *      		        int  ["cache"]["timeout"] Redis 超时时间, 单位秒默认 10
	 *      		        int	 ["cache"]["retry"] Redis 链接重试次数, 默认 3
	 */
	function __construct( $options = [] ) {

		$this->options = $options;

		$this->options['prefix'] = !empty($options['prefix']) ? $options['prefix'] : "";
		$this->prefix = $this->options['prefix'];

		$cacheOptions = !empty($this->options['cache']) ? $this->options['cache'] : [];
		if (!empty($cacheOptions['engine'])) {
			$cacheClassName = "\\Mina\\Cache\\{$cacheOptions['engine']}";
			if ( class_exists($cacheClassName) ) {
				$this->cache = new $cacheClassName( $cacheOptions );
			}
		}

		$imageOptions = !empty($this->options['image']) ? $this->options['image'] : [];
		$imageOptions['driver'] = !empty($imageOptions['driver']) ? $imageOptions['driver'] : 'gd';

		$this->image = new ImageManager( $imageOptions );

		if ( isset( $imageOptions['memory_limit']) ) {
			try {
				ini_set('memory_limit', intval($imageOptions['memory_limit']) );
			} catch( Exception  $e ) {} ;
		}

		// MIME TYPE 
        if ( self::$mimeBuilder === null ) {
            self::$mimeBuilder = \Mimey\MimeMappingBuilder::create();
            self::$mimeBuilder->add('text/mina-pages', 'page');
		    self::$mimeBuilder->add('application/vnd.ms-excel', 'xlsx');
		    self::$mimeBuilder->add('application/vnd.ms-powerpoint', 'pptx');
        }
        $this->mime = new \Mimey\MimeTypes(self::$mimeBuilder->getMapping());
		$this->type = [
			"image" => function( $mimetype ) { 
				return ( strpos($mimetype, 'image') === false ) ? false : true;
			},
			"video" => function( $mimetype ) { 
				return ( strpos($mimetype, 'video') === false ) ? false : true;
			},
			"audio" => function( $mimetype ) { 
				return ( strpos($mimetype, 'audio') === false ) ? false : true;
			},
			"text" => function( $mimetype ) { 
				return ( strpos($mimetype, 'text') === false ) ? false : true;
			},
			"html" => function( $mimetype ) { 
				return ( strpos($mimetype, 'text/html') === false ) ? false : true;
			},
			"css" => function( $mimetype ) { 
				return ( strpos($mimetype, 'text/css') === false ) ? false : true;
			},
			"js" => function( $mimetype ) { 
				return ( strpos($mimetype, 'application/javascript') === false ) ? false : true;
			},
			"json" => function( $mimetype ) { 
				return ( strpos($mimetype, 'json') === false ) ? false : true;
			},
			"page" => function( $mimetype ) { 
				return ( strpos($mimetype, 'text/mina-pages') === false ) ? false : true;
			},
			"word" => function( $mimetype ) { 
				return ( strpos($mimetype, 'word') === false ) ? false : true;
			},
			"excel" => function( $mimetype ) { 
				return ( strpos($mimetype, 'excel') === false ) ? false : true;
			},
			"ppt" => function( $mimetype ) { 
				return ( strpos($mimetype, 'powerpoint') === false ) ? false : true;
			},
			"pdf" => function( $mimetype ) { 
				return ( strpos($mimetype, 'pdf') === false ) ? false : true;
			},
			"zip" => function( $mimetype ) { 
				$ret =  ( strpos($mimetype, 'zip') === false ) ? false : true;
				if ( $ret === true) return true;

				$ret =  ( strpos($mimetype, 'rar') === false ) ? false : true;
				if ( $ret === true) return true;

				$ret =  ( strpos($mimetype, 'compressed') === false ) ? false : true;
				if ( $ret === true) return true;

				$ret =  ( strpos($mimetype, 'tar') === false ) ? false : true;
				if ( $ret === true) return true;

				return false;
			}
		];

	}

	
	/**
	 * 如使用外部云存储 API，返回服务API对象
	 * @return mix
	 */
	function inst(){ return $this->inst; }


	/**
	 * 检查云存储配置是否正确
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	function ping(){ return false; }


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
	public function get( $path, $nocache=false ) { return false; }


	/**
	 * 根据对象路径，读取对象数据
	 * @param  string $path 对象路径
	 * @param  boolean $nocache TRUE 不启用缓存, FALSE 启用缓存
	 * @return string 返回对象数据
	 */
	public function getBlob( $path, $nocache=false ) { return false; }



	/**
	 * 上传对象到指定路径
	 * @param  string $path 对象路径
	 * @param  string $blob 对象数据
	 * @param  boolean $replace 如对象已存在是否替换，默认为 TURE 替换
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function upload( $path, $blob, $replace = true ){ return false; }



	/**
	 * 追加数据到指定文件
	 * @param  string $path 对象路径
	 * @param  string $blob 当前片段数据
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function append( $path, $blob ) { return false; }



	/**
	 * 插入数据到目标文件指定位置
	 * @param  string $path 对象路径
	 * @param  string $blob 当前片段数据
	 * @param  int $from  起始位置
	 * @param  int $total 总字节数
	 * @return 完成返回: array 返回对象完整信息 
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *
	 * 		   分段完成: true
	 * 		   插入失败: false
	 */
	public function insert( $path, $blob, $from=0,  $total=null ){ return false; }



	/**
	 * 根据对象路径，删除对象
	 * @param  string $path 对象路径
	 * @param  boolean $recursive  TRUE: 同事删除子目录名下所有文件 FALSE: 如包含子目录则删除所有 false 
	 * @param  boolean $ignore_notexist  TRUE: 如对象不存在函数返回 true FALSE: 如对象不存在函数返回 false 
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	public function remove( $path, $recursive=true, $ignore_notexist = true ){ return false; }


	/**
	 * 检查对象是否存在
	 * @param  string $path 对象路径
	 * @return boolean  成功返回 TRUE, 失败返回 FALSE
	 */
	public function isExist( $path ){ return false; }


	/**
	 * 刷新对象缓存 (CDN) 
	 * @param  string $path 对象路径 
	 * @return boolean  成功返回 TRUE, 失败返回 FALSE 如没有CDN，则一直存在
	 */
	public function refresh( $path ){ return false; }


	/**
	 * 检查对象是否为已知文件类型
	 * @param  string $path 对象路径 
	 * @param  string $type 检查类型，有效值
	 *                "image" 图片
	 *                "video" 视频
	 *                "audio" 音频
	 *                "text"  文本
	 *                "html"  网页
	 *                "css"   样式表
	 *                "js"    JavaScript 脚本
	 *                "word"  Word 文档
	 *                "excel" Excel 文档
	 *                "ppt"   PowerPoint 文档
	 *                "pdf"   PDF 文档
	 *                "zip"   压缩包 ( 包含 rar/zip/tar/gz 等 )
	 *                "page"  MINA Pages 文件
	 *                "json"  JSON 文件
	 *                    
	 * @return boolean 如对象为指定类型返回 TRUE 否则返回 FALSE
	 */
	public function is_a( $path, $type ){ 
		if ( is_callable($this->type[$type]) ) {
			return $this->type[$type]( $this->getMimeTypeByName( $path ) );
		} 
		return false;
	}


	/**
	 * 返回前缀值
	 * @return string prefix
	 */
	public function prefix(){
		return $this->prefix;
	}

	/**
	 * 返回当前 cache 实例
	 * @return [type] [description]
	 */
	public function cache() {
		return $this->cache;
	}


	/**
	 * 返回 Options
	 */
	final function getOption( $name = null ) {

		if ( $name == null ) {
			return $this->options;
		}

		return  $this->options['name'];
	}


	/**
	 * 返回本地文件镜像路径
	 * @param  string $path 对象路径 
	 * @return string 文件绝对路径
	 */
	public function local( $path ) {
		return $path;
	}


	/**
	 * ==============================================================
	 *   下为 MIME TYPE 相关方法
	 * ==============================================================
	 */

	/**
	 * 读取扩展名的 MimeType(s)
	 * @param  string $ext  文件扩展名
	 * @param  boolean $getall 是否返回所有, 默认 FALSE
	 * @return string | array  
	 *               $getall = TRUE 返回  MimeType 数组
	 *               $getall = FALE 返回  MimeType 字符串
	 */
	final function getMimeType( $ext, $getall=false ) {
		if ( $getall ) {
			return $this->mime->getAllMimeTypes( $ext );
		}

		return $this->mime->getMimeType( $ext );
	}

	/**
	 * 读取文件名的 MimeType(s)
	 * @param  string $ext  文件扩展名
	 * @param  boolean $getall 是否返回所有, 默认 FALSE
	 * @return string | array  
	 *               $getall = TRUE 返回  MimeType 数组
	 *               $getall = FALE 返回  MimeType 字符串
	 */
	final function getMimeTypeByName( $filename, $getall=false ) {

		$arr = explode('.',$filename);
		$ext = strtolower(array_pop($arr));
		return $this->mime->getMimeType($ext, $getall);
	}


	/**
	 * 读取 MimeType 的扩展名
	 * @param  string $mimetype  Mime Type
	 * @param  boolean $getall 是否返回所有, 默认 FALSE
	 * @return string | array  
	 *               $getall = TRUE 返回  扩展名数组
	 *               $getall = FALE 返回  扩展名字符串
	 */
	final function getExt( $mimetype, $getall=false ) {
		if ( $getall ) {
			return $this->mime->getAllExtensions(  $mimetype );
		}
		return $this->mime->getExtension( $mimetype ); 
	}


	/**
	 * 返回 MimeType 对象实例
	 * @see https://github.com/ralouphie/mimey
	 * @return \Mimey\MimeTypes 
	 */
	final function mime() {
		return $this->mime;
	}




	/**
	 * ==============================================================
	 *   下为图片处理方法
	 * ==============================================================
	 */


	/**
	 * 返回图片处理对象
	 * @return \Intervention\Image\ImageManagerStatic $image 
	 */
	public function image(){ 
		return $this->image; 
	}


	/**
	 * 读取图片信息
	 * @param  string $path 图片路径
	 * @return array $data
	 *         	  ["width"] 图片宽度
	 *         	  ["height"] 图片高度
	 *         	  ["exif"] 图片 Exif 信息
	 */
	public function info( $path ) {
		// 验证原对象是否为图片
		if ( !$this->is_a($path, 'image') ) {
			throw new Exception("原文件不是图片  ( $origin )", 400 );
		}

		// 验证原文件是否存在
		if ( !$this->isExist($path, 'image') ) {
			throw new Exception("原文件不存在 ( $origin )", 400 );
		}

        // 读取文件类型
        // $pi = pathinfo($path);
        // $type = strtoupper($pi["extension"]);
        // $im->setFormat($type);

		$raw = $this->getBlob( $path );
        $im = new \Imagick;
        try {
            $im->readImageBlob($raw);
        } catch( Exception  $e ) {
            $im->clear();
      	    $im->destroy();
            return ['width'=>0, 'height'=>0, 'prop'=>[]];
        }
		$prop = $im->getImageProperties("*");
		$w = $im->getImageWidth();
		$h = $im->getImageHeight();
		$im->clear();
      	$im->destroy();
		return ['width'=>$w, 'height'=>$h, 'prop'=>$prop];
	}


	/**
	 * 裁切图片 
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  裁切参数
	 *                 int    ["width"]    图片宽度 ( 如未设置 ratio 和 height 参数 则必填 )
	 *                 int    ["height"]   图片高度 ( 如未设置 ratio 和 width 参数 则必填 )
	 *                 int      ["x"]        X 轴坐标, 默认值 0
	 *                 int      ["y"]        Y 轴坐标, 默认值 0
	 *                 float    ["ratio"]    调整图片比例 (如设置此参数，则 "width"、"height" 参数失效 )
	 *                 boolean  ["replace"]  如目标路径图片存在, 则替换原路径， 默认为 TRUE 
	 *                 
	 * @return array 返回目标图片完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *         	int ["width"] 图片宽度
	 *         	int ["height"] 图片高度
	 */
	public function crop( $origin, $dest, $options ) { 

		// 验证参数
		if ( empty($options['width']) && empty($options['height']) && empty($options['ratio']) ) {
			throw new Exception("参数错误 ( width/height/ratio 必填一个 )", 400 );
		}

		// 验证原对象是否为图片
		if ( !$this->is_a($origin, 'image') ) {
			throw new Exception("原文件不是图片  ( $origin )", 400 );
		}

		// 验证原文件是否存在
		if ( !$this->isExist($origin, 'image') ) {
			throw new Exception("原文件不存在 ( $origin )", 400 );
		}

		
		$raw = $this->getBlob( $origin );
		$img = $this->image->make( $raw );

		$w = $img->width(); // 原始图片宽度
		$h = $img->height();  // 原始图片高度

		$x = !empty($options['x']) ?  intval($options['x']) : 0;
		$y = !empty($options['y']) ?  intval($options['y']) : 0;

		
		if  ( isset($options['ratio']) ) { // 按比例裁切逻辑

			$ratio = floatval( $options['ratio'] );
			$crop_width = $w - $x;
			$crop_height = $h - $y;

			$fit_w = $crop_height * $ratio;
			$fit_h = $crop_width / $ratio;

			if ( $fit_w > $crop_width ) {
				$fit_w = $crop_width;
			}

			if ( $fit_h > $crop_height ) {
				$fit_h = $crop_height;
			}

			$options['width'] = $fit_w;
			$options['height'] = $fit_h;
		
		} else {  // 如果裁切大小，大于实际大小，则补充画布

		}


		// 开始裁切
		$width = intval($options['width']);
		$height = intval($options['height']);


		if ($this->getMimeTypeByName($origin) == 'image/gif' && class_exists('\Imagick')) {
		
			$im = new \Imagick;
			$im->readImageBlob($raw);

			foreach ($im as $frame) {
			   $frame->setImageBackgroundColor('none'); //This is important!
			}

		    $im = $im->coalesceImages();
		    foreach ($im as $frame) {
		    	$frame->cropImage($width, $height, $x, $y);
			  	$frame->thumbnailImage($width, $height);
			}
		    $im = $im->deconstructImages();
		    $cropRaw = $im->getImagesBlob();
		    $im->clear();
      		$im->destroy();
      		unset( $im );

		} else {
			$cropRaw = (string) $img->crop($width, $height, $x, $y)->encode();
		}

		
		$img->destroy();

		$replace = !empty($options['replace']) ?$options['replace'] : true;

		$info = $this->upload( $dest, $cropRaw, $replace );
		$info['width'] = $width;
		$info['height'] = $height;

		return $info;

	}



	/**
	 * 压缩图片 
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  压缩参数
	 *                 int    ["width"]    图片宽度 ( 如未设置 height，则必填 )
	 *                 int    ["height"]   图片高度 ( 如未设置 width，则必填 )
	 *                 boolean  ["replace"]  如目标路径图片存在, 则替换原路径
	 *                 
	 * @return array 返回目标图片完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *         	int ["width"] 图片宽度
	 *         	int ["height"] 图片高度
	 */
	public function resize( $origin, $dest, $options ) {

		if ( empty($options['width']) && empty($options['height']) ) {
			throw new Exception("参数错误 ( width/height 必填一个 )", 400 );
		}

		// 验证原对象是否为图片
		if ( !$this->is_a($origin, 'image') ) {
			throw new Exception("原文件不是图片  ( $origin )", 400 );
		}

		// 验证原文件是否存在
		if ( !$this->isExist($origin, 'image') ) {
			throw new Exception("原文件不存在 ( $origin )", 400 );
		}


		$raw = $this->getBlob( $origin );
		$img = $this->image->make( $raw );
		$w = $img->width(); // 原始图片宽度
		$h = $img->height();  // 原始图片高度

		$width = !empty($options['width'])  ? intval( $options['width']) : 1;
		$height = !empty($options['height']) ? intval( $options['height']) : 1;
		if ( empty($options['width']) ) {
			$width = intval($height * floatval( $w ) / floatval($h) );
		}

		if ( empty($options['height']) ) {
			$height = intval($width * floatval( $h ) / floatval($w) );
		}


		// GIF 处理
		if ($this->getMimeTypeByName($origin) == 'image/gif' && class_exists('\Imagick')) {
		
			$im = new \Imagick;
			$im->readImageBlob($raw);
		    $im = $im->coalesceImages();
		    do {
		        $im->resizeImage($width, $height, \Imagick::FILTER_BOX, 1);
		    } while ($im->nextImage());

		    $im = $im->deconstructImages();
		    $resizeRaw = $im->getImagesBlob();

		    $im->clear();
      		$im->destroy();
      		unset( $im );

		} else {
			$resizeRaw = (string) $img->resize($width, $height)->encode();
		}

		$img->destroy();
		$replace = !empty($options['replace']) ?$options['replace'] : true;

		$info = $this->upload( $dest, $resizeRaw, $replace );
		$info['width'] = $width;
		$info['height'] = $height;

		return $info;

	}



	/**
	 * 添加水印(Gif文字效率低)
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  压缩参数
	 *                 float|string    ["x"]           水印所在的 X 轴坐标, 默认值 0， 有效值 数值
	 *                 float|string    ["y"]           水印所在的 Y 轴坐标, 默认值 0， 有效值 数值
	 *                 string          ["position"]    位置有效值, 默认值 top-left
	 *                 									   top-left
     *                                                     top
     *                                                     top-right
     *                                                     left
     *                                                     center
     *                                                     right
     *                                                     bottom-left
     *                                                     bottom
     *                                                     bottom-right
     *                                                     rand ( 随机 )
	 *                 
	 *                 string          ["text"]        文本水印 ( 如未设置 image 则必填 )
	 *                 string          ["color"]       文本水印颜色代码，默认值 #333333
	 *                 string          ["font"]        字体文件位置 (本地)
	 *                 int             ["size"]        文本水印字号大小，默认值 15 (必须设定字体才有效)
	 *                 string 	       ["align"]       文字对齐方式，默认 left  有效值  left/right/center
	 *                 string 	       ["valign"]      文字对齐纵向方式，默认 bottom  有效值  top/middle/bottom
	 *                 float 	       ["angle"]       水印角度, 默认值0 （ 文字水印需字体支持 )
	 
	 *                 int             ["alpha"]       水印透明度，有效值 0~100 默认值 30
	 *                 string          ["image"]       图片水印 ( 如设置此参数，则 "text" 参数失效 )
	 *                 boolean         ["replace"]     如目标路径图片存在, 则替换原路径
	 *                 
	 * @return array 返回目标图片完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *         	array  ["watermark"] 水印信息
	 *         		int 	["watermark"]["alpha"]     水印透明度 ( 0-100 )
	 *         		float 	["watermark"]["angle"]     水印角度
	 *         		int 	["watermark"]["x"]         水平位置
	 *         		int 	["watermark"]["y"]         垂直位置
	 *         		
	 *         		int 	["watermark"]["width"]     图片水印宽度
	 *         		int 	["watermark"]["height"]    图片水印高度
	 *         		string 	["watermark"]["position"]  图片水印位置
	 *         		string 	["watermark"]["text"]      文字水印正文
	 *         		string 	["watermark"]["font"]      文字水印字体文件路径
	 *         		int 	["watermark"]["size"]      文字水印文字大小
	 *         		string 	["watermark"]["color"]     文字水印颜色 （16进制颜色代码)
	 *         		array 	["watermark"]["rgb"]       文字水印RGB代码
	 *         		string 	["watermark"]["align"]     文字水印水平对齐方式
	 *         		string 	["watermark"]["valign"]    文字水印垂直对齐方式
	 *         		
	 
	 */
	public function watermark( $origin, $dest, $options ) {

		if ( empty($options['image']) && empty($options['text']) ) {
			throw new Exception("参数错误 ( image/text 必填一个 )", 400 );
		}

		// 验证原对象是否为图片
		if ( !$this->is_a($origin, 'image') ) {
			throw new Exception("原文件不是图片  ( $origin )", 400 );
		}

		// if ($this->getMimeTypeByName($origin) == 'image/gif' ) {
		// 	return $this->get($origin);
		// }

		// 验证原文件是否存在
		if ( !$this->isExist($origin, 'image') ) {
			throw new Exception("原文件不存在 ( $origin )", 400 );
		}

		if ( !empty($options['image']) && !$this->is_a($options['image'], 'image') )  {
			throw new Exception("水印文件不是图片  ( {$options['image'] })", 400 );
		}

		if ( !empty($options['image']) && !$this->isExist($options['image'], 'image')){
			throw new Exception("水印文件不存在  ( {$options['image']} )", 400 );
		}

		$position = !empty($options['position']) ? $options['position'] : 'top-left';
		$x = !empty($options['x']) ? $options['x'] : 0;
		$y = !empty($options['y']) ? $options['y'] : 0;
		$alpha = !empty($options['alpha']) ? intval($options['alpha']) : 30;
		$angle = !empty($options['angle']) ? $options['angle'] : null;
		$replace = !empty($options['replace']) ?$options['replace'] : true;

		$raw = $this->getBlob($origin);
		$img = $this->image->make( $raw );
		$w = $img->width(); // 原始图片宽度
		$h = $img->height();  // 原始图片高度


		// 处理图形水印
		if ( isset( $options['image']) ) {

			$wmRaw = $this->getBlob(  $options['image'] );
			$wmImg = $this->image->make( $wmRaw );

			// 处理透明度
			$wmImg->opacity( $alpha );
		

			if ( $position == 'rand' ) {
				$wmw = $wmImg->width();
				$wmh = $wmImg->height();
				$rx = abs($w - $wmw); 
				$ry = abs($h - $wmh );
				$x = rand(0, $rx );
				$y = rand(0, $ry );
				$angle = rand( -180, 180 );
				$position = "top-left";
			}

			$resp = [
				"width" => $wmw,
				"height" => $wmh,
				"position" => $position,
				"x" => $x,
				"y" => $y,
				"alpha" => $alpha
			];

			if ( !empty($angle) ) {
				$wmImg->rotate( $angle );
				$resp['angle'] = $angle;
			}


			// GIF 处理
			if ($this->getMimeTypeByName($origin) == 'image/gif' && class_exists('\Imagick')) {


				$wmIm = new \Imagick;
				$wmIm->readImageBlob($wmImg->encode());

				$im = new \Imagick;
				$im->readImageBlob($raw);
			    $im = $im->coalesceImages();
			    do {
			        $im->compositeImage($wmIm, \Imagick::COMPOSITE_OVER, $x, $y);
			    } while ($im->nextImage());

			    $im = $im->deconstructImages();
			    $mergeRaw = $im->getImagesBlob();

			    $wmIm->clear(); 
			    // $wmIm->destory();
			    $im->clear();
	      		$im->destroy(); 
	      		unset( $im );unset( $wmIm );

			} else {
				$mergeRaw = $img->insert( $wmImg, $position, $x, $y )->encode();	
			}


			
			$img->destroy();
			$wmImg->destroy();

			$info = $this->upload( $dest, $mergeRaw, $replace );
			$info['width'] = $w;
			$info['height'] = $h;
			$info['watermark']  = $resp;
			return $info;
		}


		// 处理文字
		$text = $options['text'];
		$size = !empty($options['size']) ? $options['size'] : 15;


		if ( $position == 'rand' ) {
			$wmw = mb_strlen($text) * $size;
			$wmh = $size * 2;
			$rx = abs($w - $wmw); 
			$ry = abs($h - $wmh );
			$x = rand(0, $rx );
			$y = rand(0, $ry );
			$angle = rand( -180, 180 );
		}

		$resp = [
			"text" => $text,
			"x" => $x,
			"y" => $y
		];


		// GIF 处理
		if ($this->getMimeTypeByName($origin) == 'image/gif' && class_exists('\Imagick')) {

			$alpha = !empty($options['alpha']) ? intval($options['alpha']) : 30;
			$size = !empty($options['size']) ? intval($options['size']) : 15;
			$color = !empty($options['color']) ? $options['color'] : '#3333333';
			list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");

			$fontpath = !empty($options['font']) ? $options['font'] : null;
			$align = !empty($options['align']) ? $options['align'] : 'left';
			$valign = !empty($options['valign']) ? $options['valign'] : 'bottom';
			$angle = !empty( $angle) ?  $angle :0 ;

			$draw = new \ImagickDraw(); 
			$draw->setStrokeAntialias(true);
       		$draw->setTextAntialias(true);

       		if ( !empty($fontpath)  && file_exists($fontpath) ) {
				$resp['font'] = $fontpath;
				$draw->setFont($fontpath);	
			}

			$draw->setFontSize($size);
			$resp['size'] = $size;
			
			$resp['align'] = $align;
			switch (strtolower($align)) {
	            case 'center':
	                $align = \Imagick::ALIGN_CENTER;
	                break;
	            case 'right':
	                $align = \Imagick::ALIGN_RIGHT;
	                break;
	            default:
	                $align = \Imagick::ALIGN_LEFT;
	                break;
	        }

			$draw->setTextAlignment($align);



			// color 
			$alphaR = $alpha/100; 
			$strokeColor = new \ImagickPixel("rgb($r, $g, $b, $alphaR)");
			$draw->setFillColor( $strokeColor );
			$resp['alpha'] = $alpha;
			$resp['color'] = $color;
			$resp['rgb'] = [$r, $g, $b, $alpha/100];
			$resp['angle'] = $angle;


			$im = new \Imagick;
			$im->readImageBlob($raw);
			$im = $im->coalesceImages();
			do {
			    $im->annotateImage($draw, $x, $y, $angle * (-1), $text);
			} while ($im->nextImage());

			$im = $im->deconstructImages();
			$mergeRaw = $im->getImagesBlob();

			$im->clear();
	      	$im->destroy(); 
	      	unset( $im );

		} else {

			$mergeRaw = $img->text($text, $x, $y, function($font) use( $options, & $resp, $angle ) {

				$alpha = !empty($options['alpha']) ? intval($options['alpha']) : 30;
				$size = !empty($options['size']) ? intval($options['size']) : 15;
				$color = !empty($options['color']) ? $options['color'] : '#3333333';
				list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");

				$fontpath = !empty($options['font']) ? $options['font'] : null;
				$align = !empty($options['align']) ? $options['align'] : 'left';
				$valign = !empty($options['valign']) ? $options['valign'] : 'bottom';
				

				if ( !empty($fontpath)  && file_exists($fontpath) ) {
					$resp['font'] = $fontpath;
					$font->file($fontpath);	
				}
			    

				$font->size($size);
				$font->color(array($r, $g, $b, $alpha/100 ));
				$font->align($align);
				$font->valign($valign);
				if ( !empty($angle) ) {
					$font->angle(intval($angle));	
					$resp['angle'] = $angle;
				}

				$resp['rgb'] = [$r, $g, $b, $alpha/100];
				$resp['color'] = $color;
				$resp['align'] = $align;
				$resp['valign'] = $valign;
				$resp['size'] = $size;
				$resp['alpha'] = $alpha;

			})->encode();
		}


		$info = $this->upload( $dest, $mergeRaw, $replace );
		$info['width'] = $w;
		$info['height'] = $h;
		$info['watermark']  = $resp;

		$img->destroy();

		return $info;

	}

}


























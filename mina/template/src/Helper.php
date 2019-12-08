<?php 
/**
 * MINA Pages 模板渲染器
 * 
 * @package	  \Mina\Template
 * @author	   天人合一 <https://github.com/trheyi>
 * @copyright	Xpmse.com
 * 
 * @example
 *
 * {{IMAGE(article.cover, '800', '250')}}
 * 
 */

namespace Mina\Template;

use \Exception;
use \Endroid\QrCode\QrCode as Qrcode;
use \Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;



class Helper  {

	static private $options = [];
	static private $storage = null;  // MINA Storage
	static private $cache = null;    // MINA Cache
	static private $debug = false;

	function __construct() {}

	static function init( $options = [] ) {

		if ( isset($options['cache']) ) {
			self::$cache = $options['cache'];
			unset($options['cache'] );
		}

		if ( isset($options['storage']) ) {
			self::$storage = $options['storage'];
			unset($options['storage'] );
		}

		if ( isset($options['debug']) ) {
			self::$debug = $options['debug'];
			unset( $options['debug']);
		}

		self::$options = $options;
    }
    
    


	/**
	 * 生成二维码 Helper
	 * 
	 * {{QR(__sys.location, '扫一扫，用手机浏览', '', 140, 50, 13)}}
	 * 
	 * @param  [type]  $text       [description]
	 * @param  string  $label      [description]
	 * @param  string  $logo       [description]
	 * @param  integer $size       [description]
	 * @param  integer $logosize   [description]
	 * @param  integer $fontsize   [description]
	 * @param  integer $padding    [description]
	 * @param  string  $color      [description]
	 * @param  string  $background [description]
	 * @param  string  $font       [description]
	 * @return [type]              [description]
	 */
	static function qr( $text, $label="", $logo="",  $size=300, $logosize=50,  $fontsize=14,  $padding=10, $color="", $background="", $font=''  ){

		$option = [
			'text' => $text,
			'size' => $size,
			'padding' => $padding,
			'label' => $label,
			'fontsize' => $fontsize,
			'color' => $color,
			'background' => $background,
			'font' => $font,
			'logo' => $logo,
			'logosize' => $logosize
		];

		$qr_name = self::md4( implode(',', $option) );

		$urlkey = ( self::$debug == false ) ? "url" : "origin";
		$cname  = null;
		if (  self::$cache != null && self::$debug == false  ) {  // 从缓存中读取地址
			$cname = self::getCacheName('helper:qr', $option);
			$info = self::$cache->getJSON( $cname );
			if ( $time == "" && $info !== false  )  {
				return $info[$urlkey];
			}
		}

		$option['font'] = !empty($option['font']) ? $option['font'] : '黑体';
		$option['color'] = empty($option['color']) ?  [ 0,  0, 0, 0] : explode(',',$option['color']);
		$option['background']= empty($option['background']) ?  [ 255,  255, 255, 255] : explode(',',$option['background']);

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


		$font_style = $option['font'];
        $fontsMap = self::$options['fonts']['names'];
        $fontpath = "";
        if ( !empty($font_style) ) {
            $fontpath = $fontsMap[$font_style];
            if ( empty($fontpath) ) {
                $fontpath = current( $fonts['data'] );
            }
        }


		$qr = new QrCode();
		$qr ->setWriterByName('png')
		    ->setEncoding('UTF-8')
		    ->setText($option['text'])
		    ->setSize($option['size'])
		    ->setMargin( $option['padding'] )
		    ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
		    ->setForegroundColor($option['color'])
		    ->setBackgroundColor($option['background'])
		    ->setLabel(
		    	$option['label'], $option['fontsize'],  
		    	$fontpath['local'], 
		    	LabelAlignment::CENTER )
		    ->setValidateResult(false);
		    // ->setImageType( QrCode::IMAGE_TYPE_PNG );

			
		if ( !empty($logo) ) {

			$logoBlob = null;
			if( substr($logo, 0, 4) == 'http' || is_readable($logo) ) {
				$logoBlob = file_get_contents($logo);
			} else if ( self::$storage->isExist($logo) ) {
				$logoBlob = self::$storage->getBlob($logo);
			}


			if ( !empty($logoBlob) ) {
				$logopath = sys_get_temp_dir() . "/" . time() . ".logo";
				file_put_contents($logopath, $logoBlob);
				$qr->setLogoPath( $logopath);
				$qr->setLogoWidth( $logosize );
			}
		}



		$qr_path = self::getpath( $qr_name, 'png' );
		$blob = $qr->writeString();
		$info = self::$storage->upload( $qr_path, $blob );

		// 写入缓存
		if ( $cname != null  ) { 
			self::$cache->setJSON( $cname, $info );
		}

		return $info[$urlkey];
	}



	/**
	 * 自动裁切图片
	 * 
	 * {{IMAGE(article.cover, '800', '250')}}
	 * {{IMAGE(article.cover, '800', '250', 'T')}}
	 * 
	 * @param  string $url 图片访问地址
	 * @param  int $width  图片宽度
	 * @param  int $height 图片高度
	 * @param  string $time 如不为空，则刷新缓存
	 * @return string url 返回新图片地址
	 */
	static function image( $url, $width, $height, $time='' ) {

		if ( self::$storage == null ) {
			return $url;
		}

		$width = intval($width) ;
		$height = intval( $height);
		if ( $height == 0  || $width == 0)  return $url;


		$urlkey = ( self::$debug == false ) ? "url" : "origin";
		$cname = null;
		if (  self::$cache != null && self::$debug == false  ) {  // 从缓存中读取地址
			$cname = self::getCacheName('helper:image', [
				"url" => $url,
				"width" => $width, 
				"height" => $height
			]);

			$info = self::$cache->getJSON( $cname );
			if ( $time == "" && $info !== false  )  {
				return $info[$urlkey];
			}
		}

		$ext = self::getExt($url);
		$origin_name = self::md4( $url );
		$origin_path = self::getpath( $origin_name, $ext );

		$resize_name = $origin_name . "_{$time}_{$width}_{$height}";
		$resize_path = self::getpath( $resize_name, $ext );

		// 如果文件已存在，则直接返回结果
		if ( self::$storage->isExist($resize_path) && self::$debug == false  ) {
			$resize_info = self::$storage->get( $resize_path );

			// 写入缓存
			if ( $cname != null  ) { 
				self::$cache->setJSON( $cname, $resize_info );
			}
			return $resize_info[$urlkey];
		}


		// 自动上传原始文件
		if ( !self::$storage->isExist($origin_path) ) {
			if ( substr($url, 0, 4) != 'http' ) { 
				$url = self::home() . $url;
			}
			$blob = file_get_contents($url);
			self::$storage->upload( $origin_path, $blob );
		}


		// 裁切图片
		$ratio = floatval($width)/floatval($height);
		$crop_path = self::getpath( $resize_name . "_crop", $ext );
		$crop_info = self::$storage->crop( $origin_path, $crop_path, ['ratio'=>$ratio] );
		$resize_info = self::$storage->resize(  $crop_path, $resize_path, ['width'=>$width, 'height'=>$height] );

		// 写入缓存
		if ( $cname != null  ) { 
			self::$cache->setJSON( $cname, $resize_info );
		}

		return $resize_info[$urlkey];
	}


	/**
	 * 生成MD4 字符串
	 * 
	 * {{MD4('xxxx')}}
	 * 
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	static function md4( $name ) {
		// MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return hash('md4',  $name);   
	}


    /**
     * 输入用户名称
     */
	static function PrintName( $user ) {
		if (is_string($user)) {
			return $user;
		}
		if ( !empty($user['nickname']) ) {
			return $user['nickname'];
		} else if ( !empty($user['name']) ) {
			return $user['name'];
		} else if ( !empty($user['mobile']) ) {
			return preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $user['mobile']);
		} else {
			return '';
		}
	}

    /**
     *  如果数组 $array 中存在 $value 数值, 则输出 $output
     */
    static function IFInArray( $value, $array, $output ){
  
        if ( !is_array( $array) ) {
            return '';
        }

        if ( in_array($value, $array) ) {
            return $output;
        }

        return '';
    }

    /**
     *  如果数组 $array 中存在 $value 数值 返回  true
     */
    static function InArray( $value, $array ){
  
        if ( !is_array( $array) ) {
            return false;
        }

        if ( in_array($value, $array) ) {
            return true;
        }

        return false;
    }

    /**
     *  转换为整数
     */
    static function IntVal( $value ){
       return intval($value);
    }

    /**
     * JSON 字符串
     */
	static function JSON( $data, $escape = false ) {
		if ( empty($data) ) {
			return "";
		}

		$resp = json_encode($data);

		if ( $escape ) {
			$resp = str_replace("\"", "&quot;", $resp );
		}

		return $resp;
    }
    
    /**
     * JSON 字符串
     */
	static function JSONGet( $key, $data ) {
		if ( empty($data) || !is_array($data) ) {
			return "";
        }
        foreach( $data as $item ) {
            list($k, $v) = $item;
            if ( $k == $key ) {
                return $v;
            }
        }
        return "";
    }


	static function youkuID( $url, $content=null ) {
		

		// player.youku.com/embed/XMjY2ODQwOTQzNg==
		if ( preg_match("/id_([0-9a-zA-Z]+)/", $url, $match) ) {
			return $match[1];
		}

		// http://v.youku.com/v_show/id_XMjY2ODQwOTQzNg==.html
		// v.youku.com/v_show/id_XNjkxNDI3OTI=.html
		if ( preg_match("/v\.youku\.com\/[0-9a-zA-Z\_]+\/id_([0-9a-zA-Z]+)/", $content, $match) ) {
			return $match[1];
		}

		// player.youku.com/embed/XMjY2ODQwOTQzNg==
		if ( preg_match("/player\.youku\.com\/[0-9a-zA-Z]+\/([0-9a-zA-Z]+)=/", $content, $match ) ) {
			return $match[1];
		}

		return $url;
	}


	static function qqvID( $url, $content=null ) {
		

		// https://v.qq.com/x/cover/6alzwjlgc7h2x6v/o0760sjd893.html
		if ( preg_match("/v\.qq\.com\/x\/cover\/[0-9a-zA-Z]+\/([0-9a-zA-Z]+)\.html/", $url, $match ) ) {
			return $match[1];
		}

		// v.qq.com/iframe/player.html?vid=o0760sjd893&tiny=0&auto=0
		if ( preg_match("/v\.qq\.com\/iframe\/player\.html\?vid=([0-9a-zA-Z]+)/", $content, $match) ) {
			return $match[1];
		}

		return $url;
	}

	static function hmDateTime( $datetime, $fmt='Y年m月d日' ) {
        if ( empty($datetime) ) {
            $datetime = date('Y-m-d H:i:s');
        }
        
		$datetime = str_replace('@', '', $datetime );
		$ts = strtotime($datetime);
		return date($fmt, $ts);
    }
    
    static function UrlEncode( $string ) {
		return urlencode( $string );
	}

	static function length( $value ) {

		if ( is_string($value) ) {
			return strlen($value);
		} else if ( is_array($value) ) {
			return count($value);
		}

		return 0;
	}

	static function trim( $string ) {
		$string = str_replace("\n", "", $string );
		$string = str_replace("\r", "", $string );
		return trim( $string );
    }
    
  

	static function implode( $split ,$array ) {
		if ( is_array($array) ) {
			return implode($split, $array);
		}
		return $array;
	}

	static function explode(  $split ,$string ) {
		if ( is_string( $string) ) {
			return explode($split, $string);
		}
		return $string;
	}


	static function getPath( $name, $ext ) {
		$folder = "/{$name[0]}{$name[1]}/{$name[2]}{$name[3]}";
		return "{$folder}/{$name}.{$ext}";
	}

	static function getExt( $file_name ) {
		$arr = explode('.',$file_name);
		$ext = strtolower(array_pop($arr));
		return $ext;
	}


	static function replace( $search, $replace, $subject ) {
		return str_replace($search, $replace, $subject );
	}

	static function substr( $str, $start, $length, $tail='' ) {

        if ( count( $str) < $length ) {
            return $str;
        }

		return mb_substr( $str, $start, $length ) .  $tail;
	}

	static function home() {
		$proto ='http://';
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            $proto = 'https://';
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $proto = 'https://';
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
            $proto = 'https://';
        }
        return $proto . $_SERVER["HTTP_HOST"];
	}


	static function getCacheName( $name, $param=[] ) {
		sort( $param );
		$param_string = serialize($param) ;   // serialize 比 json 效率高
		$string = hash('md4',  $param_string);   // MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return $name . ":" . $string;
	}
}



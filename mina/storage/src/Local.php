<?php
/**
 * MINA Pages 对象存储-本地存储
 * 
 * @package      \Mina\Storage
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Storage;

use \Exception;
use Mina\Storage\Base;


class Local extends Base {

	private $mode = 0660;
	private $info_ttl = 0;
    private $raw_ttl = 0;
    
    protected static $loc_files = array();

    public static function lockFile($file_name, $wait = false) {
        $loc_file = fopen($file_name, 'c');
        if ( !$loc_file ) {
            throw new \Exception('Can\'t create lock file!');
        }
        if ( $wait ) {
            $lock = flock($loc_file, LOCK_EX);
        } else {
            $lock = flock($loc_file, LOCK_EX | LOCK_NB);
        }
        if ( $lock ) {
            self::$loc_files[$file_name] = $loc_file;
            fprintf($loc_file, "%s\n", getmypid());
            return $loc_file;
        } else if ( $wait ) {
            throw new \Exception('Can\'t lock file!');
        } else {
            return false;
        }
    }

    public static function unlockFile($file_name) {
        fclose(self::$loc_files[$file_name]);
        @unlink($file_name);
        unset(self::$loc_files[$file_name]);
    }


	/**
	 * URL 处理函数
	 * 		["url"] function( $path, $options){ return $path; }
	 * 		["origin"] function( $path, $options){ return $path; }
	 * @var array
	 */
	private $fn = []; 


	/**
	 * 对象存储
	 * @param array $options 配置选项
	 *         	     string  ["prefix"]  对象存储前缀，默认为""
	 *         	     string  ["mode"]    文件权限，默认为 0660
	 *     string | function ["url"]     文件(CDN)访问根地址，或回调函数 function( $path, $options ){ }
	 *     string | function ["origin"]  文件(原始)访问根地址，或回调函数 function( $path, $options ){ }
	 * 
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
	 *      		        int	 ["cache"]["info"] 对象信息缓存过期时间， 默认为 0，永不过期
	 *      		        int	 ["cache"]["raw"]  对象数据缓存过期时间， 默认为 0，永不过期
	 */
	function __construct( $options = [] ) {

		parent::__construct( $options );

		if ( empty($this->prefix) ) {
			throw new Exception("请设置根目录参数( prefix ) 数值。 ", 500 );
		}

		if ( isset( $this->options['mode'] ) ) {
			$this->mode = $this->options['mode'];
		}

		if ( !empty($this->options["cache"]["info"]) ) {
			$this->info_ttl = intval($this->options["cache"]["info"]);
		}

		if ( !empty($this->options["cache"]["raw"]) ) {
			$this->raw_ttl = intval($this->options["cache"]["raw"]);
		}


		/**
		 * 处理 URL 和 Origin 参数
		 */
		$this->options['url'] = !empty($this->options['url']) ? $this->options['url'] : "";
		$this->options['origin'] = !empty($this->options['origin']) ? $this->options['origin'] : $this->options['url'];

		$this->fn['url'] = $this->options['url'];
		$this->fn['origin'] = $this->options['origin'];

		// 定义 URL 和 Origin 处理函数
		if ( is_string($this->options['url']) ) {
			$this->fn['url'] = function( $path, $options ) {
				return $options['url'] . $path;
			};
		}
		if ( !is_callable($this->fn['url']) ) {
			throw new Exception("URL 参数错误 ({$this->options['url']})", 500 );
		}

		if ( is_string($this->options['origin']) ) {
			$this->fn['origin'] = function( $path, $options ) {
				return $options['origin'] . $path;
			};
		} 
		if ( !is_callable($this->fn['origin']) ) {
			throw new Exception("ORIGIN 参数错误 ({$this->options['origin']})", 500 );
		}

	}


	public function ping() {

		if ( !is_writeable($this->prefix) ) {
			return false;
		}

		return true;
	}



	public function append( $path, $blob ) { 

		$file = $this->_file( $path );
		
		$dir = dirname( $file );

		if ( is_dir($dir) === false ) {
			@mkdir($dir, 0777, true);
		}

		if ( is_writeable( $dir ) === false ) {
			throw new Exception("没有写入权限 ($dir)", 403 );
		}

		if ( file_put_contents($file, $blob, FILE_APPEND) === false ) {			
			throw new Exception("写入文件出错 ($file)", 500);
		}

		chmod($file, $this->mode );
		$this->_clear_cache( $path );
		return $this->get( $path );
	}



	public function insert( $path, $blob, $from=0, $total=null ) {

		$file = $this->_file( $path );

		$dir = dirname( $file );
		if ( is_dir($dir) === false ) {
			@mkdir($dir, 0777, true);
		}

		if ( is_writeable( $dir ) === false ) {
			throw new Exception("没有写入权限 ($dir)", 403 );
		}

		// 计算文件大小
		$blob_size = strlen( $blob );
		$total = empty( $total ) ?  $blob_size : $total;
		$complete = $total; 

		// 插入文件
		if ( $from != 0 ) {
			$fp = fopen($file, 'a');
		} else {
			$fp = fopen($file, 'w');	
		}

		fseek($fp, $from);
		fwrite($fp, $blob);
		fclose( $fp );

        // header("filepath: from={$from}  blob_size={$blob_size}  total={$total} complete={$complete} : {$path} ");

        // 所有文件保存完毕 返回 array
        $complete = $blob_size +  $from;  // 数据可能会有延迟，下一步实现易步锁
		if ( $complete >= $total ) {
			unlink( @$cnt_file);
			chmod($file, $this->mode );
			$this->_clear_cache( $path );
			return $this->get( $path );
		}

		return true;
	}



	public function upload( $path, $raw, $replace = true ) {

		$file = $this->_file( $path );

		if ( file_exists( $file ) ) {
			if ( $replace === true ) {
				if ( !is_writeable( $file ) ) {
					throw new Exception("没有写入权限 ($file)", 403 );
				}

				if ( file_put_contents($file, $raw) === false ) {
					throw new Exception("写入文件出错 ($file) ", 500);
				}

				chmod($file, $this->mode );
				$this->_clear_cache( $path );
				return $this->get( $path );
			}

			return false;
		}


		$dir = dirname( $file );

		if ( is_dir($dir) === false ) {
			@mkdir($dir, 0777, true);

			// if (  === false ) {
			// 	throw new Exception( "创建文件目录出错 ($dir)", 500);
			// }
		}

		if ( is_writeable( $dir ) === false ) {
			throw new Exception("没有写入权限 ($dir)", 403 );
		}

		if ( file_put_contents($file, $raw) === false ) {			
			throw new Exception("写入文件出错 ($file)", 500);
		}

		chmod($file, $this->mode );
		$this->_clear_cache( $path );
		return $this->get( $path );
	}


	public function get( $path, $nocache=false ) {

		if ( $nocache === false  && !empty($this->cache) ) {
			$info = $this->cache->getJSON("{$path}:info");
			if ( $info !== false )  {
				return $info;
			}
		}
		
		$info = [
			"url" => $this->fn['url']( $path, $this->options ),
			"origin" => $this->fn['origin']( $path, $this->options ),
			"mime" => $this->getMimeTypeByName( $path ),
			"path"=>$path,
			"local" => $this->local($path)
		];

		if ( $nocache === false  && !empty($this->cache) ) {
			$this->cache->setJSON("{$path}:info", $info, $this->info_ttl );
		}

		return $info;
	}

	public function getBlob( $path, $nocache=false ) {

		// $chunk = 4096;

		if ( $nocache === false  && !empty($this->cache) ) {
			$raw = $this->cache->get("{$path}:raw");
			if ( $raw !== false )  {
				return $raw;
			}
		}

		$file = $this->_file( $path );
		if ( !file_exists( $file ) ) {
			throw new Exception("文件不存在 ($file)", 404);
		}

		if ( !is_readable( $file ) ) {
			throw new Exception("没有阅读权限 ($file)", 403 );
		}

		$content = file_get_contents($file);

		if ( $nocache === false  && !empty($this->cache) ) {
			$this->cache->set("{$path}:raw", $content, $this->raw_ttl );
		}

		return $content;
	}

	public function remove( $path, $recursive=true, $ignore_notexist = true ){

		$file = $this->_file( $path );
		$this->_remove($file, $recursive, $ignore_notexist);
		$this->_clear_cache( $path );
		return true;

	}


	public function local( $path ) {
		return realpath($this->_file( $path ));
	}


	private function _remove( $file, $recursive,  $ignore_notexist ){
		
		if ( !file_exists($file) ) {
			return $ignore_notexist;
		}

		if ( !is_writeable($file) ) {
			throw new Exception("没有写入权限 ($file)", 403 );
		}

		if ( is_dir($file) && $recursive === true ) {

			 if ($dh = opendir($file)) {

			 	while (($childFile = readdir($dh)) !== false) {
			 		if ( $childFile != '.' && $childFile != '..' ) {
			 			$this->_remove($file . '/'. $childFile,  $recursive,  $ignore_notexist );
			 		}
			 	}
			 	closedir($dh);
			 	rmdir($file);
			 }

		} else {
			if ( unlink($file) === false ) {
				throw new Exception("删除文件失败 ($file)", 500 );;
			}
		}
	}


	public function refresh( $path ){ 
		return $this->_clear_cache( $path); 
	}

	public function isExist( $path ) {
		return file_exists($this->_file( $path )); 
	}



	// === 工具函数 ======
	private function _file( $path ) {
		return ($this->prefix . $path);
	}

	private function _clear_cache( $path ) {
		if ( !empty( $this->cache) ) {
			return $this->cache->delete($path);
		}

		return true;
	}

}
<?php
/**
 * MINA  App Gateway 本地调用模式
 * 
 * @package      \Mina\Gateway
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Gateway;
use Mina\Gateway\Base;
use \Exception;
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );

class Local extends Base {

	function __construct( $options = [] ) {
		parent::__construct($options);

		$this->mt = new \Mimey\MimeTypes;
	}

	/**
	 * 读取静态文件
	 * @param  string $path 文件名称
	 * @return $this
	 */
	function file( $path ) {

		if ( $this->isAccessable( $path ) === false ) {
			header('Content-Type: application/json');
			$error = [
				"code" => 403,
				"message" => "文件禁止访问",
				"extra" => [
					"path" => $path
				]
			];
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return  $this;
		}

		$root = $this->app['path'];
		$file = "{$root}{$path}";

		if (!file_exists($file) ) {
			header('Content-Type: application/json');
			$error = [
				"code" => 404,
				"message" => "文件不存在",
				"extra" => [
					"path" => $path
				]
			];
			echo json_encode($error, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES);
			return $this;

		} 

		$pi = pathinfo($file);
		$content_type = $this->mt->getMimeType( $pi['extension']);
		$content = file_get_contents($file);


		$this->response['http_error'] = "";
		$this->response['content'] = $content;
		$this->response['data'] = null;
		$this->response['code']  = 200;
		$this->response['message'] = "" ;
		$this->response['extra'] = [];
		$this->response['trace'] = [];
		$this->response['type'] = $content_type;

		Header("Content-Type: {$content_type}");
		echo $content;

		return $this;
	}

	/**
	 * 透明代理方式，访问应用控制器
	 * @param  string $ctr 控制器
	 * @param  string $act Action
	 * @return $this
	 */
	function transparent( $ctr=null, $act=null ) {
		
		$this->setRouter($ctr, $act);
		$root = $this->app['path'];
		if( !defined('APP_ROOT') ) define('APP_ROOT', $root);

		$headers = $this->parserHeader();
		$headers['Gateway-Type'] = 'local/transparent';
		$seroot = $headers['Xpmse-Service'];

		// 自动载入脚本
        require_once( $seroot . DS . 'loader' . DS  . "Autoload.php" );
        
        \Xpmse\Loader\Auto::run( $headers,  $this->params['data'] );
        
		$this->response['code'] = 0;

		return $this;
	}



	/**
	 * 访问应用控制器，读取内容
	 * @param  string $ctr 控制器
	 * @param  string $act Action
	 * @return $this
	 */
	function fetch($ctr=null , $act=null ) {
		
		$this->setRouter($ctr, $act);
		$root = $this->app['path'];
		if( !defined('APP_ROOT') ) define('APP_ROOT', $root);

		$headers = $this->parserHeader();

		// print_r($headers );

		// return $this;
		$headers['Gateway-Type'] = 'local/fetch';
		$seroot = $headers['Xpmse-Service'];

		// 自动载入脚本
		require_once( $seroot . DS . 'loader' . DS  . "Autoload.php" );


		ob_start();
		$data = \Xpmse\Loader\Auto::run( $headers,  $this->params['data'] );
		$content = ob_get_contents();
		ob_end_clean();

		$this->response['code'] = 0;
		$this->response['content'] = $content;
		$this->response['data'] = $data;
		return $this;
	}




	function parserHeader() {
		$headers = [];
		foreach( $this->params['header'] as $hd ) {
			$arr = explode(':', $hd);
			$key = $arr[0];
			$v  = $arr[1];

			if ( count($arr) > 2 ) {
				unset($arr[0]);
				$v = implode(':', $arr);
			}
			$headers[$key] = trim($v);
		}

		return $headers;
	}


	

	/**
	 * 读取上一次请求执行结果
	 * @return array $response
	 *         	 
	 *         	 $response["code"] 返回结果代码，成功返回0 失败返回其他数值
	 *         	 
	 *           ==== 请求成功相关 ====
	 *           $response["content"] 应用控制器输出内容
	 *           $response["type"] 输出内容的 Content-Type 
	 *           $response["data"] 应用控制器函数 Return 数据
	 * 
	 * 			 ==== 请求失败相关 =====
	 *         	 $response["message"]  请求失败原因，若访问成功返回 0
	 *         	 $response["http_error"] 请求失败原因，一般为请求未到达服务器的原因，比如域名解析错误。
	 *         	 $response['extra'] 请求失败的扩展信息
	 *         	 $response['trace'] 请求失败的代码追溯信息
	 *         	 
	 */
	function get() {
		return $this->response;
	}



	private function mimetype( $filename ) {
		if(!function_exists('mime_content_type')) {

			function mime_content_type($filename) {

				$mime_types = array(

					'txt' => 'text/plain',
					'htm' => 'text/html',
					'html' => 'text/html',
					'php' => 'text/html',
					'css' => 'text/css',
					'js' => 'application/javascript',
					'json' => 'application/json',
					'xml' => 'application/xml',
					'swf' => 'application/x-shockwave-flash',
					'flv' => 'video/x-flv',

					// images
					'png' => 'image/png',
					'jpe' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'gif' => 'image/gif',
					'bmp' => 'image/bmp',
					'ico' => 'image/vnd.microsoft.icon',
					'tiff' => 'image/tiff',
					'tif' => 'image/tiff',
					'svg' => 'image/svg+xml',
					'svgz' => 'image/svg+xml',

					// archives
					'zip' => 'application/zip',
					'rar' => 'application/x-rar-compressed',
					'exe' => 'application/x-msdownload',
					'msi' => 'application/x-msdownload',
					'cab' => 'application/vnd.ms-cab-compressed',

					// audio/video
					'mp3' => 'audio/mpeg',
					'qt' => 'video/quicktime',
					'mov' => 'video/quicktime',

					// adobe
					'pdf' => 'application/pdf',
					'psd' => 'image/vnd.adobe.photoshop',
					'ai' => 'application/postscript',
					'eps' => 'application/postscript',
					'ps' => 'application/postscript',

					// ms office
					'doc' => 'application/msword',
					'rtf' => 'application/rtf',
					'xls' => 'application/vnd.ms-excel',
					'ppt' => 'application/vnd.ms-powerpoint',

					// open office
					'odt' => 'application/vnd.oasis.opendocument.text',
					'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
				);

				$ext = strtolower(array_pop(explode('.',$filename)));
				if (array_key_exists($ext, $mime_types)) {
					return $mime_types[$ext];
				}
				elseif (function_exists('finfo_open')) {
					$finfo = finfo_open(FILEINFO_MIME);
					$mimetype = finfo_file($finfo, $filename);
					finfo_close($finfo);
					return $mimetype;
				}
				else {
					return 'application/octet-stream';
				}
			}
		}

		return mime_content_type( $filename );
	}

}
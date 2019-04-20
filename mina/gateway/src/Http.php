<?php
/**
 * MINA Http App Gateway Http 代理模式
 * 
 * @package      \Mina\Gateway
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Gateway;
use Mina\Gateway\Base;
use \Exception;

class Http extends Base {

	private $_request_retry = 0;
	private $_http_response = [];

	function __construct( $options = [] ) {
		
		// Http 方式需要开启签名
		if ( !array_key_exists('sign', $options) ) {
			$options['sign'] = true;
		}

		parent::__construct($options);
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
			return $this;
		}

		$home = dirname($this->app['proxy_script']);
		$url = "$home{$path}";
		$this->Request('GET', $url);
		foreach ($this->_http_response['response_header'] as $hd ) {
			@header($hd);
		}

		$this->response['http_error'] = $this->_http_response['http_error'];
		$this->response['content'] = $this->_http_response['body'];
		$this->response['data'] = $this->_http_response['data'];
		$this->response['code']  = $this->_http_response['code'];
		$this->response['message'] = $this->_http_response['message'];
		$this->response['extra'] = $this->_http_response['extra'];
		$this->response['trace'] = $this->_http_response['trace'];
		$this->response['type'] = $this->_http_response['response_header_map']['Content-Type'];
		echo $this->_http_response['body'];
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

		// 获取请求地址
		$url = $this->app['proxy_script'];
		if ( empty($url) || $this->app['proxy_on'] != 1 ) {
			throw new Exception("无效请求, 应用不能使用代理模式访问", 403);
		}

		// 获取请求地址
		$this->Request( 'POST',  $url, [
			'header'=>array_merge($this->params['header'], ["Gateway-Type: http/fetch"] ),
			'follow'=>false,
			'data'=>$this->params['data'],
			'type'=>'json',
			'datatype'=>'json'
		]);

		// Set-Cookie
		if ( !empty($this->_http_response['response_header_map']['Set-Cookie']) ) {
			@header("Set-Cookie: {$this->_http_response['response_header_map']['Set-Cookie']}");
		}

		$this->response['http_error'] = $this->_http_response['http_error'];
		$this->response['content'] = $this->_http_response['content'];
		$this->response['data'] = $this->_http_response['data'];
		$this->response['code']  = $this->_http_response['code'];
		$this->response['message'] = $this->_http_response['message'];
		$this->response['extra'] = $this->_http_response['extra'];
		$this->response['trace'] = $this->_http_response['trace'];
		$this->response['type'] = $this->_http_response['response_header_map']['Content-Type'];

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

		// 获取请求地址
		$url = $this->app['proxy_script'];
		if ( empty($url) || $this->app['proxy_on'] != 1 ) {
			throw new Exception("无效请求, 应用不能使用代理模式访问", 403);
		}

		// 发起请求
		$this->Request( 'POST',  $url, [
			'header'=>array_merge( $this->params['header'], ["Gateway-Type: http/transparent"] ),
			'follow'=>false,
			'data'=>$this->params['data'],
			'type'=>'json',
			'datatype'=>'json'
		]);

		$this->response['http_error'] = $this->_http_response['http_error'];
		$this->response['content'] = $this->_http_response['body'];
		$this->response['message'] = $this->_http_response['message'];
		$this->response['data'] = $this->_http_response['data'];
		$this->response['extra'] = $this->_http_response['extra'];
		$this->response['trace'] = $this->_http_response['trace'];
		$this->response['code']  = $this->_http_response['code'];
		$this->response['type'] = $this->_http_response['response_header_map']['Content-Type'];

		foreach ($this->_http_response['response_header_map'] as $key=>$hd ) {
			// echo "{$key}: {$hd}" . "\n";
			@header("{$key}: {$hd}");
		}
		echo $this->_http_response['body'];
		return $this;
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


	/**
	 * Requst 方法 ( DNS缓存在内存中 )
	 * @param [type] $method 请求方法 POST / GET / PUT /DELETE
	 * @param [type] $url   请求地址 http://xxxx.com
	 * @param [type] $opt   header HTTP REQUEST 头  ['ak: xxxxds', 'sk: xxx' ]
	 *					  query HTTP GET查询参数 ['name'=>'value','name2'=>'value2']
	 *					  data  HTTP POST查询参数 ['name'=>'value','name2'=>'value2']
	 *					  type  HTTP REQUEST TYPE 默认为 form . 有效数值 form/json/raw/media
	 *					  datatype HTTP RESPONSE TYPE 默认为json. 有效值 json/html/auto/xml  
	 *					  follow 是否抓取301/302之后的地址 默认为true. 有效值 true/false	CURLOPT_FOLLOWLOCATION
	 *					  nocheck 是否验证HTTP状态码（默认是false)
	 *					  urlencode 是否 ENCODE QUERY 参数（默认是true)
	 *					  dnscache 是否开启DNS缓存 默认为true. 有效值 true/false
	 *					  curlopt = [] 其他 CURLOPT_*  ( KEY => VALUE)
	 *					  cert 双向认证证书路径
	 *					  cert.key 双向认证证书私钥路径
	 *					  cert.keytype 证书类型，默认为 PEM
	 *					  rootca CA证书 (验证服务器证书的真实性)
	 *					  
	 *					  
	 * @return string/array datatype = json 返回数组 
	 *					  datatype = html 返回 RESPONSE Body String
	 *					  datatype = auto 返回 [ "body"=>RESPONSE Body String, "type"=>Content-Type ]
	 */	
	private function Request( $method, $url, $opt=[] ) {

		$ch = curl_init();
		$options = array();
		$resp_body = array();

		$header = (isset( $opt['header'] ) ) ? $opt['header'] : [];
		$query = (isset( $opt['query'] ) ) ? $opt['query'] : [];
		$data = (isset( $opt['data'] ) ) ? $opt['data'] : [];
		$requestType =(isset( $opt['type'] ) ) ? strtolower($opt['type']) : 'form';
		$responseType =(isset( $opt['datatype'] ) ) ? strtolower($opt['datatype']) : 'json';
		$nocheck = (isset( $opt['nocheck'] ) ) ? strtolower($opt['nocheck']) : false;
		$debug = (isset( $opt['debug'] ) ) ? $opt['debug'] : false;

		$urlr = parse_url($url);
		$urlr['path'] = ( isset($urlr['path']) ) ? $urlr['path'] : "";
		$host_name = $urlr['host'];
		$user = ( isset($urlr['user']) && $urlr['user'] != "")? "{$urlr['user']}@" : ""; 
		$user = (  isset($urlr['user']) &&  isset($urlr['pass']) && $urlr['user'] != "" && $urlr['pass'] != "")? "{$urlr['user']}:{$urlr['pass']}@" : $user;
		$port = ( isset($urlr['port']) && $urlr['port'] != "")? ":{$urlr['port']}" : "";
		$options['url'] = "{$user}{$host_name}{$port}{$urlr['path']}";

		// CURL 的可选参配置
		$opt['follow'] = (isset($opt['follow'])) ? $opt['follow'] : true;
		$opt['dnscache'] = (isset($opt['dnscache'])) ? $opt['dnscache'] : true;
		$opt['curlopt'] = (isset($opt['curlopt'])) ? $opt['curlopt'] : [];
		$opt['urlencode']= (isset($opt['urlencode'])) ? $opt['urlencode'] : true;


		// 缓存Host IP加速请求速度
		$mem = $this->cache;
		if( $opt['dnscache']  && $this->cache != null)  {
			$host_ip = $mem->get("$host_name");
			if ( $host_ip === false )  {
				$host_ip = gethostbyname($host_name);
				$expires_at = 2592000; // 30天后过期
				$mem->set("$host_name", $host_ip, $expires_at );
			}
			$options['url'] = str_replace($host_name, $host_ip, $options['url']);
		}


		// HTTPS 配置选项
		if ($urlr['scheme'] == 'https') {

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // 不验证证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 不验证域名匹配关系
			
			// CA根证书（用来验证的网站证书是否是CA颁布
			if ( isset( $opt['rootca']) && file_exists( $opt['rootca'] ) ) {
				 curl_setopt($ch, CURLOPT_CAINFO, $cacert);
			}

			// 双向验证 KEY 
			if ( isset( $opt['cert.key']) && file_exists( $opt['cert.key'] ) ) {

				$opt['cert.keytype']  = empty($opt['cert.keytype']) ? 'PEM' : $opt['cert.keytype'];
				
				curl_setopt($ch,CURLOPT_SSLKEYTYPE,$opt['cert.keytype']);
				curl_setopt($ch,CURLOPT_SSLKEY, $opt['cert.key'] );
			}

			// 双向验证 证书
			if ( isset( $opt['cert']) && file_exists( $opt['cert'] ) ) {
				curl_setopt($ch,CURLOPT_SSLCERT, $opt['cert']);
			}

		}



		// Query String 解析
		$query_string_arr = [];
		if( isset($urlr['query'] )  && $urlr['query']  != "" ) {
		   	$query_string_arr = explode('&', $urlr['query']);
		}
		foreach ( $query as $key => $value) {
			if ( $opt['urlencode'] == true) {
				$value = urlencode($value);
			}
			$query_string_arr[] = "$key=$value";
		}
		$query_string = implode('&', $query_string_arr );
		$options['url'] = "{$urlr['scheme']}://{$options['url']}";
		if ( $query_string != "" ) {
			$options['url'] = $options['url'] . "?$query_string";
		}

		
		// POST Data 解析
		$postfields = null;

		if ( count($data) > 0  ) {
			switch ($requestType) {
				case 'form':
					// $options['body'] = $data;
					$postfields = $data;
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
					break;

				case 'media':
					srand((double)microtime()*1000000); 
					$boundary = '------WebKitFormBoundary'.substr(md5(rand(0,32000)),0,10); 
					$header[] = "Content-Type: multipart/form-data; boundary=$boundary"; 
					$content = '--'.$boundary."\r\n";
					$files = (isset($data['__files'])) ? $data['__files'] : [];
					if (isset($data['__files'])) {
						unset( $data['__files'] );
					}

					// Form Data
					$formdata = '';
					foreach ($data as $key => $val) {
						$formdata .= "Content-Disposition: form-data; name=\"".$key."\"\r\n"; 
						$formdata .= "Content-Type: text/plain\r\n\r\n"; 
						if(is_array($val)){ 
							$formdata .= json_encode($val)."\r\n"; // 数组使用json encode后方便处理 
						}else{ 
							$formdata .= rawurlencode($val)."\r\n"; 
						} 
						$formdata .= '--'.$boundary."\r\n"; 
					}

					// Files
					$filedata = ''; $filedata_debug = '';

					foreach($files as $val){ 
						$val['filename'] = isset($val['filename']) ? basename($val['filename']) : 'unknown.tdm';
						$val['name'] = isset( $val['name'] ) ? $val['name'] : 'tdm_file';
						$val['mimetype'] = isset( $val['mimetype'] ) ? $val['mimetype'] : mime_content_type($val['filename']);
					   
						$filedata .= "Content-Disposition: form-data; name=\"".$val['name']."\"; filename=\"".$val['filename']."\"\r\n"; 
						$filedata .= "Content-Type: ".$val['mimetype']."\r\n\r\n";
						$filedata .= $val['data']."\r\n"; 
						$filedata .= '--'.$boundary.""; 

						$filedata_debug .= "Content-Disposition: form-data; name=\"".$val['name']."\"; filename=\"".$val['filename']."\"\r\n"; 
						$filedata_debug .= "Content-Type: ".$val['mimetype']."\r\n\r\n";
						$filedata_debug .= base64_encode($val['data'])."\r\n"; 
						$filedata_debug .= '--'.$boundary.""; 
					}

					$content.= $formdata.$filedata."--\r\n\r\n"; 
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					$postfields = $formdata.$filedata_debug."--\r\n\r\n";
					curl_setopt($ch, CURLOPT_POSTFIELDS, $content );

					break;

				case 'json':
					$postfields = json_encode($data);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data) );
					break;

				case 'raw':
					$postfields = json_encode($data, JSON_UNESCAPED_UNICODE);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE) );
					break;
				default:
					curl_setopt($ch, CURLOPT_POST, 1);
					$postfields = $data;
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
					break;
			}
		}


		 // 请求地址和Header
		$header[] = "Host: $host_name";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $options['url'] );

		// CURL 的其他配置项
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //设置连接超时时间为5妙
		curl_setopt($ch, CURLOPT_FAILONERROR, FALSE );
		curl_setopt($ch, CURLOPT_NOBODY, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE); // RESPONSE Header

			// 是否抓取跳转之后的页面
			if ( $opt['follow'] ) {  
				  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
			}

		// CURL 自定义配置项
		foreach ($opt['curlopt'] as $key => $value) {
			eval("\$opt_name = CURLOPT_$key;");
			curl_setopt($ch, $opt_name, $value);
		}


		$respData = curl_exec($ch);
		
		// 响应头
		$respHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$respHeader = substr($respData, 0, $respHeaderSize);
		$respHeaders = array_filter(explode("\n", $respHeader));
		$respHeaderMap = [];

		

		foreach ($respHeaders as $hd) {
			$kv = explode(':', $hd);
			$cnt = count($kv);

			if ( isset($kv[1]) ) {
				$k = trim($kv[0]);
				$v = trim($kv[1]);

				
				$vr = $kv;
				unset($vr[0]);
				$vs = trim(implode(':', $vr));
				$respHeaderMap[$k] = $vs;

				// 替换 SESSION ID
				if ( $k == 'Set-Cookie') {
					// echo "替换 SESSION ID {$vs} ";
					$sid = session_id();
					$vs = preg_replace("/PHPSESSID=(.+);/", "PHPSESSID={$sid};", $vs);
					// echo "为 SESSION ID {$vs} \n";
					$respHeaderMap[$k] = $vs;
				}
			}


		}

		$body = substr($respData, $respHeaderSize);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// 请求异常情况
		if ( $http_code == 0 ) {
			$http_code = 500 + intval(curl_errno( $ch ));
			$error = curl_error( $ch );
		}

		$this->_http_response['body'] = $body;
		$this->_http_response['http_error'] = $error;
		$this->_http_response['http_code'] = $http_code;
		$this->_http_response['response_header'] = $respHeaders;
		$this->_http_response['response_header_map'] = $respHeaderMap;

		if ( $responseType == 'json' || $respHeaderMap['Content-Type'] == 'application/json' ) {

			$this->_http_response['json'] = json_decode($body, true);

			if ( is_array($this->_http_response['json']) ) {
				if (   isset($this->_http_response['json']['code'])  &&
					   ( isset($this->_http_response['json']['content']) ||  isset($this->_http_response['json']['message']))
				) {
					$this->_http_response = array_merge($this->_http_response, $this->_http_response['json']);
					unset($this->_http_response['json']);
				}
			}
		}

		return $this;
	}
}
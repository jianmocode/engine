<?php

namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/data-driver/Data.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Secret;
use \Xpmse\Media;


/**
 * XpmSE RESTFul API 构造器
 */
class Api {

	protected $allowMethod = [];
	protected $allowQuery =[];
	protected $forbidden = [];
	protected $option = [];
	
	// 通过 JSON 传递的数据
	protected $params = [];

	// 当前实例
	protected $instance = "root";

	function __construct( $option = [] ) {
		
        $this->option = $option;
        $iscli = Utils::iscli();
        
        // 默认返回值
        $this->__setDataType( 'application/json' );
        
        if ( !$iscli ) {

            if (empty($_SERVER['HTTP_ORIGIN'])) {
                // var_dump("HTTP_ORIGIN : error");
                header("Access-Control-Allow-Origin: *");
            }else {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            }
            header("Access-Control-Allow-Headers: content-name, content-type,cache-control,x-requested-with,content-range,Content-Instance,content-disposition");
            header("Access-Control-Allow-Credentials: true");
            if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                exit;
            }
            
            $this->option["input"] = file_get_contents("php://input");
            if (  $_SERVER["HTTP_CONTENT_TYPE"] != "" ) {
                $requestContentType = current(explode(";",  $_SERVER["HTTP_CONTENT_TYPE"]));
            }
        }

        // 处理参数
        if ( $requestContentType == "application/json" ) {
            $this->params = json_decode($this->option["input"], true);
            if( $this->params === false ) {
                $this->params = [];
            }
        }
        
		// 读取当前实例信息
		$this->instance = $this->__getInstance();
	}


	/**
	 * 读取当前实例信息
	 * 优先级: 高 Request Header: Content-Instance  
	 * 优先级: 中 Request Header: Origin 
	 * 优先级: 低 PI DomainA
	 */
	private function __getInstance() {

		if ( !empty($_SERVER['HTTP_CONTENT_INSTANCE']) ) {
			return $_SERVER['HTTP_CONTENT_INSTANCE'];
		}

		$domain = $_SERVER['HTTP_ORIGIN'];
		if ( empty($domain) ) {
			$domain = $_SERVER["HTTP_HOST"];
		}
		
		// 解析domain
		$rootNames = ["api","www","root"];
		$cname = Utils::getCNAME( $domain, isset($_GET["__refresh"]) );
		$cname = explode(".", $cname);
		$instance = $cname[0];
		if ( !in_array($instance, $rootNames) ) {
			return $instance;
		}

		return "root";
    }
    

    /**
     * 设定返回值类型
     */
    public function __setDataType( $dataType ) {
        $GLOBALS['_RESPONSE-CONTENT-TYPE'] = $dataType;
    }

    /**
     * 读取返回值类型
     */
    public function __getDataType(){
        if ( empty($GLOBALS['_RESPONSE-CONTENT-TYPE']) ) {
            return 'application/json';
        }
        return $GLOBALS['_RESPONSE-CONTENT-TYPE'];
    }


    /**
     * 静态调用
     */
	public static function __callStatic($method,$arg) {  
        $api = new Self();
        $api->$method(...$arg );
    }

    protected function authToken( $appid="", $token="" ) {
    }

    /**
     * Secret 鉴权 ( Https Only )
     * @param  [type] $appid  [description]
     * @param  [type] $secret [description]
     * @return [type]         [description]
     */
    protected function authSecret( $appid, $secret = null ) {

    	if ( empty($secret) ) {
    		$arr = explode("|", $appid );
    		$appid = $arr[0];
    		$secret = $arr[1];
    	}

		if ( empty($secret) ) {
			throw new Excp("Secret 错误", 403, ['secret'=>$secret, 'appid'=>$appid]);
		}

    	$sc = new Secret;
		$secretReal = $sc->getSecret($appid);

		if ( $secretReal !== $secret ) {
			throw new Excp("Secret 错误", 403, ['secret'=>$secret, 'appid'=>$appid]);
		}
    }


    protected function __dropzone() {
        $query  = array_merge( $_GET, $_POST );
      
        if ( 
            array_key_exists("dztotalfilesize", $query)  &&
            array_key_exists("dzchunkbyteoffset", $query) 
        ) {
            $file = current( $_FILES );
            $field = str_replace('_file_', '',current( array_keys($_FILES)));  // 字段名称
            $client_name = $file['name'];  // 用户电脑文件名称
            $bytes= $file['size'];  // 本次上传文件大小
            $offset = $query["dzchunkbyteoffset"];
            $total = $query["dztotalfilesize"];
            $_SERVER['HTTP_CONTENT_RANGE'] = "content-range: bytes {$offset}-{$bytes}/{$total}";
            $_SERVER['HTTP_CONTENT_DISPOSITION'] = "content-disposition: attachment; filename=\"{$client_name}\"";

            $name = $query["dzcontentname"];
            if ( $name ) {
                $_SERVER['HTTP_CONTENT_NAME'] = $name;
            }
        }

        // Fix file type
        if ( count($_FILES) > 0 && array_key_exists("dzcontenttype", $query) ) {
            $type = $query["dzcontenttype"];
            $field = str_replace('_file_', '',current( array_keys($_FILES)));  // 字段名称
            if (!empty($type)) {
                $_FILES[$field]["type"] = $type;
            }
        }
    }



    /**
     * 处理上传文件
     * @param  string $option 上传选项 (支持断点)
     * @return file
     */
    protected function __savefile( $option = [] ) {

        // format __dropzone
        $this->__dropzone();

    	// 默认参数表
    	$default = [
    		"image" => ["image/png", "image/jpeg", "image/gif",  "image/svg", "image/svg+xml",  "image/svg"],
    		"video" => ["video/mpeg", "video/mp4"],
    		"private" => false,
    		"host" => "",
    		"cdn" => [],
    	];

    	$option = array_merge( $default, $option );
    	$mediaParams = [
    		"private" => $option["private"],
    		"cdn" => $option["cdn"],
    		"host"=> $option['host']
    	];

    	// 处理参数
    	$file = current( $_FILES );
    	if ( empty($file) ) {
    		throw new Excp("未接收到任何文件数据", 402, ['option'=>$option, '_FILES'=>$_FILES]);
    	}

    	$field = str_replace('_file_', '',current( array_keys($_FILES)));  // 字段名称
    	$mimetype = $file['type'];	// mimetype
    	$client_name = $file['name'];  // 用户电脑文件名称
    	$total = $size = $file['size'];  // 本次上传文件大小
    	$from = null;   // 本次启始位置
    	$to = null;		// 本次结束位置

    	// 文件类型 file / image / video
    	$type = 'file';
    	if ( in_array( $mimetype , $option['image']) ) {
    		$type = 'image';
    	} else if (in_array( $mimetype , $option['video']) ) {
    		$type = 'video';
    	}

    	$origin_unique_name = $field . time() . $client_name ;

    	// 断点续传选项
    	$content_range =  $_SERVER['HTTP_CONTENT_RANGE'];  // content-range: bytes 51200-102399/181879
    	$content_disposition = $_SERVER['HTTP_CONTENT_DISPOSITION'];  // content-disposition: attachment; filename="icon-sm.png"
    	if ( !empty($content_range) ){
    		if ( preg_match("/[ ]{1}([0-9]+)\-([0-9]+)\/([0-9]+)/", $content_range, $match ) ) {
    			$origin_unique_name = $field . $client_name . $content_disposition;
    			$from = intval($match[1]);
    			$to = intval($match[2]);
                $total = intval($match[3]);
                
    		}
    	}
        
    	// 文件名称
    	$mimes = Utils::mimes();
        $unique_name =  hash('md4',  $origin_unique_name);
        $mimetype = Utils::mimetypeExt($mimetype); 
    	$ext = $mimes->getExtension($mimetype);
    	if ( empty($ext) ) {
            header("Debug: mimetype={$mimetype}");
    		$ext = 'jmf';
        }
        
        $target_filename = !empty($_SERVER['HTTP_CONTENT_NAME']) ? $_SERVER['HTTP_CONTENT_NAME'] : "{$unique_name}.{$ext}" ;
        // $target_filename = "{$unique_name}.{$ext}";

    	// 保存文件
        $media = new Media( $mediaParams );
        // $md5 = md5( file_get_contents($file['tmp_name']));
        // $base64 = base64_encode( file_get_contents($file['tmp_name']));
    	$resp = $media->insert($file['tmp_name'], $target_filename, [
    		"total" => $total,
    		"from" => $from,
    		"to" => $to,
    		"type" => $type
        ]);
        
        // header("Debug: source={$file['tmp_name']}; target={$target_filename}; from={$from}; to={$to}; total={$total}; origin_unique_name={$origin_unique_name}");

        // var_dump( $content_range ) . "\n";
        // var_dump( $content_disposition ) . "\n";
        // print_r([
    	// 	"total" => $total,
    	// 	"from" => $from,
    	// 	"to" => $to,
    	// 	"type" => $type
    	// ]);

    	if ( $resp === true ) {  // 分段上传，某一断点部分上传完毕
    		return [
                "code"=>0,
                // "md5" => $md5,
                // "base64" => $base64,
    			"message"=> "保存成功",
    			"progress"=>$to/$total * 100,
    			"completed" => false,
    			
    			"data" => [
		    		"total" => $total,
		    		"from" => $from,
		    		"to" => $to,
		    		"type" => $type
	    		]
	    	];
    	}

    	// 整个文件上传完毕
    	return [
    		"code"=>0,
            "message"=> "保存成功",
            // "md5" => $md5,
            // "base64" => $base64,
    		"progress"=>100,
    		"completed" => true,
    		"data"=>$resp
    	];
    }


    /**
	 * 签名鉴权
	 * @return [type] [description]
	 */
	protected function auth( $params ) {

		$sc = new Secret;
		$secret = $sc->getSecret($params['appid']);
		$signature = $params['signature'];
		unset($params['signature'], $params['appid'], $params['n'],$params['c'],$params['a']);

		$ret = $sc->signatureIsEffect($signature, $params, $secret );

		if ( $ret === -1 ) {
			throw new Excp("请求已过期", 403, ['params'=>$params, 'signature'=>$signature]);
		} else if ( $ret === false ) {
			throw new Excp("请求签名错误", 403, ['params'=>$params, 'signature'=>$signature]);
		}
	}


	/**
	 * 验证码 鉴权
	 * @param  [type] $code [description]
	 * @param  string $key  [description]
	 * @return [type]       [description]
	 */
	protected function authVcodeOnly( $code = null, $param = '_vcode' ) {
		$this->authVcode( $code, $param, true );
	}

	protected function authVcode( $code = null, $param = '_vcode', $authonly=false ) {
		@session_start();
		$sscode = $_SESSION['_VCODE:' . $param];
		
		if ( $authonly === false ) {
			unset($_SESSION['_VCODE:' . $param]);
		}

		$code =  empty($code) ? $_REQUEST[$param] : $code;
		if ( empty($code) ) {
			throw new Excp('图形验证码不正确', 403, ['code'=>$code, 'param'=>$param, 'errorlist'=>[["$param"=>'图形验证码不正确']]]);
		}
		
		// 忽略大小写
		if ( strtolower($code) != strtolower($sscode) ) {
			throw new Excp('图形验证码不正确', 403, ['code'=>$code, 'param'=>$param,'errorlist'=>[["$param"=>'图形验证码不正确']]]);
		}
	}



	/**
	 * VCODE
	 * @return [type] [description]
	 */
	protected function vcode(  $query=[] ) {
		
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/image';
		@session_start();
		$param = empty($query['param']) ? '_vcode'  : $query['param'];
		$width = empty($query['width']) ? 120  : $query['width'];
		$height = empty($query['height']) ? 48  : $query['height'];
		$size = empty($query['size']) ? 18  : $query['size'];
		$length = empty($query['length']) ? 4 : $query['length'];

		$_vc = Utils::vcode(); 
		$_vc->length( $length );
		$code = $_vc->getCode();

		$_SESSION['_VCODE:' . $param] = $code;
		$_vc->doimg( $width, $height, $size);
    }
    
    /**
     * 读取Vcode信息，用于单元测试
     */
    final function __test_getvcode( $param = "_vcode", $length = 4){
        Utils::cliOnly();
        @session_start();
        $_vc = Utils::vcode(); 
		$_vc->length( $length );
        $code = $_vc->getCode();
        $_SESSION['_VCODE:' . $param] = $code;
        $_REQUEST[$param] = $code;
        return $code;
    }

    /**
     * 发起API请求
     */
	final function call( $method, $query=[], $data=[], $files=[] ) {

		if ( !method_exists(get_class($this), $method) ) {
			throw new Excp("API $method 不存在", 404,  ["method"=>$method, 'class_name'=>get_class($this)]);
        }

        // 废弃, 使用 $this->option["input"] 访问
		// $data['__input']=$this->option["input"];
		return $this->$method( $query, $data, $files );
	}


    /**
     * 校验是否允许调用 (即将废弃)
     */
	final function isForbidden( $method ) {
		return in_array($method, $this->forbidden);
	}

	/**
	 * 快速设定查询条件 (即将废弃)
	 */
	protected function qb( & $qb, $field, $name, $query, $method=['and','or'], $operator='=') {

		$Ucname = ucfirst($name);

		foreach ($method as $m ) {
			
			if ( $m == 'and' && isset($query[$name]) ) {

				$value = $query[$name];
				if ( $operator == 'like' ) {
					$value = "%{$value}%";
				}
				$qb->where($field,$operator, $value);

			} else if ( $m == 'or' && isset($query["or$Ucname"]) ){

				$value = $query["or$Ucname"];

				if ( $operator == 'like' ) {
					$value = "%{$value}%";
				}
				$qb->orWhere($field, $operator, $value);

			} else if ( $m == 'in' && isset($query["in$Ucname"])) {

				$str = $query["in$Ucname"];
				$arr = explode(',', $str);
				$qb->whereIn($field, $arr );
			}
		}
	}



	protected function forbidden( $methods ){
		$methods = is_array($methods) ? $methods : [$methods];
		$this->forbidden = array_merge($this->forbidden, $methods);
		return $this;
	}


	protected function allowMethod($api, $methods ) {

		$this->allowMethod[$api] = is_array($this->allowMethod[$api]) ? $this->allowMethod[$api] : [];
		$methods = is_array($methods) ? $methods : [$methods];
		$this->allowMethod[$api] = array_merge($this->allowMethod[$api], $methods);
		return $this;
	}

	protected  function allowQuery( $api, $fields) {
		$this->allowQuery[$api] = is_array($this->allowQuery[$api]) ? $this->allowQuery[$api] : [];
		$fields = is_array($fields) ? $fields : [$fields];
		$this->allowQuery = array_merge($this->allowQuery[$api], $fields);
		return $this;
	}

}
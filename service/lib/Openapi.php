<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
use \Xpmse\Model;
use \Xpmse\Validate;
use \Mina\Cache\Redis as Cache;

/**
 * XpmSE RESTFul API 构造器
 */
class Openapi {

    /**
     * Request Query String Map
     */
    static public $params = [];

    /**
     * 解析后的 Request Payload
     */
    static public $payload = [];

    /**
     * 当前实例
     */
    static public $instance = "root";

    
    /**
     * 配置信息
     */
    protected $option = [];

    /**
     * 数据缓存
     */
    protected $cache = null;

    /**
     * 构造函数
     * @param array $option 配置选项
     */
    function __construct( $option = [] ) {

        /**
         * 配置选项
         */
        $this->option = $option;
           
		// 读取当前实例信息
        self::$instance = $this->__getInstance();
        
        // 数据缓存
        $this->cache = new Cache( [
            "prefix" => "_system:api:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);
    }


    /**
     * 发起API请求
     */
	final function run( $method, $params=null, $payload=null ) {

        if ( $params === null ) {
            $params = self::$params;
        } else {
            self::$params = $params;
        }

        if ( $payload === null ) {
            $payload = self::$payload;
        } else {
            self::$payload = $payload;
        }

        $className = get_class($this);

        // 校验API方法是否存在
		if ( !method_exists($className, $method) ) {
			throw new Excp("API:{$api}不存在", 404, ["class_name"=>$className, "method"=>$method]);
        }

		return $this->$method( $params, $payload );
    }

    /**
     * 校验API权限
     * @param string $path 路径地址
     * @return 成功返回 true, 失败抛出异常
     */
    public static function AuthorizeRequest( $path ) {

        // GET Query String
        self::$params = $_GET;

        // Get Payload
        $contentType = trim(current(explode(";",$_SERVER["CONTENT_TYPE"])));

        // HTML Form format
        if ( $contentType == "multipart/form-data" || $contentType == "application/x-www-form-urlencoded" ) {
            self::$payload = $_POST;
        
        // JSON String
        } else if ( $contentType == "application/json" ) {
            $input = file_get_contents("php://input");
            
            self::$payload = [];
            if ( $input != null ) {
                self::$payload = json_decode( $input, true );
                if ( self::$payload === false ) {
                    throw new Excp("API:Payload解析错误", 400, ["path"=>$path, "input"=>$input]);
                }
            }
        
        // RAW Data
        } else {
            self::$payload = file_get_contents("php://input");
        }

        // 读取API配置
        $conf = self::getConfig( $path );
        self::ChekLimit( $conf["limit"]);
        self::Validate( $conf["validation"] );
        
        return true;
    }

    /**
     * 数据校验
     */
    public static function Validate( $validation ) {

        if ( empty( $validation) ) {
            return ;
        }

        if ( !is_array($validation) ){
            throw new Excp("API:数据验证配置格式错误", 400, ["validation"=>$validation]);
        }

        $required = []; // 必填项
        $atLeastOne = [];  // 至少包含一个
        $allowPayload = []; // Payload 许可字段
        $allowParams = []; // Params 许可字段
        $params = &self::$params;
        $payload = &self::$payload;

        // 根据规则校验数据
        $validate = new Validate();
        array_walk( $validation, function( $v, $field ) use($validate, &$required, &$atLeastOne, &$params, &$payload, & $allowParams, & $allowPayload) {
            
            // 读取数据 payload 优先
            $value = null;
            if ( $v["payload"] ) {
                $value = $payload[$field];
                array_push( $allowPayload, $field );
            }

            if ( $v["params"] != false ) {
                array_push( $allowParams, $field );
            }

            if ( $value == null && $v["params"] != false  ) {
                $value = $params[$field];
            }

            // 开始校验
            if ( !empty($v["rule"]) ) {
                $validate->check($field, $value, $v["rule"], $v["message"]);
            }

        });

        $validate->checkAtLeastOne();

        // 过滤未定义数值
        $validate->filter( $params, $allowParams );
        $validate->filter( $payload, $allowPayload );
        
    }

 

    /**
     * 配额校验
     */
    public static function ChekLimit( $limit ) {
    }




    /**
     * 发起测试请求
     */
    public static function test( $method, $api, $params=[], $payload=[], $files=[] ) {
        
        $home = Utils::getHome();
        $path = self::GetPath( get_called_class(), $api );    
        $url = "{$home}{$path}";

        $response = Utils::Request($method, $url, [
            "debug" => false,
            "nocheck" => true,
            "header" => ["Content-Type: application/json"],
            "type" => "json",
            "datatype" => "json",
            "query" => $params,
            "data" => $payload
        ]);

        // 返回错误数据
        if ( isset($response["http_code"]) && $response["http_code"] != 200 ) {
            print_r( $response );
            $error = $response["resp_body"];
            $error["status"] = $response["http_code"];
            return $error;
        }

        return $response;
    }


    /**
     * 类名换路径
     */
    public static function GetPath( $class_name, $api ) {
        $class = explode("\\", strtolower($class_name) );
        if ( count($class) == 4) {
            if ( strtolower($class[0])  == "xpmse" ) {
                unset( $class[0] );
                unset( $class[1] );    
            }
            unset($class[2]);
            return "/".implode("/", $class) . "/{$api}";
        }
        throw new Excp("请求来源错误", 400, ["class"=>$class]);
    }
    

    /**
     * 路径换类名+方法
     */
    public static function GetClass( $api ) {

        $class = array_map('trim', explode('/', $api));
        $class = array_values(array_filter( $class ));

        if ( count($class) == 2) {
            $className = "\\Xpmse\\Xpmse\\Api\\{$class[0]}";
            $method = $class[1];
        } else if ( count($class) == 4) {
            $className = "\\{$class[0]}\\{$class[1]}\\Api\\{$class[2]}";
            $method = $class[3];
        } else {
            throw new Excp("API:{$api}地址不正确", 404, ["class"=>$class]);
        }

        if ( !class_exists($className) ) {
            throw new Excp("API:{$api}不存在(Class不存在)", 404, ["class_name"=>$className, "method"=>$method]);
        }

        if ( !method_exists($className, $method) ) {
            throw new Excp("API:{$api}不存在(Method不存在)", 404, ["class_name"=>$className, "method"=>$method]);
        }

        return [
            "class" => $className,
            "method" => $method
        ];
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
     * Drop zone 格式文件上传
     */
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

    	$origin_unique_name = $field . $client_name;

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
     * 创建配置文件结构
     */
    public function __schema() {

        $openapi = new Model(["prefix"=>"core_"], "Mysql5_7");
        $openapi->table('openapi')
                ->putColumn( 'path', $openapi->type('string', ['length'=>64, "index"=>true, "null"=>false]) )
                ->putColumn( 'app', $openapi->type('string', ['length'=>64, "index"=>true, "null"=>false]) )
                ->putColumn( 'instance', $openapi->type('string', ['length'=>128, "default"=>"root", "index"=>true, "null"=>false]) )
                ->putColumn( 'slug', $openapi->type('string', ['length'=>128,"unique"=>true, "index"=>true]) )
                ->putColumn( 'limit', $openapi->type('json', []) )
                ->putColumn( 'validation', $openapi->type('json', []) )
                ->putColumn( 'method', $openapi->type('string', ["default"=>"GET", "length"=>10]) )
        ;
    }

    /**
     * 读取API配置
     */
    public static function getConfig( $path ) {

        $instances = array_unique([self::$instance, "root"]);


        $openapi = new Model(["prefix"=>"core_"], "Mysql5_7");
        $orders = "'" . implode("','", $instances) . "'";
        $rows = $openapi->table('openapi')
                        ->query()
                        ->where("path", "=", $path)
                        ->whereIn("instance", $instances)
                        ->orderByRaw("FIELD(instance, $orders)")
                        ->select( ["*"] )
                        ->limit(1)
                        ->get()
                        ->toArray()
        ;

        if ( empty($rows) ) {
            return [];
        }
        
        $row = current( $rows );
        return $row;
    }


    /**
     * 设定默认数值(初始化服务等)
     */
    public function __defaults(){

        
    }


    /**
     * 读取API配置文件
     * @return array $option
     * 
     */
    private function getOption( $api ) {

    }
    

    /**
     * 返回数据缓存名称
     */
    private function cacheName( $name  ) {
        return "{$name}";
    }

    /**
     * 从缓存中读取数据 
     * @param string $name 缓存名称
     * @param bool $json  是否为Json格式数据
     * @return mix 成功返回缓存中的数据, 失败返回 fasle
     */
    public function getCache( $name, $json=true ){

        $cache_name = $this->cacheName( $name );

        // 缓存
        if ( $json ) {
            return $this->cache->getJSON( $cache_name );
        }

        return $this->cache->get($cache_name);
    }

    /**
     * 设置数据缓存
     */
    public function setCache( $name, $data, $json=true ) {

        $cache_name = $this->cacheName( $name );
    
        // 缓存
        if ( $json ) {
            return $this->cache->setJSON( $cache_name, $data );
        }

        return $this->cache->set($cache_name, $data);
    }

}
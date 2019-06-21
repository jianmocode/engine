<?php
/**
 * YAO API加载器
 */

use \Yao\Route;
use \Yao\Excp;
use \Yao\Arr;
use \Yao\Db;
use \Mina\Router\Dispatcher;

defined("YAO_APP_ROOT") ?: define("YAO_APP_ROOT", "/apps");
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
ini_set('display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');

if ( !array_key_exists("_debug", $_GET) ) {
    $_GET["_debug"] = null;
}

// 载入YaoJS Backend 配置
if ( !array_key_exists("YAO", $GLOBALS) ) {
    $GLOBALS["YAO"] = include_once(__DIR__ . "/yao/config.inc.php");
}

error_reporting(E_ALL & ~E_NOTICE);


// 载入路由设定文件寻址 (正式上线时从配置文件中读取)
$domain_groups = [
    "vpin.biz" => [
        "default" => "/apps/vpin/backend/api/public",
        "kol" => "/apps/vpin/backend/api/kol",
        "vpin" => "/apps/vpin/backend/api/vpin",
        "agent" => "/apps/vpin/backend/api/agent",
    ]
];
$domain_groups["vpin.ink"] = $domain_groups["vpin.biz"];

// 读取域名信息
$host = $_SERVER["HTTP_HOST"];
$host_names = explode(".", $host);
$host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];

// 绑定的独立域名解析
if ( !in_array($host_name, ["vpin.biz", "vpin.ink"])){
    $cname = dns_get_record($host, DNS_CNAME);
    $host_names = explode(".", $host);
    $host_name = $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];
}

// 读取 group_map
$GLOBALS["YAO"]["group_map"] = $domain_groups["$host_name"];


// 注册自动载入
function handler_autoload($class_name ) {

    
	$class_arr = explode( '\\', $class_name );
    $namespace  = current($class_arr);
    
    if ( strtolower($namespace) == 'yao') { 
        $YAO_ROOT = __DIR__ . "/yao";
            
        // Vendor autoload
        $autoload = realpath("{$YAO_ROOT}/vendor/autoload.php");
        include_once($autoload);
        
        // Class Name
        $class = array_pop($class_arr);
        array_shift( $class_arr);
        
        // Source Path
        $path = strtolower(implode("/", $class_arr));
        $src_path = !empty($path) ? "src/{$path}" : "src";
        $class_file = ucfirst(strtolower($class)) . '.php';
        $class_path_file = "{$YAO_ROOT}/{$src_path}/{$class_file}";
        include_once($class_path_file);

    // Load Mina SDK
    } else if ( strtolower($namespace) == 'mina' && is_string($class_arr[1]) 
        && in_array($class_arr[1], ['Storage', 'Template', 'Cache', 'Router', 'Delta', 'Gateway']) ) {

        $MINA_ROOT = __DIR__ . '/mina';
        $CLASS_ROOT = $MINA_ROOT . '/' . strtolower($class_arr[1]);
        $autoload = $CLASS_ROOT . "/vendor/autoload.php";

        $class = end($class_arr);
        $class_file = ucfirst(strtolower($class)) . '.php';
        $class_path_file = $CLASS_ROOT . "/" . 'src' . "/". $class_file;

        if ( file_exists($autoload) ) {
            include_once($autoload);
        }
        include_once($class_path_file);
        return;

    // 载入APP
    } else if ( count($class_arr) >= 2 ) {

		$APP_ROOT = YAO_APP_ROOT;
        $class_arr = array_map( "strtolower", $class_arr );

        // 兼容旧版 Model (简墨引擎)
        if ( in_array("model", $class_arr) ) {

            $class = array_pop( $class_arr );
            $class_file = ucfirst($class);
            $class_path = strtolower(implode("/", $class_arr));
            $class_path_file = "{$APP_ROOT}/{$class_path}/{$class_file}.php";


        // YAO Backend 模型 
        } else {

            $class = array_pop( $class_arr );
            array_splice( $class_arr, 2, 0, ["model"] ); // 添加 model 目录
            $class_file = ucfirst($class);
            $class_path = strtolower(implode("/", $class_arr));
            $class_path_file = "{$APP_ROOT}/{$class_path}/{$class_file}.php";
        }

        if ( file_exists($class_path_file) ) {
            include_once($class_path_file);
        }
    }
};


/**
 * 异常通报
 */
function handler_excp($e) {

    $debug = Arr::get($GLOBALS, "YAO.debug", true);

    if ( $debug ) {
      
        http_response_code( $code );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo json_encode([
            "code" =>$e->getCode(),
            "message"=>$e->getMessage(),
            "trace"=>$e->getTrace()
        ]);
        exit;
    }

    $type = get_class($e);

    if ( $e instanceof Excp ) {

        $code = $e->getCode();
        if ( $code > 600 || $code < 400 ) {
            $code = 500;
        }
        http_response_code( $code );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo json_encode($e->toArray());

        // 服务端错误计入日志
        if ( $code >= 500 ) {
            $e->log();
        }
        exit;

    } else if ( $e instanceof Exception || $e instanceof Error ) {
        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jinamo.ink");
        echo '{"code":'.$e->getCode().', "message":"发生未定义错误"}';
        $exp = Excp::create("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误. Exception Type:{$type} Message:".$e->getMessage().".", 500 );
        $exp->log();
        exit;

    } else {
        http_response_code( 500 );
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo '{"code":500, "message":"发生未定义错误"}';
        $message = $e->getMessage();
        $exp = Excp::create("访问{$_SERVER["REQUEST_URI"]}时, 发生未定义错误 Exception Type:{$type} Message:{$message}.", 500 );
        $exp->log();
        exit;
    }
}

/**
 * 错误通报
 */
function handler_error($severity, $message, $file, $line) {
    
    $debug = Arr::get($GLOBALS, "YAO.debug", false);
    if ( $debug ) {
        header("Content-Type: application/json");
        header("server: jianmo/server:1.9.3");
        header("x-powered-by: jianmo.ink");
        echo json_encode([
            "message" => "程序运行错误({$message})",
            "file"=>$file,
            "line" => $line,
            "severity" =>$severity,
        ]);
        exit;
    }


    // if (!(error_reporting() & $severity)) {
    //     // This error code is not included in error_reporting
    //     return;
    // }
    header("Content-Type: application/json");
    header("server: jianmo/server:1.9.3");
    header("x-powered-by: jianmo.ink");
  
    
    echo '{"code":500, "message":"程序运行错误"}';
    $e = Excp::create("{$message}( 第{$line}行 {$file} )",500);
    $e->log();
    exit;
}


// set_error_handler("handler_error");
set_exception_handler('handler_excp');
spl_autoload_register("handler_autoload");


function __getInstance() {

    // if ( !empty($_SERVER['HTTP_CONTENT_INSTANCE']) ) {
    //     return $_SERVER['HTTP_CONTENT_INSTANCE'];
    // }

    // $domain = Arr::get($_SERVER,'HTTP_ORIGIN');
    // if ( empty($domain) ) {
    //     $domain = $_SERVER["HTTP_HOST"];
    // }
    
    // // 解析domain
    // $rootNames = ["api","www","root"];
    // $cname = Utils::getCNAME( $domain, isset($_GET["__refresh"]) );
    // $cname = explode(".", $cname);
    // $instance = $cname[0];
    // if ( !in_array($instance, $rootNames) ) {
    //     return $instance;
    // }

    return "root";
}

function getLocation() {

    if ( !empty($_SERVER['HTTP_TUANDUIMAO_LOCATION']) ) {
        return $_SERVER['HTTP_TUANDUIMAO_LOCATION'];
    }


    $proto ='http://';
    
    if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        $proto = 'https://';
    }
    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $proto = 'https://';
    }
    elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
        $proto = 'https://';
    }

    

    return $proto . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
}

function getHome( $location = null ) {
    if ( $location  == null ) {
        $location = getLocation();
    }

    $uri = parse_url( $location );
    $pos = strpos(   $uri['path'],'/_a' );
    $path = '';
    $port = isset($uri['port']) ? ":{$uri['port']}" : '';
    if ( $pos !== false ) {
        $path = substr( $uri['path'],0, $pos);
    }
    return "{$uri['scheme']}://{$uri['host']}{$port}{$path}";
}

function browser() {

    if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
        $browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);	
    }
    unset($browser['browser_name_regex']); // 这个变量存在，则程序会终止。 非常诡异？触发了PHP的BUG？
    unset($browser['browser_name_pattern']);

    $browser['agent'] = $_SERVER['HTTP_USER_AGENT'];
    $browser['iswechat'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) ? true : false;
    $browser['isdingtalk'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false ) ? true : false;

    $browser['type'] = 'desktop';
    if (  $browser['ismobiledevice']  ) {
        $browser['type'] = 'mobile';
    }

    if (  $browser['iswechat']  ) {
        $browser['type'] = 'wechat';
    }


    if( $_GET['_debug'] == 1 ){
        echo "<pre>";
        var_dump($browser);
        var_dump($_SERVER);
        echo "</pre>";
    }

    return $browser;
}


$instance = __getInstance();
$nocache = isset($_GET["__refresh"]);
$debug = isset($_GET['debug']);
$code = "devcode";  // 暂时使用 DEV 版本

if ( $debug) {
    $nocache = 1;
}

// SET Instance 4 API
$_SERVER['HTTP_CONTENT_INSTANCE'] = $instance ;

// 读取语言包
$lang = Arr::get($_COOKIE,"__lang");

$exec_options = [
    'nocache' => $nocache,   // 关闭页面缓存便于观察问题
];

if ( !empty($lang) ) {
    $exec_options["lang"] = $lang;
}

if ( $debug ) {
    $nocache = true;
    $code = "devcode";
    $exec_options['nocache'] = true;
}

$redis = $GLOBALS["YAO"]["redis"];
$renderOption = [];
$render = new \Mina\Template\Render([
    "lang" => "/data/stor/private/lang",  // 语言包根路径
    "cache" => [
        "engine" => 'redis',
        "prefix" => 'Page:Pages:',
        "host" => $redis['host'],
        "port" => $redis['port'],
        "auth"=> null,
    ]
]);

$dispatcher = new Dispatcher([
    "cache" => [
        "engine" => 'redis',
        "prefix" => 'Page:Entries:',
        "host" => $redis['host'],
        "port" => $redis['port'],
        "auth"=> null,
    ]
]);

try {
$dispatcher->setup(
    /**
     * 读取页面清单
     */
    function( $domain, $instance) {

        if ( empty($instance) ) {
            $instance = "root";
        }

        // Get Pages 
        $pages = [];
        // $page = Utils::getTab("page");
        
        // 实例定制页面
        $instances = DB::table("xs_core_page")
             ->whereNull("deleted_at")
             ->where("instance", "=", $instance)
             ->orderBy("priority", "desc")
             ->select('slug', 'name', 'entries', 'adapt', 'alias', 'instance')
             ->get()
             ->toArray();
        $resp = ['data'=>[], 'map'=>[] ];
        $mapPage = []; $mapEntry  = [];

        // 读取默认数据 ( root )
        if (empty($instances) ) {
            $instances = DB::table("xs_core_page")
                ->whereNull("deleted_at")
                ->where("instance", "=", "root")
                ->orderBy("priority", "desc")
                ->select('slug', 'name', 'entries', 'adapt', 'alias', 'instance')
                ->get()
                ->toArray();
        }
        
        // 处理JSON请求
        foreach($instances as &$row ) {
            $row["entries"] = json_decode($row["entries"], true);
            $row["adapt"] = json_decode($row["adapt"], true);
            $row["alias"] = json_decode($row["alias"], true);
        }


        // 解析入口程序
        function entriesParser( $p, & $mapPage, & $mapEntry ) {
            $slug = $p['slug'];
            $entries = $p['entries'];
            $mapPage[$slug] = $p;
         
            foreach ($entries as $entry ) {
                $entry['page'] = $p;
                $router = $entry["router"];
                $mapEntry[$router] = $entry;
            }
        }

        // 解读数据
        array_walk( $instances, function( $p ) use( &$mapPage, & $mapEntry ) {
            entriesParser( $p, $mapPage, $mapEntry);
        });

        $resp = [
            "data" => array_values($mapEntry),
            "map" => $mapPage
        ];

        return $resp;

    },

    /**
     * 渲染页面
     */
    function( $entry, $vars, $maps, $instance="root" ) use(  $render, $code, $exec_options) {

        // 页面渲染异常情况 （ $vars == 状态码  $maps == 状态信息 )
        if ( $entry === null && is_numeric($vars) && is_string($maps) ) {
            echo json_encode(["message"=>$maps, "code"=>$vars]);
            exit;
        }
        
        $browser = browser();
        $orgin = \Yao\Route\Request::origin();
        $type = $browser['type'];
        $slug = $entry['page']['slug'];
        $adapt = $entry['page']['adapt'];
        $alias = $entry['page']['alias'];
        $vars["__agent"] = $orgin["agent"];
        $vars["__platform"] = $orgin["platform"];
        $vars["__moible"] = $orgin["mobile"];
        $vars['__browser'] = $type;
        $vars['__home'] = getHome();
        $vars['__location'] =getLocation();
        $vars['__instance'] = $instance;
        $GLOBALS['_VAR'] =  $vars;

        if ( !in_array($type, $adapt) ) {
            if ( isset($alias[$type]) ) {
                $slug = $alias[$type];
            }
        }

        $p = $maps[$slug];
        if ( empty( $p) ) {
            echo json_encode(["message"=>"页面不存在", "code"=>404]);
            exit;
        }
        $page = "{$p["instance"]}:{$p["slug"]}";

        // 从缓存中读取页面清单
        if ( $exec_options["nocache"] != true ) {
            $content =  $render->getFromCache($page);
            if ( $content !== false ) {
                echo $content;
                return;
            }
        }

        // 从机构域读取数据
        // $code_text = Utils::getTab("page")->getVar($code, 'WHERE slug=? and instance=? LIMIT 1', [$slug, $instance]);
        $code_texts = DB::table("xs_core_page")
                        ->where("slug","=", $slug)
                        ->where("instance", "=", $instance )
                        ->pluck($code)
                        ->toArray()
                    ;
        if ( empty($code_texts) ) {
            // $code_text = Utils::getTab("page")->getVar($code, 'WHERE slug=? and instance=? LIMIT 1', [$slug, "root"]);
            $code_texts = DB::table("xs_core_page")
                            ->where("slug","=", $slug)
                            ->where("instance", "=", "root" )
                            ->pluck($code)
                        ;
        }

        if ( empty($code_texts) ) {
            echo json_encode(["message"=>"页面编译文件不存在", "code"=>404, 
                "extra"=>[
                    "instance"=>$instance,
                    "slug"=>$slug, 
                    "entry"=>$entry, 
                    "host"=> getenv('HOST')
                ]
            ]);
            exit;
        }

        $code_text = is_array($code_texts) ? current($code_texts) : $code_texts;
        // 缓存时间
        $exec_options["ttl"] = $entry["ttl"];
        $code_text = str_replace([
            "\$source = new \\Xpmse\\Model\\Data", 
            'require_once("/code/model/Data.php");',
            'require_once("/code/_lp/autoload.php");'
        ], [
            "\$source = new \\Xpmse\\Model\\YaoApi",
            'require_once("/code/model/Yaoapi.php");',
            ''
        ], $code_text);

        
        // echo $code_text; exit;
        $render->exec( $page, $code_text, $exec_options );

    },

    $nocache, $instance

);

} catch( Excp $e ){
    $resp =  $e->toArray();

    if ( $_GET['debug'] ) {
        $resp['trace'] = [];
        $trace = $e->getTrace();
        foreach ($trace as $t ) {
          $args = $t['args'];
          foreach ($args as $idx => $arg) {
            if (is_string($arg)) {
                $t['args'][$idx] = htmlspecialchars($arg);
                // $t['args'][$idx] = str_replace("\n", '\n', $t['args'][$idx] );
                $t['args'][$idx] = str_replace("&quot;", '"', $t['args'][$idx] );
                
                // unset($t['args']);
                // echo $t['args'][$idx];
            }
          }

          array_push($resp['trace'], $t);
        }
         // =  $e->getTrace();
    }

    echo json_encode( $resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

} catch( \FastRoute\BadRouteException $e ) {

    $error = [
        "code" => 500,
        "message" => $e->getMessage()
    ];

    if ( $_GET["debug"] ) {
        $trace = $e->getTrace();
        $error["trace"] = $trace;
    }

    echo json_encode( $error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}
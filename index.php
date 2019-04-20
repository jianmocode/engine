<?php
if ( isset($_GET['__timing']) && $_GET['__timing'] == 1 ) {
    $stime=microtime(true);
}

if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );
if( !defined('AROOT') ) define( 'AROOT' , realpath(__DIR__) );
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
error_reporting(E_ALL);
ini_set( 'display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');

require_once(__DIR__ . DS . "_lp" . DS ."autoload.php" );
use \Xpmse\Conf;
use \Xpmse\Utils;
use \Xpmse\Excp;
use \Xpmse\Mem as Mem;
use \FastRoute\simpleDispatcher;
use \Mina\Router\Dispatcher;

function __getInstance() {

    if ( !empty($_SERVER['HTTP_CONTENT_INSTANCE']) ) {
        return $_SERVER['HTTP_CONTENT_INSTANCE'];
    }

    $domain = @$_SERVER['HTTP_ORIGIN'];
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
$lang = $_COOKIE["__lang"];

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

$redis = Conf::G('mem/redis');
$renderOption = [];
$render = new \Mina\Template\Render([
    "lang" => "/data/stor/private/lang",  // 语言包根路径
    "cache" => [
        "engine" => 'redis',
        "prefix" => 'Page:Pages:',
        "host" => $redis['host'],
        "port" => $redis['port'],
        "auth"=> $redis['password']
    ]
]);

$dispatcher = new Dispatcher([
    "cache" => [
        "engine" => 'redis',
        "prefix" => 'Page:Entries:',
        "host" => $redis['host'],
        "port" => $redis['port'],
        "auth"=> $redis['password']
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
        $page = Utils::getTab("page");
        
        // 实例定制页面
        $instances = $page->query()
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
            $instances = $page->query()
                ->whereNull("deleted_at")
                ->where("instance", "=", "root")
                ->orderBy("priority", "desc")
                ->select('slug', 'name', 'entries', 'adapt', 'alias', 'instance')
                ->get()
                ->toArray();
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
    function( $entry, $vars, $maps, $instance="root" ) use(  $render, $code,  $exec_options) {

        // 页面渲染异常情况 （ $vars == 状态码  $maps == 状态信息 )
        if ( $entry === null && is_numeric($vars) && is_string($maps) ) {
            echo json_encode(["message"=>$maps, "code"=>$vars]);
            exit;
        }
        
        $browser = Utils::browser();
        $type = $browser['type'];
        $slug = $entry['page']['slug'];
        $adapt = $entry['page']['adapt'];
        $alias = $entry['page']['alias'];
        $vars['__browser'] = $type;
        $vars['__home'] = Utils::getHome();
        $vars['__location'] = Utils::getLocation();
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
        $code_text = Utils::getTab("page")->getVar($code, 'WHERE slug=? and instance=? LIMIT 1', [$slug, $instance]);
        if ( empty($code_text) ) {
            $code_text = Utils::getTab("page")->getVar($code, 'WHERE slug=? and instance=? LIMIT 1', [$slug, "root"]);
        }

        if ( empty($code_text) ) {
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

        // 缓存时间
        $exec_options["ttl"] = $entry["ttl"];
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

if ( $_GET['__timing'] ) {
    $etime=microtime(true);
    $total=$etime-$stime; 
    echo "
    <script language='javascript' type='text/javascript' >
        console.log('[PHP页面执行时间：{$total} 秒]');
    </script>"
    ;
}

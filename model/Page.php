<?php
namespace Xpmse\Model;

/**
 * 
 * 页面模型 ( 用于存储 MINA Page )
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Page
 *
 * USEAGE: 
 *
 */

use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;
use \Mina\Template\Render;


class DataCompiler  extends  \Mina\Template\DataCompiler {

	function __construct( $options = [] ) {
		parent::__construct( $options );
	}

	function compile( $json_data ) {
		return "\t" .'$data = ["foo"=>"bar"];' . "\n\t@extract(\$data);" ;
	}
}

class DevDataCompiler  extends  \Mina\Template\DataCompiler {

	function __construct( $options = [] ) {
		parent::__construct( $options );
	}

	function compile( $json_data ) {
		
		$autoload = realpath( __DIR__ . '/../_lp/autoload.php');
		$pagemode = realpath( __DIR__ . '/../model/Data.php');
		$json_text = str_replace("\\", "\\\\", Utils::get($json_data));
		$type = empty($json_data['type']) ?  'html' : $json_data['type'];

		

		switch ($type) {
			case 'html':
				$content = "header(\"Content-Type: text/html\");";
				break;
			case 'text': 
				$content = "header(\"Content-Type: text/plain\");";
				break;
			case 'xml' : 
				$content = "header(\"Content-Type: text/xml\");";
				break;
			default:
				# code...
				break;
		}

		return "
			{$content};
			require_once(\"$autoload\");
			require_once(\"$pagemode\");
			\$source = new \\Xpmse\\Model\\Data('$json_text', \$this );
			\$data = \$source->getData();
            @extract(\$data);
		";
	}
}


class Page extends Model {

    private $render = null;
    
    private $cache = null;

	/**
	 * 页面数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );
        $this->table('page');
        $redis = Conf::G('mem/redis');
        $this->cache = new \Mina\Cache\Redis([
            "prefix" => 'Page:',
            "host" => $redis['host'],
            "port" => $redis['port'],
            "auth"=> $redis['password']
        ]);
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// 页面 SLUG 页面别名 (project/name) 
			$this->putColumn( 'slug', $this->type('string', [ "null"=>false, 'index'=>1, 'length'=>128] ) )

			// 访问入口
			->putColumn( 'entries', $this->type('longText', [ "null"=>true, "json"=>true] ) )

			// 页面兼容
			->putColumn( 'alias', $this->type('longText', [ "null"=>true, "json"=>true] ) )

			// 页面适配
			->putColumn( 'adapt', $this->type('longText', [ "null"=>true, "json"=>true] ) )

			// 页面名称  ( 特殊页面 web )
			->putColumn( 'name', $this->type('string', [ "null"=>false, 'index'=>1, 'length'=>96] ) )

			// 页面中文名称  
			->putColumn( 'cname', $this->type('string', [ "null"=>true, 'length'=>96] ) )

			// 项目名称 ( 可按前缀查询 )
			->putColumn( 'project', $this->type('string', [ "null"=>false, 'default'=>'default', 'index'=>1, 'length'=>28] ) )

			// 页面
			->putColumn( 'page', $this->type('longText', [] ) )

			// 页面配置
			->putColumn( 'json', $this->type('longText', [] ) )

			// 页面样式
			->putColumn( 'css', $this->type('longText', [] ) )

			// 页面逻辑
			->putColumn( 'js', $this->type('longText', [] ) )

			// PHP Code ( 编译后的  PHP Code )
			->putColumn( 'phpcode', $this->type('longText', [] ) )

			// PHP Code ( 编译后的  PHP Code 开发版 )
			->putColumn( 'devcode', $this->type('longText', [] ) )

			// OP Code ( PHP编译后的中间代码 OP Code )
			->putColumn( 'opcode', $this->type('longText', [] ) )

			//机构名称
			->putColumn( 'instance', $this->type('string', ["null"=>false,'length'=>128] ) )

			//instance_slug
			->putColumn( 'instance_slug', $this->type('string', ["null"=>false, "unique"=>true, 'length'=>128] ) )

			// priority 优先级
			->putColumn( 'priority', $this->type('integer', ["index"=>true, "default"=>9999]))

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
    }
    

    /**
     * 按条件检索页面
     */
    function search( $query = [] ) {
        $select = empty($query['select']) ? ["_id","page.slug", "page.name", "cname", "project", "instance", "priority", "adapt", "entries", "created_at", "updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

	
		// 创建查询构造器
		$qb = Utils::getTab("core_page as page", "{none}")->query();
 		// $qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "coin.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("page.slug", "like", "%{$query['keywords']}%");
				$qb->orWhere("page.name","like", "%{$query['keywords']}%");
				$qb->orWhere("page.cname","like", "%{$query['keywords']}%");
				$qb->orWhere("page.instance","like", "%{$query['keywords']}%");
				$qb->orWhere("page.project","like", "%{$query['keywords']}%");
			});
		}


		// 按积分SLUG查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("page.slug", '=', "{$query['slug']}" );
		}
		  
		// 按实例查询 (=)  
		if ( array_key_exists("instance", $query) &&!empty($query['instance']) ) {
			$qb->where("page.instance", '=', "{$query['instance']}" );
		}
          
        
        $qb->orderBy("priority", "asc");
        $qb->orderBy("instance", "asc");

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("page.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("page.updated_at", "desc");
        }
        
        


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$pages = $qb->select( $select )->pgArray($perpage, ['page._id'], 'page', $page);

 		foreach ($coins['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$pages['_sql'] = $qb->getSql();
			$pages['query'] = $query;
		}

		return $pages;
    }

    function format( & $rs ) {
        return $rs;
    }

    
    /**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		
		
		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"instance_slug"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `instance_slug`))"
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}

			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
	}


    /**
     * 清除页面缓存
     * @param string $slug 页面slug
     * @param string $instance 机构信息
     */
    function clearCache($slug = "", $instance="root") {
        $this->cache->delete("Pages:{$instance}:{$slug}");
        $this->cache->delete("Entries:{$instance}");
    }

	/**
	 * 编译项目
	 * @param  string $project 项目名称，默认值 default
	 * @return $this
	 */
	function build( $project = 'default', $page=null, $instance="root" ) {

		// $web = $this->getLine("WHERE `project`=:project and `name` = '/' LIMIT 1 ", ["*"], ["project"=>$project]);
		$projectConf = $this->getProject($project, $instance);
		if ( empty($projectConf) ) {
			throw new Excp('项目不存在', 404, ["project"=>$project]);	
		}

		if ( $page == null ) {
			$resp = $this->select("WHERE `project`=:project and `name` <> '/' and `instance`=:instance ", ["*"], ["project"=>$project, "instance"=>$instance] );
		} else {
			$resp = $this->select("WHERE `project`=:project and `name` = :page and `instance`=:instance", ["*"], ["project"=>$project, 'page'=>$page, "instance"=>$instance] );
		}
		if ( empty($resp['data']) ) {
			throw new Excp("项目没有任何页面(instance:{$instance}, project:{$project})", 404, ["project"=>$project]);
		}

		foreach ($resp['data'] as $rs  ) {			
			$this->compile($rs, $projectConf );
		}

		return $this;
	}


	/**
	 * 编译页面 ( TO PHP Code )
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function compile( $data, $web ) {

		$compiler = new DataCompiler();
		$dev_compiler = new DevDataCompiler();

        $webconf =  Utils::json_decode($web['json']);
        $global_json = [];

        // 支持全局JSON DATA变量
        if ( !empty($web['global']) ) {
            $global_json = Utils::json_decode($web['global']);
        }

		$storage = $webconf['storage'];
		$this->setStorage($storage, $web['name']);
		$engine = $storage['engine'];
		$options = [];

		if ( strtolower($engine) == 'minapages') {
			$root = Conf::G("storage/local/bucket/public/root");
			$engine = "Local";
			$storage['options']['prefix'] = $root . $storage['options']['prefix'];
		} else {
			$engine = ucwords(strtolower($engine));
		}

		$class_name  = "\\Mina\\Storage\\$engine";
		if ( !class_exists($class_name) ) {
			throw new Excp('存储插件不存在', 404, ['storage'=>$storage, 'class_name'=>$class_name]);
		}

		$options = array_merge_recursive( $options, $storage['options'] );
		$stor = new $class_name( $options );

		$sdk = $stor->get("/jssdk");
		$pages = $storage['pages'];

		// 替换 {PROJECT_NAME} 字段
		foreach( $pages as $name=> & $pg ) {
			$pg = str_replace("{PROJECT_NAME}", $web['name'], $pg);
		}
		

		// if ( $this->render == null ) {
            // compile type 
            try {
                $json = Utils::json_decode($data['json']);
            } catch( Excp $e ) {
                $extra = $e->getExtra();
                print_r( $data );
                throw new Excp($e->getMessage() . "(". Utils::get($extra["details"]) . "JSON::\n" . $data['json'] . "::JSON\n)", 400 );
            }

            // 合并全局JSON数据
            if ( !empty($global_json) ) {
                // 合并 data
                if ( is_array($json["data"]) && is_array($global_json["data"]) ){
                    $json["data"] = array_merge( $global_json["data"], $json["data"] );
                }
                // 设定全局API 版本
                if( isset($global_json["version"]) && !isset($json["version"]) ) {
                    $json["version"] = $global_json["version"];
                }
            }

            // 设定API版本
            if ( !isset($json["version"]) ) {
                $json["version"] = "1.0";
            }

            // 数据签名
            $instance_slug = "{$data["instance"]}:{$data["project"]}{$data["name"]}";
            $json["page"] = $instance_slug; // 页面名称
            $json["build"] = date("Y-m-d\TH:i:s");  // 编译时间

            $data['json'] = json_encode($json, JSON_UNESCAPED_UNICODE ); // 更新JSON


            $type = empty($json['type']) ? 'html' : $json['type']; 
            
            // 检测是否包含 requirejs
            if ( $type == "html" ) {
                if (preg_match("/\<script[ ]+src=[\"\'][a-zA-z0-9\/\-]+require[min\.]+js[\"\']/", $data["page"]) ){
                    $type = "html-requirejs";
                }
            }

			switch ($type) {

                case 'html-requirejs':
					$develop = [
						"marker" => false,
                        "script" => [" 
                            var Web = null, Page = null, getWeb = null;
                            requirejs.config({
                                shim: {
                                    '{$sdk["origin"]}/web.dev.js': {
                                        exports: 'Web'
                                    },
                                    '{$sdk["origin"]}/getweb.dev.js':{
                                        deps: ['{$sdk["origin"]}/web.dev.js']
                                    },
                                    '{$sdk["origin"]}/page.dev.js':{
                                        deps: ['{$sdk["origin"]}/getweb.dev.js'],
                                        exports: 'Page'
                                     }
                                }
                            });

                            require([
                                '{$sdk["origin"]}/web.dev.js', 
                                '{$sdk["origin"]}/getweb.dev.js', 
                                '{$sdk["origin"]}/page.dev.js'
                            ], function(w, gw, p) {

                                Web = w, getWeb = gw,Page = p;
                                // Load Page
                                require([
                                    '{$pages["origin"]}/web.js',
                                    '{$pages["url"]}{$data["name"]}.js',
                                ], function( webInst, pageInst ){
                                    try {
                                        window.mina.page.\$load(__PHP_SHORT_TAG_BEGIN__=json_encode([
                                            'var'=>\$GLOBALS['_VAR'],
                                            'get'=>\$_GET,
                                            'post'=>\$_POST
                                        ])__PHP_SHORT_TAG_END__, __PHP_SHORT_TAG_BEGIN__=json_encode(\$data)__PHP_SHORT_TAG_END__);
                                    }catch(e){
                                        console.log('page load error', e);
                                    }
                                });
                            }); 
						"],
						"assets" => [
                            "{$sdk["origin"]}/mina.dev.css", 
                            "{$pages['origin']}/web.css" ,
                            "{$pages["url"]}{$data["name"]}.css"
                        ]
					];

					$production = [
						"marker" => false,
                        "script" => [" 
                            console.log('{$sdk["origin"]}');
							window.mina.page.ready(function(){
								try {
									window.mina.page.\$load(__PHP_SHORT_TAG_BEGIN__=json_encode([
										'var'=>\$GLOBALS['_VAR'],
										'get'=>\$_GET,
										'post'=>\$_POST
									])__PHP_SHORT_TAG_END__, __PHP_SHORT_TAG_BEGIN__=json_encode(\$data)__PHP_SHORT_TAG_END__);
								}catch(e){
									console.log('page load error', e);
								}
							});
						"],
						"assets" => []
						// SDK 完善后启用
						// "assets" => [ 
						// 	$sdk['url']  . "/mina.min.css", 
						// 	$pages['url']. "/web.min.css",
						// 	$sdk['url'] . "/minaweb.min.js"
						// ]
					];
					break;
				case 'html':
					$develop = [
						"marker" => false,
						"script" => [" 
							window.mina.page.ready(function(){
								try {
									window.mina.page.\$load(__PHP_SHORT_TAG_BEGIN__=json_encode([
										'var'=>\$GLOBALS['_VAR'],
										'get'=>\$_GET,
										'post'=>\$_POST
									])__PHP_SHORT_TAG_END__, __PHP_SHORT_TAG_BEGIN__=json_encode(\$data)__PHP_SHORT_TAG_END__);
								}catch(e){
									console.log('page load error', e);
								}
							});
						"],
						"assets" => [
							$sdk['origin']  . "/mina.dev.css", 
							$pages['origin']. "/web.css" ,
							$sdk['origin'] . "/getweb.dev.js", 
							$sdk['origin'] . "/page.dev.js", 
							$sdk['origin'] . "/web.dev.js",
                            $pages['origin'] . "/web.js",
                            $pages['url'] . $data['name'] . ".css",
                            $pages['url'] . $data['name'] . ".js",
						]
					];

					$production = [
						"marker" => false,
						"script" => [" 
							window.mina.page.ready(function(){
								try {
									window.mina.page.\$load(__PHP_SHORT_TAG_BEGIN__=json_encode([
										'var'=>\$GLOBALS['_VAR'],
										'get'=>\$_GET,
										'post'=>\$_POST
									])__PHP_SHORT_TAG_END__, __PHP_SHORT_TAG_BEGIN__=json_encode(\$data)__PHP_SHORT_TAG_END__);
								}catch(e){
									console.log('page load error', e);
								}
							});
						"],
						"assets" => [
							$sdk['origin']  . "/mina.dev.css", 
							$pages['origin']. "/web.css" ,
							$sdk['origin'] . "/getweb.dev.js", 
							$sdk['origin'] . "/page.dev.js", 
							$sdk['origin'] . "/web.dev.js",
                            $pages['origin'] . "/web.js",
                            $pages['url'] . $data['name'] . ".min.css",
					        $pages['url'] . $data['name'] . ".min.js",
						]
						// SDK 完善后启用
						// "assets" => [ 
						// 	$sdk['url']  . "/mina.min.css", 
						// 	$pages['url']. "/web.min.css",
						// 	$sdk['url'] . "/minaweb.min.js"
						// ]
					];
					break;
				case 'xml':
					$develop = $production = [
						"marker" => false,
						"script" => [],
						"assets" => []
					];

					break;
				case 'text':
					$develop = $production = [
						"marker" => false,
						"script" => [],
						"assets" => []
					];
                    break;
                // ? Markdown Support prepare
                case 'markdown': 
                    $develop = $production = [
                        "marker" => false,
                        "script" => [],
                        "assets" => []
                    ];
                    break;
				case 'component':
					$develop = $production = [
						"marker" => false,
						"script" => [" 
							try {
								window.mina.page.\$load(__PHP_SHORT_TAG_BEGIN__=json_encode([
									'var'=>\$GLOBALS['_VAR'],
									'get'=>\$_GET,
									'post'=>\$_POST
								])__PHP_SHORT_TAG_END__, __PHP_SHORT_TAG_BEGIN__=json_encode(\$data)__PHP_SHORT_TAG_END__);
							}catch(e){
								console.log('page load error', e);
							}
						"],
						"assets" => []
					];
					break;

				default:
					$develop = $production = [
						"marker" => false,
						"script" => [],
						"assets" => []
					];
					break;
			}

			$this->render['develop'] = new Render( $develop );
			$this->render['production'] = new Render( $production );

		// }

		if ( $webconf['debug'] == true ) {

			$data['devcode'] = $this->render['develop']->compile([
				"page" => $data['page'],
				"json" => $data['json'],
				'compiler' => $dev_compiler,
				// "script" => ["console.log('警告:当前为开发版. compile at " . date("Y-m-d H:i:s"). "');"],
				'assets' => [
					// $pages['url'] . $data['name'] . ".css",
					// $pages['url'] . $data['name'] . ".js",
				]
			]);
			
		} else {
			$data['phpcode'] = $this->render['production']->compile([
				"page" => $data['page'],
				"json" => $data['json'],
				'compiler' => $compiler,
				'assets' => [
					// $pages['url'] . $data['name'] . ".min.css",
					// $pages['url'] . $data['name'] . ".min.js",
				]
			]);
		}

		$this->save($data);

	}


	function importPage( $zipFile, $options, $fn=null ) {

		if ( !file_exists($zipFile) ) {
			throw new Excp('文件不存在', 404, ['zip'=>$zip]);
		}

		if ( empty($options['page'])) {
			throw new Excp('未提交页面名称', 404, ['options'=>$options]);	
		}

		$options['project'] = !empty( $options['project']) ? trim($options['project']) : "default";

		$zip = Utils::zip();
		$tmpdir = sys_get_temp_dir() . '/minapages-compile/' . time();
		@mkdir( $tmpdir, 0777, true);

		$zip->openFile($zipFile)
			->extractTo($tmpdir);



		$page = trim($options['page']);

		$data = [];
		$data['name'] = $page;

		if ( file_exists( "$tmpdir/$page.js")) {
			$data['js'] = file_get_contents("$tmpdir/$page.js");
		}

		if ( file_exists( "$tmpdir/$page.css")) {
			$data['css'] = file_get_contents("$tmpdir/$page.css");
		}

		if ( file_exists( "$tmpdir/$page.json")) {
			$data['json'] = file_get_contents("$tmpdir/$page.json");

			// 读取网页入口
			$page_json = Utils::json_decode( $data['json'] );
			$data['entries'] = !empty($page_json['entries']) ? $page_json['entries'] : ["router"=>$page, "ttl"=>0];
			
			// 页面兼容
			$data['alias'] = !empty($page_json['alias']) ? $page_json['alias'] : [];
			foreach ($data['alias'] as $dev => $alias_page ) {
				$data['alias'][$dev] =$options['project'] . $alias_page;
			}

			$data['cname'] = !empty($page_json['cname']) ? $page_json['cname'] : $data['name'];
		}

		if ( file_exists( "$tmpdir/$page.page")) {
			$data['page'] = file_get_contents("$tmpdir/$page.page");
		}

        $data['project'] = $options['project'];
        $data['priority'] = $options["priority"];
		@$data['instance'] = $options['config']['instance'];

		if ( is_callable($fn)) {
			$fn( $data, false) ;
		} else {
			$this->save($data);
		}

		// 删除无用文件
		Utils::rmdir( $tmpdir );


		// 编译
		return $this;

	}


	/**
	 * 导入模板页面
	 * @param  string $zipFile 模板文件压缩包
	 * @param  array  $options 导入选项
	 *                    ["project"] 项目名称
	 * @param  function | null  $fn function($page, $isweb ){} 回调函数，默认为  null 直接将数据入库 
	 * @return $this
	 */
	function import( $zipFile, $options=[], $fn = null ) {

		if ( !file_exists($zipFile) ) {
			throw new Excp('文件不存在', 404, ['zip'=>$zip]);
		}

		// 系统默认配置
		$config = !is_array($options['config']) ? [] : $options['config'];
		$options['project'] = !empty( $options['project']) ? trim($options['project']) : "default";

		$zip = Utils::zip();
		$tmpdir = sys_get_temp_dir() . '/minapages-compile/' . time();
		@mkdir( $tmpdir, 0777, true);

		$zip->openFile($zipFile)
			->extractTo($tmpdir);

		// 读取页面配置信息
		$conf = Utils::json_decode( $tmpdir . "/web.json" );
		$pages = $conf['pages'];


		// 遍历页面
		foreach ($pages as $page ) {
			
			$data = [];
			$data['name'] = $page;

			if ( file_exists( "$tmpdir/$page.js")) {
				$data['js'] = file_get_contents("$tmpdir/$page.js");
			}

			if ( file_exists( "$tmpdir/$page.css")) {
				$data['css'] = file_get_contents("$tmpdir/$page.css");
			}

			if ( file_exists( "$tmpdir/$page.json")) {
				$data['json'] = file_get_contents("$tmpdir/$page.json");
				// $json=json_decode($data['json'],true);
                
                //  Fuck ????
                // foreach ($json['data'] as $jsonkey => &$jsonvalue) {
				// 	if(array_key_exists('query', $jsonvalue)){
				// 		$jsonvalue['query']['instance_gulp']=$options['config']['instance'];
				// 	}
                // }
                
                // $data['json']=json_encode($json);
                
				// 读取网页入口
				$page_json = Utils::json_decode( $data['json'] );
				$data['entries'] = !empty($page_json['entries']) ? $page_json['entries'] : ["router"=>$page, "ttl"=>0];

				// 页面兼容
				$data['alias'] = !empty($page_json['alias']) ? $page_json['alias'] : [];
				foreach ($data['alias'] as $dev => $alias_page ) {
					$data['alias'][$dev] =$options['project'] . $alias_page;
				}

				$data['cname'] = !empty($page_json['cname']) ? $page_json['cname'] : $data['name'];

			}

			if ( file_exists( "$tmpdir/$page.page")) {
				$data['page'] = file_get_contents("$tmpdir/$page.page");
			}

			$data['project'] = $options['project'];
            @$data['instance'] = $options['config']['instance'];
            $data['priority'] = $options["priority"];

			if ( is_callable($fn)) {
				$fn( $data, false) ;
			} else {
				$this->save($data);
			}

		}

		// 保存项目信息
		$data = [];
		$data['name'] = $options['project'];
		$data['domain'] = isset($options['domain']) ? $options['domain'] : null;
		$data['cname'] = !empty($conf['cname']) ? $conf['cname'] : $data['name'];
		if ( file_exists( "$tmpdir/web.js")) {
			$data['js'] = file_get_contents("$tmpdir/web.js");
		}

		if ( file_exists( "$tmpdir/web.css")) {
			$data['css'] = file_get_contents("$tmpdir/web.css");
		}

		if ( file_exists( "$tmpdir/web.json")) {
			$data['json'] = file_get_contents("$tmpdir/web.json");
        }
        
        if ( file_exists( "$tmpdir/global.json")) { // 全局JSON文本
			$data['global'] = file_get_contents("$tmpdir/global.json");
        } else {
            $data['global'] = null;
        }
        
        $data['instance'] = $options['config']['instance'];

		if ( is_callable($fn)) {
			$fn( $data, true) ;
		} else {
            // 项目优先级
            $data['priority'] = $options["priority"];
			$projectData = $this->saveProject($data,true);
		}

		// // 保存机构信息
		// $domainData = [];
		// $domainData['name'] = $options['project'];
		// $domainData['domain'] = isset($options['domain']) ? $options['domain'] : null;
		// $domainData['project_id'] = $projectData['_id'];
		// $domainData['instance'] = $options['config']['instance'];

		// if ( is_callable($fn)) {
		// 	$fn( $domainData, true) ;
		// } else {
		// 	$this->saveDomain($domainData);
        // }
        

		// 上传 MINA JSSDK 
		$storage =$conf['storage'];
		$this->setStorage( $storage, $options['project'] ) ;
		$this->uploadJSSDK($storage);

		// 删除无用文件
		Utils::rmdir( $tmpdir );
		return $this;
	}


	function setStorage( & $storage, $project ) {

		$storage['options'] =!is_array($storage['options']) ?  [] : $storage['options'];

		if ( empty($storage["options"]['url']) ) {
			$storage["options"]['url'] = "/static-file/{$project}";
		}

		if ( empty($storage["options"]['origin']) ) {
			$storage["options"]['origin'] = "/static-file/{$project}";
		}

		if ( empty($storage["options"]['prefix']) ) {
			$storage["options"]['prefix'] = "/{$project}";
		}
	}


	/** 
	 * 根据适配信息，返回页面兼容信息
	 */
	function adapt( $alias ) {

		// 如果未设置 alias， 适配 mobile & desktop & wechat
		$resp = ['desktop'=>true, 'mobile'=>true, 'wechat'=>true];

		if ( empty( $alias ) ) {
			return array_keys($resp);

		} else if (!is_array($alias)) {
			$alias =  [];
		}

		if ( array_key_exists('desktop', $alias) ) {
			unset( $resp['desktop']);
		}

		if ( array_key_exists('mobile', $alias) ) {
			unset( $resp['mobile']);
			unset( $resp['wechat']);
			return array_keys($resp);
		}  

		if ( array_key_exists('wechat', $alias) ) {
			unset( $resp['wechat']);
		}

		return array_keys( $resp );
	}



	/**
	 * 上传 MINA Pages SDK 到 CDN
	 * @param  [type] $storage [description]
	 * @return [type]          [description]
	 */
	function uploadJSSDK( $storage ) {


		
		$engine = $storage['engine'];
		$options = [];
		if ( strtolower($engine) == 'minapages') {
			$root = Conf::G("storage/local/bucket/public/root");
			$engine = "Local";
			$storage['options']['prefix'] = $root . $storage['options']['prefix'];

		} else {
			$engine = ucwords(strtolower($engine));
		}

		$class_name  = "\\Mina\\Storage\\$engine";
		if ( !class_exists($class_name) ) {
			throw new Excp('存储插件不存在', 404, ['storage'=>$storage, 'class_name'=>$class_name]);
		}


		$jssdkfiles = [
			"getWeb.dev.js" => '/jssdk/getweb.dev.js',
			"Page.dev.js"   => '/jssdk/page.dev.js',
			"Web.dev.js"    => '/jssdk/web.dev.js',
			"mina.dev.css"    => '/jssdk/mina.dev.css',
		];

		$options = array_merge_recursive( $options, $storage['options'] );
		

		$stor = new $class_name( $options );
		$sdkpath = realpath( __DIR__ . '/../mina/jssdk');

		foreach( $jssdkfiles as $src=>$dst ) {

			$jscode = file_get_contents($sdkpath . '/'. $src );
			if ( $jscode === false ) {
				throw new Excp('上传SDK失败', 500, [ 'jsfile'=>$sdkpath . $src,  'storage'=>$storage, 'class_name'=>$class_name]);
			}

			$stor->upload( $dst, $jscode );
		}

		return $this;

	}



	/**
	 * 保存项目信息
	 * @param  [type]  $data   [description]
	 * @param  boolean $return [description]
	 * @return [type]          [description]
	 */
	function saveProject( $data, $return = false ) {

		if ( empty($data['name']) ) {
			throw new Excp('保存项目失败, 未知项目名称', 400, ['data'=>$data]);
		}

		$proj = M('Project');

        // 唯一主键
        $data["name_instance"] = "DB::RAW(CONCAT_WS(':',name,instance))";

		$resp = $proj->createOrUpdate($data);
		if (  $resp !== true ) {
			throw new Excp('保存项目数据错误', 500, ['resp'=>$resp, 'data'=>$data]);
		}

		if ( $return == true ) {
			return $proj->getLine("WHERE name=? AND instance=? LIMIT 1", ["*"], [$data['name'], $instance]);
		}
	}

	/**
	 * 保存项目信息
	 * @param  [type]  $data   [description]
	 * @param  boolean $return [description]
	 * @return [type]          [description]
	 */
	function saveDomain( $data, $return = false ) {

		if ( empty($data['project_id']) ) {
			throw new Excp('保存项目失败, 未知项目名称', 400, ['data'=>$data]);
		}

		$proj = M('Domain');


		$resp = $proj->createOrUpdate($data);
		if (  $resp !== true ) {
			throw new Excp('保存项目数据错误', 500, ['resp'=>$resp, 'data'=>$data]);
		}

		if ( $return == true ) {
			return $proj->getLine("WHERE project_id=? LIMIT 1", ["*"], [$data['project_id']]);
		}
	}

	/**
	 * 读取项目信息
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	function getProject( $name, $instance='root' ) {
		$proj = M('Project');
		return $proj->getLine("WHERE name=? AND instance=? LIMIT 1", ["*"], [$name, $instance]);
	}



	/**
	 * 保存页面信息（ 如不存在则创建 )
	 * 
	 * @param  array  $data [description]
	 * @param  boolen $return 是否返回结果
	 * @return void | $rs $return = true 返回结果集，否则无返回
	 */
	function save( $data, $return = false ) {	
		
		// AUTO CREATE DB DATA
		$data['slug'] = 'DB::RAW(CONCAT(`project`, `name`))';
		$data['instance_slug'] = 'DB::RAW(CONCAT(`instance`, ":", `project`, `name`))';
        
        // 追加页面适配信息
		if ( array_key_exists('alias', $data) ) {
			$data['adapt'] = $this->adapt( $data['alias'] );

			if ( is_string($data['json']) ) { // Copy To Data JSON 
				$data['json'] = json_decode( $data['json'], true );
				$data['json']['adapt'] = $data['adapt']; 
				$data['json'] = json_encode($data['json'], JSON_UNESCAPED_UNICODE );
			}
        }
        try {
            $resp = $this->createOrUpdate($data);
        }catch ( Excp $e ) { $e->log();}

		if (  $resp !== true ) {
			throw new Excp('保存数据错误', 500, ['resp'=>$resp, 'data'=>$data]);
        }
        
        // 更新的数据集
        $slug = "{$data["project"]}{$data["name"]}";
        $instance = "{$data["instance"]}";
        $instance_slug = "{$data["instance"]}:{$data["project"]}{$data["name"]}";

        // 清理缓存
        $this->clearCache($slug, $instance);
		if ( $return == true ) {
			return $this->getLine("WHERE instance_slug=? LIMIT 1", ["*"], [$instance_slug]);
		}
	}

	
}


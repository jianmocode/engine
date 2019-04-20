<?php
namespace Xpmse\Model;
define('XPMCODE_TPL_ROOT', '/templates');

use \Xpmse\Model;
use \Xpmse\Conf;
use \Xpmse\Utils;
use \Xpmse\Excp;
use \Mina\Cache\Redis as Cache;

use \Twig_Loader_Array;
use \Twig_Environment;
use \Twig_Filter;
use \Twig_Lexer;

class Code extends Model {

	/**
	 * 代码模板路径
	 * @var null
	 */
	private $root = null;

	/**
	 * 媒体对象
	 * @var null
	 */
	private $meida = null;


	/**
	 * 缓存对象
	 * @var null
	 */
	private $cache = null;


	/**
	 * 当前代码母版配置
	 * @var array
	 */
	private $code = [];


	/**
	 * 当前页面配置信息
	 * @var array
	 */
	private $option = [];


	/**
	 * 全局过滤器路径
	 * @var array
	 */
	private $filter_paths = [];



	/**
	 * 代码模板数据表 ( 1.6.11 新增功能 )
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct($param , $driver );

		// 缓存配置
		$cacheOptions = [
			"engine" => 'redis',
			"prefix" => '_code:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd" => Conf::G("mem/redis/password")
		];
		$this->cache = new Cache($cacheOptions);

		$root_private = Conf::G("storage/local/bucket/private/root");
		$root_private = empty($param['root']) ? $root_private : $param['root'];
		$this->root = is_dir( "{$root_private}/templates" ) ? "{$root_private}/templates" : XPMCODE_TPL_ROOT;
		$this->meida = new \Xpmse\Media(['private'=>true, 'host'=>Utils::getHome()]);

		// 代码保存记录
		$this->code_history = Utils::getTab('codemaker_history');




		// 模板记录表
		$this->table('code'); 
	}


	function __schema() {

		// 创建代码母版表
		try {

			// 应用ID
			$this->putColumn( 'code_id', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

			// 应用路径
			->putColumn( 'path', $this->type('string', ['unique'=>1, "null"=>false,'length'=>200] ) )

			// 所有者机构 ( 组织 )
			->putColumn( 'org', $this->type('string', [ "null"=>false, "index"=>true, 'default'=>DEFAULT_ORG, 'length'=>100] ) )

			// 应用名称
			->putColumn( 'name', $this->type('string', ["null"=>false, "index"=>true, 'length'=>100] ) )

			// 应用 SLUG （ org/name )
			->putColumn( 'slug', $this->type('string', ["null"=>false, "unique"=>1, 'length'=>200] ) )			

			// 中文名称
			->putColumn( 'cname', $this->type('string', [ "null"=>false,'length'=>200] ) )

			// 简介信息
			->putColumn( 'summary', $this->type('string', [ "null"=>false, 'length'=>800] ) )

			// 版本号
			->putColumn( 'version', $this->type('string', ['length'=>200] ) )

			// 界面截图路径
			->putColumn( 'images', $this->type('text', ['json'=>true] ) )
		
			// 应用作者
			->putColumn( 'author', $this->type('string', ['length'=>200, "index"=>true] ) )

			// 官网地址
			->putColumn( 'homepage', $this->type('string', ['length'=>200] ) )

			// 协议类型
			->putColumn( 'license', $this->type('string', ['length'=>50] ) )

			// 仓库地址
			->putColumn( 'repository', $this->type('text', ['json'=>true] ) )

			// 关键词
			->putColumn( 'keywords', $this->type('text', ['json'=>true] ) )

			// 模板标签定义
			->putColumn( 'tag', $this->type('text', ['json'=>true] ) )

			;
			
		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}


		// 创建代码记录表
		try {

			$this->code_history

				 // 数据记录ID
				 ->putColumn( 'history_id', $this->type('string', ['unique'=>1, "null"=>false,'length'=>128] ) )

				 // 代码母版ID
				 ->putColumn( 'code_id', $this->type('string', ["null"=>false,'length'=>128, "index"=>1] ) )

				 // 名称
				 ->putColumn( 'name', $this->type('string', ['length'=>128] ) )

				 // 创建用户ID
				 ->putColumn( 'user_id', $this->type('string', ['length'=>128, "index"=>1] ) )


				 // 参数数值
				 ->putColumn( 'data', $this->type('longText', ['json'=>true] ) )

				 // 当前进度信息 {"page_name":"saved", ... }  |
				 ->putColumn( 'process', $this->type('text', ['json'=>true] ) )

				 // 下一步: 进度名称
				 ->putColumn( 'next', $this->type('string', ['length'=>128, "index"=>1] ) )

				 // 上一步: 进度名称
				 ->putColumn( 'prev', $this->type('string', ['length'=>128, "index"=>1] ) )

			;

		} catch( Exception $e  ) {
			Excp::elog( $e );
			throw $e;
		}
	}


	function __clear() {

		$this->dropTable();
		$this->code_history->dropTable();
		return $this;
    }
    
    /**
     * 导出代码
     */
    function export( $history_id ){

        $rs = $this->code_history->getBy("history_id", $history_id );
        if ( empty($rs) ) {
            throw new Excp( "项目不存在", 404, ['history_id'=>$history_id] );
        }

        $code_id = $rs["code_id"];
        $codeRs = $this->getBy("code_id", $code_id);
        if ( empty($codeRs) ) {
            throw new Excp( "代码母版不存在", 404, ['history'=>$rs, "code_id"=>$code_id]);
        }

        return [
            "name" => $rs["name"],
            "data" => $rs["data"],
            "process" => $rs["process"],
            "next" => $rs["next"],
            "prev" => $rs["prev"],
            "templete" => [
                "org"=>$codeRs["org"],
                "name"=>$codeRs["name"],
                "slug"=>$codeRs["slug"],
                "cname"=>$codeRs["cname"],
                "version"=>$codeRs["version"],
                "summary"=>$codeRs["summary"]
            ]
        ];
    }


	/**
	 * 根据当前流程页面和数据, 计算流程信息 ( next & prev )
	 * @param  [type] $curr    [description]
	 * @param  [type] $history [description]
	 * @param  [type] $pages   [description]
	 * @return [type]          [description]
	 */
	function processOptions( $page, & $history, & $pages ) {

		$curr = & $pages[$page]; // 当前页信息
		$process = is_array($history['process']) ? $history['process'] : [];  // 流程记录


		// 检查 Option 能否被点亮
		if ( $this->isActivableOption( $curr, $process ) === false ) {
			throw new Excp( "前置选项配置尚未完成", 400, ['page'=>$curr, 'process'=>$process]);
		}

		// 计算下一步, 上一步
		$page_names = array_keys($pages);  // 所有页面名称
		
		$curr_index = array_search($page, $page_names);
		$prev_index = $this->prevOption( $page, $page_names);
		$next_index = $this->nextOption( $page, $page_names);
		
		// 计算当前情况下，各个页面状态
		foreach ($pages as  $name => & $pa ) {
			$pa['active'] = false;
			$pa['activable'] = $this->isActivableOption( $pa, $process );
			$pa['status'] = $this->getStatusOption($name, $process );
		}

		$complete = true;
		foreach ( $process as $k => $v) {
			if ( $v != "saved") {
				$complete = false;
			}
		}

		// 标记为当前选项
		$curr["active"] = true;


		return [
			"curr" => $page,
			"next" => ( $next_index !== false && $pages[ $page_names[$next_index] ]['activable']  !== false ) ? $page_names[$next_index] : false,
			"prev" => ( $prev_index !== false ) ? $page_names[$prev_index] : false,
			"islast" => ( $curr_index + 1 == count($page_names) && $process[$page_names[$next_index]] == 'saved' ) ? true : false,
			// 是否已经完成
			"complete" => $complete
		];
	}


	/**
	 * 根据依赖关系，校验 Option 能否被点亮
	 * @param  [type]  $curr    [description]
	 * @param  [type]  $deps    [description]
	 * @param  [type]  $process [description]
	 * @return boolean          [description]
	 */
	function isActivableOption( & $page, & $process ) {		
		foreach ($page["dependencies"] as $p => $status ) {
			if ( $process[$p] != $status ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * 读取当前配置面板状态
	 * @param  [type] $page_name [description]
	 * @param  [type] $process   [description]
	 * @return [type]            [description]
	 */
	function getStatusOption( $page_name, & $process ) {
		
		$status = $process[$page_name];
		if ( empty($status) ) {
			$status = 'unsaved';
		}

		return $status;
	} 


	/**
	 * 下一个配置面板 Index
	 * @param  [type] $curr       [description]
	 * @param  [type] $page_names [description]
	 * @return [type]             [description]
	 */
	function nextOption( $page_name, & $page_names ) {
		$max_index = count($page_names) - 1;
		$curr_index = array_search( $page_name, $page_names);
		$next_index = ($curr_index == $max_index) ? false : $curr_index + 1;

		return $next_index;
	}

	/**
	 * 上一个配置面板 Index
	 * @param  [type] $curr       [description]
	 * @param  [type] $page_names [description]
	 * @return [type]             [description]
	 */
	function prevOption( $page_name, & $page_names ) {
		$max_index = count($page_names) - 1;
		$curr_index = array_search( $page_name, $page_names);
		$next_index = ($curr_index == 0) ? false : $curr_index - 1;
		return $next_index;
	}


	/**
	 * 读取历史记录(项目列表)
	 * @param  [type] $history_id [description]
	 * @return [type]             [description]
	 */
	function searchHistory( $query = [] ) {

		// 选中结果
		$select = empty($query['select']) ? ['*'] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		$qb = $this->code_history->query();
        $qb->orderBy('created_at', 'desc');
        $qb->orderBy('updated_at', 'desc');

		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 查询一级分类
		$response = $qb->select($select)->pgArray($perpage, ['_id'], 'page', $page);
		foreach ($response['data'] as & $rs ) {
			$this->formatHistory( $rs );
		}

		return $response;
	}


	/**
	 * 格式化历史记录(项目列表)
	 * @param  [type] $rs [description]
	 * @return [type]     [description]
	 */
	function formatHistory( & $rs ) {
		return $rs;
	}


	/**
	 * 读取历史记录(项目列表)
	 * @param  [type] $history_id [description]
	 * @return [type]             [description]
	 */
	function getHistory( $history_id  ) {
		$rs = $this->code_history->getBy('history_id', $history_id);
		return $rs;
	}


	/**
	 * 保存历史记录(项目列表)
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function saveHistory( $data ) {

		$rs = $this->code_history->saveBy('history_id', $data );
		return $rs;
	}


	function removeHistory( $history_id ){
		return $this->code_history->remove($history_id, 'history_id');
	}


	/**
	 * 读取当前历史记录(项目列表)
	 * @return [type] [description]
	 */
	function currentHistory() {

		$history_id = $_COOKIE['_cmhi'];
		if ( empty($history_id) ) {
			$history_id = time() . rand(100000, 999999);
			setcookie('_cmhi', $history_id, 0, '/_a/');
		}

		$rs = $this->getHistory($history_id);
		if ( empty($rs['name']) ) {
			$rs['name'] = '未命名';
			$rs['history_id'] = $history_id;
		}

		return $rs;
	}


	/**
	 * 选中给定 History
	 * @param  [type] $history_id [description]
	 * @return [type]             [description]
	 */
	function selectHistory( $history_id ) {
		setcookie('_cmhi', $history_id, 0, '/_a/');

		$rs = $this->getHistory($history_id);
		if ( empty($rs['name']) ) {
			$rs['name'] = '未命名';
			$rs['history_id'] = $history_id;
		}
		return $rs;
	}


	/**
	 * 读取母版配置信息
	 */
	function getOptions( $code_id ) {

		$rs = $this->getBy('code_id', $code_id );
		$opts_file = "{$rs['path']}/options/options.json";
		$this->getOptionsInfo( $opts_file, $opts );

		
		$this->options = $opts;
		$this->code = $rs;
		return $opts;
	}


	/**
	 * 渲染字段列表 ( XSFDL )
	 * @param  [type] $form_struct [description]
	 * @param  [type] $tpl         [description]
	 * @param  array  $data        [description]
	 * @return [type]              [description]
	 */
	function renderOptionPage( $form_struct, $tpl_paths, & $data, & $options ) {
	
		foreach ($form_struct as & $struct ) {
			$struct['_opts'] = $options;
		}


        $tpl_content = $this->getTemplateContent( $tpl_paths );
		$xsf = new \Xpmse\XSFDL(['phpcode'=>true]);
		$xsf->loadFields( $form_struct )
		    ->loadTemplateContent( $tpl_content )
		    ->setFilters( $this->options['filters'] )  // SetFilter
		    ->render( $data )
		;
		return $xsf->get();
	}


	/**
	 * 读取表单验证JS代码 ( XSFDL )
	 * @param  [type] $form_struct [description]
	 * @param  [type] $tpl_path    [description]
	 * @return [type]              [description]
	 */
	function getValidationJSCode( $form_struct, $tpl_paths ) {
		
		$tpl_content = $this->getTemplateContent( $tpl_paths );

		$xsf = new \Xpmse\XSFDL();
		$xsf->loadFields( $form_struct )
		    ->loadTemplateContent( $tpl_content )
		    ->setFilters( $this->options['filters'] )  // SetFilter
		;

		$validate = $xsf->getValidationJSCode();
		if ( empty($validate) ) {
			$validate = ["rules"=>[], "messages"=>[]];
		}

		return json_encode( $validate,  JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES  );
	}


	/**
	 * 合并模板文件
	 * @param  [type] $tpl_paths [description]
	 * @return [type]            [description]
	 */
	function getTemplateContent( $tpl_paths ){
		$content = '';
		foreach ($tpl_paths as $file_name ) {
			if ( is_readable($file_name) ) {
				$content .= file_get_contents( $file_name );
			}
		}

		return $content;
	}


	/**
	 * 读取并解析代码模板配置项 options.json 文件
	 * @param  string $json_file JSON文件地址
	 * @param  [type] $options   [description]
	 * @return [type]            [description]
	 */
	function getOptionsInfo( $json_file,  & $options ) {

		if ( !is_readable($json_file) ) {
			throw new Excp("无法访问JSON文件($json_file)", 404, ['json_file'=>$json_file]);
		}

		
		$json_text = file_get_contents($json_file);
		$json_data = json_decode( $json_text, true );
		if ( $json_data === false || $json_data === null ) {
			$json_data = Utils::json_decode( $json_text );
			return;
		}

        // 读取表单模板地址
        $this->getOptionTemplates( $json_file, $templates );
		$json_data["templates"] = $templates;

        // 读取过滤器地址
        $this->getOptionFilters( $json_file, $filters );
		$json_data["filters"] = $filters;


		// 解析每个页面配置
		$pages = !empty($json_data['pages']) ? $json_data['pages'] : [] ;
		$root = dirname( $json_file );
		$json_data['root'] = dirname($root); // 母版根目录
		$json_data['pages'] = [];
		foreach ($pages as $pg ) {
			$page_path = "$root/$pg";
			$this->getOptionPageInfo($page_path, $option);
			$tpl_name = $option['template'];
			$tpl_path = $templates[$tpl_name];
			$option['template'] = ["name"=>$tpl_name, "path"=>$tpl_path];
			$json_data['pages'][$pg] = $option;
		}
		$options = $json_data;
		return $options;
	}


	/**
	 * 读取模板过滤器
	 * @param  [type] $json_file [description]
	 * @return [type]            [description]
	 */
	function getOptionFilters( $json_file, & $filters ) {

		$filters = [];
		$filter_paths = [realpath(__DIR__ . '/../service/lib/xsfdl')];
		$path = dirname(dirname( $json_file ));
		array_push($filter_paths, "{$path}/templates");

		$filter_files = [];
		foreach ($filter_paths as $path ) {
			if ( is_dir($path) ) {
				$filter_files = array_merge($filter_files, glob("{$path}/Filter.php"));
			}
		}
		
		foreach ($filter_files as $filter) {
			$_Twig_Filters = [];
			include($filter);
			$filters = array_merge( $filters, $_Twig_Filters);
		}

		return $filters;
	}


	/**
	 * 读取Form 模板路径
	 * @param  string $json_file Option 路径
	 * @param  array  $templates [description]
	 * @return
	 */
	function getOptionTemplates( $json_file, & $templates ) {
		
		$map = [];
		$template_paths = [realpath(__DIR__ . '/../service/lib/xsfdl')];
		$path = dirname(dirname( $json_file ));
		array_push($template_paths, "{$path}/templates");

		$tpls = [];
		foreach ($template_paths as $path ) {
			$htmls = glob("{$path}/*.html");
			$tpls = array_merge($tpls, glob("{$path}/*.html"));
		}

		foreach ($tpls as $tpl) {
			$name = str_replace('.tpl.html', '', basename($tpl));
			$map[$name][] = $tpl;
		}

		$templates = $map;
		return $map;
	}



	/**
	 * 读取并解析配置页面信息
	 * @param  [type] $page_path [description]
	 * @return [type]            [description]
	 */
	function getOptionPageInfo( $page_path, & $option ) {

		$opt_file = "{$page_path}/option.json";
		$form_file = "{$page_path}/form.json";

		if ( !is_readable($opt_file) ) {
			throw new Excp("无法访问JSON文件($opt_file)", 404, ['opt_file'=>$opt_file]);
		}

		if ( !is_readable($form_file) ) {
			throw new Excp("无法访问JSON文件($form_file)", 404, ['opt_file'=>$form_file]);
		}


		$opt_text = file_get_contents($opt_file);
		$opt_data = json_decode( $opt_text, true );
		// 抛出异常
		if ( $opt_data === false || $opt_data === null ) {
			$opt_data = Utils::json_decode( $opt_text );
			return;
		}

		$form_text = file_get_contents($form_file);
		$form_data = json_decode( $form_text, true );
		// 抛出异常
		if ( $form_data === false || $form_data === null ) {
			$form_data = Utils::json_decode( $form_text );
			return;
		}

		$opt_data['form'] = $form_data;
		$option = $opt_data;

		return $opt_data;
	}


	/**
	 * 扫描已安装应用模型清单
	 * @return
	 */
	function scanModelList() {

		$cache_name = "modelList";

		// 从缓存中读取数据
		if ( !$nocache ) {
			$resp = $this->cache->getJSON( $cache_name );
			if ( $resp  !== false ) {
				return $resp;
			}
		}

		$models = [];

		// 已安装应用 PATH
		$app = new App();
		$apps = $app->getInstalled();
		foreach( $apps['data'] as $ap ) {
			$path = $ap['path'];
			$files = glob("$path/model/*.php");
			foreach ($files as $file) {
				$pi = pathinfo($file);
				$ap['org'] = ucfirst(strtolower($ap['org']));
				$ap['name'] = ucfirst(strtolower($ap['name']));
				$name = "\\{$ap['org']}\\{$ap['name']}\\Model\\" . $pi['filename'];
				if ( get_parent_class($name) == "Xpmse\\Model") {
					array_push( $models, $name );	
				}
			}
		}

		// 核心 Model
		$files =  glob(__DIR__ . "/*.php");
		foreach ($files as $file) {
			$pi = pathinfo($file);
			$name = "\\Xpmse\\Model\\" . $pi['filename'];
			if ( get_parent_class($name) == "Xpmse\\Model") {
				array_push( $models, $name );	
			}
		}

		$this->cache->setJSON( $cache_name, $models );
		return $models;
	}


	/**
	 * 读取模型字段信息
	 * @param  string $model_class  模型名称
	 * @return 
	 */
	function getModelStruct( $model_class, $nocache = false  ) {

		$cache_name = "modelStruct:{$model_class}";

		// 从缓存中读取数据
		if ( !$nocache ) {
			$resp = $this->cache->getJSON( $cache_name );
			if ( $resp  !== false ) {
				return $resp;
			}
		}
		
		if (!class_exists($model_class) ) {
			throw new Excp("模型不存在($model_class)", 404, []);
		}

		if ( get_parent_class($model_class) != "Xpmse\\Model") {
			throw new Excp("非模型数据($model_class)", 402, ["type"=>get_parent_class($model_class)]);	
		}

		$inst = new $model_class();
		$struct = $inst->getStruct();

		$resp  =  [
			"table"=>$struct['table'],
			"prefix"=>$struct['prefix'],
			"global_prefix" => $struct['global_prefix'],
			"full_prefix"=>$struct['full_prefix'],
			"table_name" =>$struct['prefix'] . $struct['table'],
			"table_fullname"=>$struct['full_prefix'] . $struct['table'],
			"index_fields" => array_column($struct['indexes']['data'], 'field'),
			"fields" => $struct['fields']['map'], 
			"indexes" => $struct['indexes']['map']
		];

		$this->cache->setJSON( $cache_name, $resp );
		return $resp;
	}


	/**
	 * 读取模型代码信息
	 * @param  string  $model_class 
	 * @param  boolean $nocache     
	 * @return 
	 */
	function getModelCode( $model_class, $nocache = false ) {

		if (!class_exists($model_class) ) {
			throw new Excp("模型不存在($model_class)", 404, []);
		}

		if ( get_parent_class($model_class) != "Xpmse\\Model") {
			throw new Excp("非模型数据($model_class)", 402, ["type"=>get_parent_class($model_class)]);	
		}

		$ref_model_class = new \ReflectionClass( $model_class );
		$model_methods = $ref_model_class->getMethods();
		$codes = [];

		foreach ($model_methods as $m ) {
			if ( strtolower("\\{$m->class}") == strtolower($model_class) ) {
				$codes[$m->name] = $this->getMethodCode( $m );
			}
		}

		return $codes;
	}
	

	/**
	 * 读取API代码信息
	 * @param  [type]  $model_class [description]
	 * @param  boolean $nocache     [description]
	 * @return [type]               [description]
	 */
	function getAPICode( $model_class, $nocache = false ) {

		if (!class_exists($model_class) ) {
			throw new Excp("模型不存在($model_class)", 404, []);
		}

		if ( get_parent_class($model_class) != "Xpmse\\Model") {
			throw new Excp("非模型数据($model_class)", 402, ["type"=>get_parent_class($model_class)]);	
		}

		$api_class = str_replace('Model', 'Api', $model_class);
		if (!class_exists($api_class) ) { // API 不存在
			return [];
		}

		$ref_api_class = new \ReflectionClass( $api_class );
		$api_methods = $ref_api_class->getMethods();
		$codes = [];

		foreach ($api_methods as $m ) {
			if ( strtolower("\\{$m->class}") == strtolower($api_class) ) {
				$codes[$m->name] = $this->getMethodCode( $m );
			}
		}

		return $codes;
	}


	/**
	 * 读取方法代码
	 * @param $method ReflectionMethod 对象
	 * @return 方法代码
	 */
	function getMethodCode( & $method ) {

		$filename = $method->getFileName();
		$start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
		$end_line = $method->getEndLine();
		$length = $end_line - $start_line;
		$source = file($filename);
		$body = implode("", array_slice($source, $start_line, $length));
		return $body;

	}


	/**
	 * 清空缓存
	 * @param
	 * @return
	 */
	function cleanCache( $prefix = null ) {
		$cache_list = ["modelStruct", "modelList"];
		if ( $prefix == null ) {
			foreach( $cache_list as $cache_prefix ) {
				$this->cache->delete( $cache_prefix );
			}
		} else {
			$this->cache->delete( $prefix );
		}
	}



	/**
	 * ========== 代码生成相关方法 ================================================================================
	 */
	
	/**
	 * 读取并解析代码配置-生成代码文件树
	 * @param  string $history_id 历史记录(项目)ID
	 * @return array 代码目录树
	 */
	function getCodeFiles( $history_id, $active='/README.md' ) {

		$history = $this->getHistory( $history_id );
		if ( empty($history) ) {
			throw new Excp("未知项目", 404, ['history_id'=>$history_id]);
		}

		$code_id = $history['code_id'];
		if ( empty($code_id) ) {
			throw new Excp("尚未选定代码母版", 404, ['history'=>$history]);
		}

		$this->getOptions($code_id);
		$code = $this->code;
		$opts_file = "{$code['path']}/options/options.json";
		$opts_text = $this->codeGetContent( $opts_file, $history['data'] );
		$opts_data = json_decode( $opts_text, true );

		// 抛出异常
		if ( $opts_data === false || $opts_data === null ) {
			$opts_data = Utils::json_decode( $opts_text );
			return;
		}

		$this->options['files'] = $opts_data['files'];

		$map = []; $mode = [
			"md" => "markdown",
			"json" => "javascript",
			"php"=> "php",
			"js" => "javascript",
			"html"=> "htmlmixed",
			"htm"=> "htmlmixed",
			"page"=> "htmlmixed",
			"wxml"=> "htmlmixed",
			"css"=> "css",
			"wxss"=> "css",
			"less"=> "css",
			"sass"=> "sass",
			"java"=> "java",
			"xml"=> "xml",
			"sql"=> "sql",
			"sh"=> "shell",
			"go"=> "go",
			"py"=> "python",
			"vue" => "vue",
			"txt" => "textile"
		];
		$this->eachCodeFiles( $this->options['files'], function( & $file ) use( $code, $active, $mode ) {

			$pi = pathinfo( $file['name'] );
			$file['path'] = !empty($file['template']) ?  "{$code['path']}/code{$file['template']}" : "{$code['path']}/code{$file['name']}";
			$file['text'] = $pi['basename'];
			$file['ext'] =  strtolower($pi['extension']);
			$file['nodes'] = & $file['children'];

			if ( is_dir($file['path']) ) {
				$file['selectable'] = false;
			} else {
				$file['icon'] = "fa fa-file-code-o";
				$file['mode'] =  empty($mode[$file['ext']]) ? 'textile' : $mode[$file['ext']];
			}

			if ( $file['name'] == $active) {
				$file['state']['selected'] = true;
			}

		});

		$this->eachCodeFiles( $this->options['files'], function(  $file ) use( & $map ) {
			$map[ $file['name']] = $file;
		});

		return ["data"=>$this->options['files'], "map"=>$map];
	}


	/**
	 * 遍历所有文件
	 * @param  [type] $files [description]
	 * @param  [type] $cb    [description]
	 * @return [type]        [description]
	 */
	function eachCodeFiles( & $files, $cb = null ) {

		if ( !is_callable($cb) ) {
			$cb = function( & $node ) {};
		}

		foreach ($files as & $node ) {
			$cb( $node );
			if ( is_array($node['children']) ) {
				$this->eachCodeFiles( $node['children'], $cb );
			}
		}
	}


	/**
	 * 打包下载应用
	 * @param  [type]  $app_id [description]
	 * @param  boolean $return [description]
	 * @return [type]          [description]
	 */
	function codeDownload( $history_id, $return=false ) {

		$history = $this->getHistory( $history_id );
		$files = $this->getCodeFiles( $history['history_id'] ); // 所有代码文件 Files
		$zipFile = new \PhpZip\ZipFile();

		foreach ($files['map'] as $name => $file ) {
			
			if ( is_dir($file['path']) ) {
				$zipFile->addEmptyDir( $name );
				continue;
			}

			$content = $this->codeGetContent( $file['path'], $history['data'], $file );

			// try {
			// 	$content = $this->codeGetContent( $file['path'], $history['data'], $file );
			// }catch( Exception $e ) {
			// 	$content =  $e->getMessage() . "\n{$file['path']}";
			// }
			$zipFile->addFromString($name, $content); // add an entry from the string
		}

		$time = date('Y-m-d-His',strtotime($history['updated_at']));
		$name = "{$history['history_id']}-{$history['name']}-{$time}.zip";

		// ZipFile 
		if ( $return === true ) {
			return $zipFile->outputAsString();
		}

		// 下载
		$zipFile->outputAsAttachment($name);
    }
    

    /**
     * 自动合并代码
     * @param string $file 目标文件地址
     * @param string $newContent 新文件地址
     * @return  成功返回 true,  失败抛出异常
     */
    function codeMergeContent( $file, $newContent ) {
        
        if ( !is_readable($file) ) {
			throw new Excp("无法读取文件($file)", 402,['file'=>$file]);
        }

        $content = file_get_contents( $file );
        $diff = xdiff_string_diff($newContent, $content);
        $diff  = Utils::diffFilter( $diff, ["@KEEP BEGIN", "@KEEP END"] );
        $mergedContent = xdiff_string_patch($newContent, $diff, XDIFF_PATCH_NORMAL, $errors);
        if ( $errors !== null ) {
            throw new Excp("合并代码失败", 500, ["errors"=>$errors]);
        }

        return $mergedContent;
    }


	/**
	 * 渲染代码文件
	 * @param  [type] $file [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function codeGetContent( $file, $data=[], $finfo=[], $project = [] ) {
		if ( !is_readable($file) ) {
			throw new Excp("无法读取文件($file)", 402,['file'=>$file]);
		}
		$content = file_get_contents( $file );
		$twig = $this->getCodeRender( $content );
		
		// file 
		$data['_file'] = $finfo;

		// app & org
		$data['_app'] = empty($GLOBALS['_cm_app']) ? $data['general']['app'] : $GLOBALS['_cm_app'];
		$data['_org'] = empty($GLOBALS['_cm_app']) ? $data['general']['org'] : $GLOBALS['_cm_org'];
		$data['_update'] = date('Y-m-d H:i:s');// 最后修改时间
		$data['_baseon'] = $file; 	// 程序母版地址

		// 项目信息
		$data['_p'] = $project;
		$data['_p']['project_id'] = $data['_p']['history_id'];
		unset( $data['_p']['data']);

		
		// 程序母版信息
		$data['_code'] = empty($this->code) ? [] : $this->code;
		$data['_code']['file'] = $file;

		if ( empty($data['_app']) ){
			$data['_app'] = 'someapp';
		}

		if ( empty($data['_org']) ){
			$data['_org'] = 'someorg';
		}

		return $twig->render('code', $data );
	}


	/**
	 * 创建代码渲染器
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	function getCodeRender( & $content ) {

		$loader = new Twig_Loader_Array(["code" => $content]);
		$twig = new Twig_Environment( $loader, [
			'autoescape'=>false,
			"phpcode" => false,  // 是否包含PHP代码
			"debug" => false  // 是否为 Debug 模式
		]);

		$code = $this->code;
		$options = $this->options;

		// 设定 Filter
		foreach ($options['filters'] as $name => $filter) {
			$twig->addFilter( $filter );
		}

		// 设定标签
		$tag = empty($code['tag']) ? [] : $code['tag'];
		if ( !empty($tag) ) {
			$lexer = new Twig_Lexer($twig, $tag);
			$twig->setLexer($lexer);
		}

		return $twig;
	}



	/**
	 * 读取并解析代码母版 package.json 文件
	 * @param  string $json_file JSON文件地址
	 * @return [type]       [description]
	 */
	function getPackageInfo( $json_file, & $codes ) {

		if ( !is_readable($json_file) ) {
			throw new Excp("无法访问JSON文件($json_file)", 404, ['json_file'=>$json_file]);
		}

		$json_text = file_get_contents($json_file);
		$json_data = json_decode( $json_text, true );
		// 抛出异常
		if ( $json_data === false || $json_data === null ) {
			$json_data = Utils::json_decode( $json_text );
			return;
		}
		$path_current[]  = $json_data['path'] = $code_path = dirname( $json_file );
	
		// 读取图片
		$images = glob("$code_path/images/*.*");
		foreach ($images as & $img ) {
			try {
				$rs = $this->meida->uploadFile( $img );
			} catch( Excp $e ) {
				$rs = [];
			}

			if ( !empty($rs['path']) ) {
				$img = $rs['path'];
			}
		}

		$json_data['images'] = $images;
		$codes[$code_path] = $json_data;

		return $json_data;
	}


	/**
	 * 检索已安装代码母版
	 * @param  array  $query 查询条件
	 * @return 
	 */
	function search( $query = [] ) {

		$qb = $this->query();

		// 选中结果
		$select = empty($query['select']) ? ['*'] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 查询一级分类
		$response = $qb->select($select)->pgArray($perpage, ['_id'], 'page', $page);
		foreach ($response['data'] as & $rs ) {
			$this->format( $rs );
		}

		return $response;
	}



	/**
	 * 搜索代码模板目录, 更新数据表
	 * @param  string $path 代码模板路径
	 * @param  function $cb 回调函数
	 * @return true / 抛出异常
	 */
	function scan( $path = null, $cb =null ) {
		
		$path = empty( $path ) ? $this->root : $path ;
		$codes = $this->query()->select('path')->get()->toArray();
		$path_latest = array_column( $codes, 'path');
		$path_latest = !is_array($path_latest) ? [] : $path_latest;
		$json_files = glob("{$path}/*/*/package.json");
		$codes_frompath = []; $path_current = [];

		// 根据 JSON 文件读取数据
		foreach ($json_files as $json_file) {
			$this->getPackageInfo( $json_file, $codes_frompath );
			$path_current[] = dirname( $json_file );
		}


		// 数组差集
		$path_remove = array_diff( $path_latest , $path_current );

		// 删除数据
		$errors = [];
		foreach ($path_remove as $path ) {
			try {
				$resp = $this->remove($path, 'path' );
			}catch( Excp $e ){
				$errors[] = $e->toArray();
			}

			if ( $resp === false ) {
				$errors[] = ['code'=>500, 'message'=>'删除失败', ['extra'=>['path'=>$path]]];
			}
		}

		// 添加或更新数据
		$rows =[];
		foreach ($codes_frompath as $path=>$code ) {
			try {
				$code['deleted_at'] = null;
				$rs = $this->saveBy( 'code_id',  $code, ['code_id', 'path', 'slug'] );
			} catch( Excp $e ){
				$errors[] = $e->toArray();
				continue;
			}
			$this->format( $rs );
			$rows[] = $rs;
		}

		if ( !empty($errors) ) {
			throw new Excp("扫描代码模板信息出错", 500, ['errors'=>$errors] );
			
		}

		return $rows;
	}

	/**
	 * 处理程序输出结果
	 * @param  array $rs 
	 * @return array []
	 */
	function format( & $rs ) {

		if ( array_key_exists('images', $rs) && is_array($rs['images']) && count($rs['images']) > 0 ) {
			foreach ($rs["images"] as & $img ) {
				if ( is_string($img) ) {
					$img = $this->meida->get( $img );
				}
			}
		}
		return $rs;
	}


	/**
	 * 重载标记删除程序
	 * @param  [type]  $data_key  [description]
	 * @param  string  $uni_key   [description]
	 * @param  boolean $mark_only [description]
	 * @return [type]             [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ) {

		if ( $mark_only === true ) {
			$time = date('Y-m-d H:i:s');
			$row = $this->updateBy( $uni_key, [
				'deleted_at'=>$time, "$uni_key"=>$data_key, 'slug'=>null
			]);
			if ( $row['deleted_at'] == $time ) {
				return true;
			}
			return false;
		}

		return parent::remove( $data_key, $uni_key, $mark_only );
	}


	/**
	 * 重载保存函数
	 * @param  [type] $uniqueKey [description]
	 * @param  [type] $data      [description]
	 * @param  [type] $keys      [description]
	 * @param  array  $select    [description]
	 * @return [type]            [description]
	 */
	function saveBy( $uniqueKey, $data, $keys=null, $select=['*']  ) {

		if ( empty($data['slug']) && !empty($data['name']) && !empty($data['org']) ) { 
			$data['slug'] = "DB::RAW(CONCAT(`org`, '_', `name`))";
		}

		return parent::saveBy( $uniqueKey, $data, $keys, $select );
	}


	/**
	 * 重载创建程序
	 * @param  array $data []
	 * @return array $rs 新增的记录
	 */
	function create( $data ) {
		if ( empty($data['code_id']) ) {
			$data['code_id'] = $this->genId();
		}	

		if ( empty($data['slug']) ) { 
			$data['slug'] = "DB::RAW(CONCAT(`org`, '_', `name`))";
		}
		
		return parent::create( $data );
	}

}























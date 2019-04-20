<?php
namespace Xpmse\Loader;

use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;

use \ZipArchive as ZipArchive;

/**
 * UI代码生成器
 */
class Design extends \Xpmse\Loader\Controller {
	
	var $nocache = false;
	var $model_name = null;

	function __construct( $nocache=false, $model_name = null ) {
		$this->nocache = $nocache;
		$this->model_name = $model_name;
	}


	/**
	 * 上传模拟器
	 * @return [type] [description]
	 */
	public function  uploader() {
		echo json_encode(['code'=>500,'message'=>'模拟上传成功']);
	}


	/**
	 * UI代码生成器入口
	 */
	public function index() {


		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';
		$data = $this->_data(['name'=>$name,'action'=>$action], '界面生成器');
		$this->_render($data, 'design');
		return  [	
					'js' => [
						'js/plugins/jquery-validation/jquery.validate.min.js',
						'js/plugins/select2/select2.full.js',
						'js/plugins/masked-inputs/jquery.maskedinput.min.js',
						'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
						'js/plugins/masked-inputs/jquery.maskedinput.min.js',
						'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
						'js/plugins/bootstrap-colorpicker/bootstrap-colorpicker.min.js',
						'js/plugins/codemirror/lib/codemirror.js',
						'js/plugins/codemirror/addon/display/autorefresh.js',
						'js/plugins/codemirror/addon/search/searchcursor.js',
						'js/plugins/codemirror/addon/search/search.js',
						'js/plugins/codemirror/addon/dialog/dialog.js',
						'js/plugins/codemirror/addon/edit/matchbrackets.js',
						'js/plugins/codemirror/addon/edit/closebrackets.js',
						'js/plugins/codemirror/addon/comment/comment.js',
						'js/plugins/codemirror/addon/wrap/hardwrap.js',
						'js/plugins/codemirror/addon/fold/foldcode.js',
						'js/plugins/codemirror/addon/fold/brace-fold.js',
						'js/plugins/codemirror/mode/javascript/javascript.js',
						'js/plugins/codemirror/mode/sql/sql.js',
						'js/plugins/codemirror/mode/php/php.js',
						'js/plugins/codemirror/mode/htmlmixed/htmlmixed.js',
						'js/plugins/codemirror/mode/xml/xml.js',
						'js/plugins/codemirror/mode/css/css.js',
						'js/plugins/codemirror/mode/clike/clike.js',
						'js/plugins/codemirror/keymap/sublime.js',
						'js/plugins/handsontable/handsontable.full.min.js',
						'js/plugins/handsontable/zeroclipboard/ZeroClipboard.js',
						"js/plugins/jquery-ui/jquery-ui.min.js",
						"js/plugins/bootstrap-bwizard/bwizard.js",
				 		"js/plugins/dropzonejs/dropzone.min.js",
				 		"js/plugins/cropper/cropper.min.js",
				 		"js/plugins/jquery-form-designer/jquery.formdesigner.js",
				 		"js/plugins/jquery-form-designer/jquery.formdesigner.toolbar.js",
				 		"js/plugins/jquery-form-designer/jquery.formdesigner.tabs.js"
					],
					'css'=>[
						'js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css',
						'js/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css',
						'js/plugins/jquery-tags-input/jquery.tagsinput.min.css',
						'js/plugins/codemirror/lib/codemirror.css',
						'js/plugins/codemirror/addon/fold/foldgutter.css',
						'js/plugins/codemirror/addon/dialog/dialog.css',
						'js/plugins/codemirror/theme/monokai.css',
						'js/plugins/handsontable/handsontable.full.min.css',
						"js/plugins/bootstrap-bwizard/bwizard.css"
					],
					'crumb' => [
		                "脚手架" => $data['_HOME'],
		                "界面设计 ({$data['_NAME']}) " => ""
		        	]
            ];
	}


	/**
	 * 载入选择界面模板
	 * @return [type] [description]
	 */
	function uitemplate(){
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$data = $this->_data(['name'=>$name], '界面生成器');
		$this->_render($data, 'template');
	}

	/**
	 * 载入界面样例
	 * @return [type] [description]
	 */
	function uitemplateSample() {
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';

		$data = $this->_data(['name'=>$name,'action'=>$action], '界面生成器');
		$this->_render($data, "template/$name/$action");
	}


	/**
	 * 载入元素设定页面
	 */
	function uisetting() {
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';
		$data = $this->_data(['name'=>$name, 'action'=>$action], '界面生成器');
		$this->_render($data, "template/$name/setting");
	}


	/**
	 * 设定元素界面工具条
	 * @return [type] [description]
	 */
	function uisettingToolbar(){
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$data = $this->_data(['name'=>$name, 
			'components'=>$this->_components(
				['inlinetext','numtext', 'multilinetext','select', 'radio', 'checkbox', 'switch', 'datepicker', 'colorpicker', 'tagsinput', 'btngroup'],
				['imageuploader', 'fileuploader', 'ueditor']
			)],
			'界面生成器');
		$this->_render($data, "template/$name/editor.toolbar");
	}


	/**
	 * 界面编辑器
	 * @return [type] [description]
	 */
	function uisettingEditor(){
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';
		$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '0';

		$data = $this->_data(['name'=>$name,'action'=>$action, 'tab'=>$tab], '界面生成器');
		$this->_render($data, "template/$name/editor.$action");
	}


	/**
	 * 保存数据到SESSION
	 * @return [type] [description]
	 */
	function uisave() {
		$name = (isset($_POST['name'])) ? $_POST['name'] : 'tabforms';
		$action = (isset($_POST['action'])) ? $_POST['action'] : 'index';
		$data = $_POST['data'];
		$_SESSION["uidata_{$name}_{$action}"] = json_encode($data);
		echo json_encode(['code'=>0, 'message'=>'success']);
	}



	/**
	 * 载入生成代码界面
	 * @return [type] [description]
	 */
	function uibuilder(){
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';
		
		// $ctrlname = ucfirst(strtolower($name)) . 'ctr';
		$ctrlname ='Default';
		$modelname = 'Onemod';
		$viewname = strtolower($name);
		// $modelname = ucfirst(strtolower($name)) . 'Onemod';
		// $viewname = ucfirst(strtolower($name)) . 'tpl';


		$codename = json_decode($_SESSION["uiname_{$name}_{$action}"], true);
		if ( $codename != null ) {
			$ctrlname = $codename['ctrl'];
			$modelname= $codename['mod'];
			$viewname= $codename['tpl'];
		}


		$ctrlname = (isset($_GET['ctrlname'])) ? ucfirst(strtolower($_GET['ctrlname'])) : $ctrlname;
		$modelname = (isset($_GET['modelname'])) ? ucfirst(strtolower($_GET['modelname'])) :  $modelname;
		$viewname = (isset($_GET['viewname'])) ? ucfirst(strtolower($_GET['viewname'])) : $viewname;
		$_SESSION["uiname_{$name}_{$action}"] = json_encode(['ctrl'=>$ctrlname, 'mod'=>$modelname, 'tpl'=>$viewname]);

		$uidata = json_decode($_SESSION["uidata_{$name}_{$action}"], true);

		$data = $this->_data([
			'name'=>$name, 
			'action'=>$action, 
			'ctrlname'=>$ctrlname,
			'modelname'=>$modelname,
			'viewname'=>$viewname,
			'uidata' => $uidata
		], '界面生成器');
		
		$this->_render($data, "template/$name/builder");
	}

	
	/**
	 * 生成代码
	 * @param  [type] $template [description]
	 * @param  array  $data     [description]
	 * @return [type]           [description]
	 */
	function gencode() {
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';

		$template = $_GET['template'];
		$data = array_merge( $_GET,$_POST);
		$tpl = "{$template}.code";
		$source = $this->_render($data, "template/$name/$tpl", 'view', true);
		echo htmlspecialchars($source);
	}


	/**
	 * 生成代码，并打包下载
	 * @return [type] [description]
	 */
	function codeDownload() {
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		$action = (isset($_GET['action'])) ? $_GET['action'] : 'index';

		$tmp_root = "/tmp";
		$tmp_name = 'code_'.time() . mt_rand(10000,99999);
		$tmp_dir =  "$tmp_root/$tmp_name";
		$tmp_zip =  "$tmp_root/$tmp_name/$name.zip";

		if ( !mkdir($tmp_dir) ) {
			echo json_encode(['code'=>500, 'message'=>'临时目录不可写']);
			return ;
		}

		if ( !is_writeable($tmp_dir) ) {
			echo json_encode(['code'=>500, 'message'=>'临时目录不可写']);
			return;
		}


		// 制作压缩包
		$zip = new ZipArchive();
		if ( !$zip->open($tmp_zip,ZipArchive::OVERWRITE) ) {
			echo json_encode(['code'=>500, 'message'=>'创建压缩包失败']);
			return;
		}
		foreach ($_POST as $code ) {
			if (!is_array($code) ) { continue;}
			$zip->addFromString("{$code['type']}/{$code['file']}", $code['value']);
		}
		$zip->close();
		echo json_encode(['code'=>0, 'link'=>App::NR($this->route['controller'],'download',['tmp_zip'=>$tmp_zip])]);

	}

	/**
	 * [download description]
	 * @return [type] [description]
	 */
	function download() {
		$tmp_zip = $_GET['tmp_zip'];
		if (!file_exists($tmp_zip) ) {
			echo json_encode(['code'=>404, 'message'=>'文件不存在']);
			return;
		}

		if ( substr($tmp_zip, 0, 10) != '/tmp/code_') {
			echo json_encode(['code'=>404, 'message'=>'非法请求']);
			return;	
		}


		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.$tmp_zip);
		header('Content-Length: ' . filesize($tmp_zip));
		readfile($tmp_zip);
		$this->rmrfdir(dirname($tmp_zip));
	}


	protected function rmrfdir($dir) {
	  //先删除目录下的文件：
	  $dh=opendir($dir);
	  while ($file=readdir($dh)) {
	    if($file!="." && $file!="..") {
	      $fullpath=$dir."/".$file;
	      if(!is_dir($fullpath)) {
	          unlink($fullpath);
	      } else {
	          $this->rmrfdir($fullpath);
	      }
	    }
	  }
	 
	  closedir($dh);
	  //删除当前文件夹：
	  if(rmdir($dir)) {
	    return true;
	  } else {
	    return false;
	  }
	}


	/**
	 * 模型结构数据
	 * @return [type] [description]
	 */
	function modeloption() {
		$model = (isset($_GET['name'])) ? $_GET['name'] : null;
		header('Content-Type: application/json');
		
		try {
			$m = App::M($model);
		} catch( Excp $e ) {
			// echo $e->toJSON();
			echo json_encode(['results'=>[], 'html'=>'<option></option>']);
			return ;
		} catch (Exception $e ) {
			// Excp::etoJSON($e);
			echo json_encode(['results'=>[], 'html'=>'<option></option>']);
			return;
		}

		$sheet = $m->sheet();
		$columns = (isset($sheet['columns'])) ? $sheet['columns'] : [];
		$options = [];
		$data = [];
		$opt = [];
        foreach ($columns as $key => $type){
        	$t = $type->toArray();
            $dt = $t['data'];
            $opt['id']  = $key;
            $opt['text']  = $dt['screen_name'].'('.$key.')';
            $option =  '<option value="'.$key.'"';
            foreach ($dt as $k=>$v) {
            	$option .= 'data-'.$k.'="'.$v.'"' . "\n";
            	$opt['data-'.$k] = $v;
            }
            $option .= '>' .$dt['screen_name'].'('.$key.')</option>';

            array_push($options, $option );
            array_push($data, $opt );
        }

        echo json_encode(['results'=>$data, 'html'=>"<option></option>\n" . implode("\n",$options)]);


        // echo json_encode(['code'=>0, 'html'=>implode("\n",$options)]);
		// $data = $this->_data(['columns'=>$columns,  'name'=>$name, 'model'=>$model, 'index'=>$m->indexName(false, true) ], '界面生成器');
		// echo json_encode($data);

	}


	/**
	 * 模型选取器
	 * @param  [type] $model [description]
	 * @return [type]        [description]
	 */
	protected function _modelSelector( $model = null ) {
		$name = (isset($_GET['name'])) ? $_GET['name'] : 'tabforms';
		// if ( $model === null ) {
		// 	return "";
		// }

		// $m = App::M($model);
		// $sheet = $m->sheet();
		// $columns = (isset($sheet['columns'])) ? $sheet['columns'] : [];
		// $data = $this->_data(['columns'=>$columns,  'name'=>$name, 'model'=>$model, 'index'=>$m->indexName(false, true) ], '界面生成器');
		$data = $this->_data(['name'=>$name, 'model'=>$model], '界面生成器');
		return $this->_render($data, "defaults/selector.model", 'components', true);

	}



	/**
	 * 读取组件清单，默认返回所有
	 * @param  array  $std     [description]
	 * @param  array  $ext     [description]
	 * @param  string $setting [description]
	 * @return [type]          [description]
	 */
	protected function _components( $std=null, $ext=null, $group='defaults' ) {
		
		$result = ['setting'=>'', 'standard'=>[], 'extension'=>[] ];

		$mem = new Mem(false, "COMPONENTS/");
		$components_text = false;

		if ( !$this->nocache ) {
			$cache = "$group";
			$setting = null;
			$components_text = $mem->get($cache);
		}

		if ( $components_text === false ) {
			$resp = $this->_build_all_components($group);
			$components = $resp['components'];
			$setting  = $resp['setting'];
		} else {
			$components = json_decode($components_text, true);
			if ($components == null && json_last_error() ) {
				$e = new Excp( '编译组件错误(' . json_last_error_msg() .')', 500, ['pathname'=>$pathname]);
				$e->log();
				return $result;
			}
		}

		if ( $setting == null  ) {
			 $setting = $mem->get("$cache/setting");
		}

		

		if ( $std == null ) {
			foreach ($components as $com ) {
				$result['standard'][] = $com;
			}
		} else {

			foreach ($std as $st) {
				if (isset($components[$st])) {
					$result['standard'][] = $components[$st];
				}
			}

			foreach ($ext as $ex) {
				if (isset($components[$ex])) {
					$result['extension'][] = $components[$ex];
				}
			}
		}


		// 处理SETTING
		$components_option = '';
		$coms = array_merge($result['standard'],$result['extension']);
		foreach ($coms as $com) {
			$components_option  .= "<option value=\"{$com['slug']}\">{$com['name']}</option>\n";
		}
		

		// 检查是否有模板
		try {
			$model_selector = $this->_modelSelector($this->model_name);
		} catch( Excp $e ) {
			$e->log();
			$model_selector = "";
		}

		// 更新数据
		$setting = $this->_replaceENV([
			'COMPONENTS_OPTION'=>htmlspecialchars($components_option),
			'MODEL_SELECTOR'=>htmlspecialchars($model_selector)
		], $setting );

		$result['setting'] = $setting;

		return $result;
	}


	/**
	 * 读取并编译所有组件
	 * @return [type] [description]
	 */
	protected function _build_all_components( $group='defaults' ) {
		$mem = new Mem(false, "COMPONENTS/");
		$cache = "$group";

		$env = [
			'APP_HOME' => APP::$APP_HOME,
			'APP_ROOT' => APP::$APP_ROOT,
			'APP_HOME_PORTAL' => APP::$APP_HOME_PORTAL,
			'APP_HOME_NOFRAME' => APP::$APP_HOME_NOFRAME,
			'ROUTE_CONTROLLER' => $this->route['controller'],
			'APP_HOME_STATIC' => APP::$APP_HOME_STATIC,
			'DOMAIN' => Conf::G('general/domain'),
			'HOMEPAGE' => Conf::G('general/homepage'),
			'STATIC' => Conf::G('general/static'),
			'APPHOST' => Conf::G('general/apphost'),
			'API' => Conf::G('general/api')
		];


		$components = [];
		$ut = new Utils;
		$root = $ut->getServiceRoot();
		$path =  "{$root}/assets/design/components/$group";

		if ( !is_dir($path) ) {
			return false;
		}

		$dir = @dir($path);  
		while (($name = $dir->read())!==false) {
			 if(is_dir($path."/".$name) && $name != "." && $name != "..") {
			 	$component = $this->_build_component( $path."/".$name, $env );
			 	if (is_array($component) && isset($component['name']) ) {
			 		$components[$name] = $component;
			 	}
			 }
		}

		if ( file_exists("$path/setting.html") ) {
			$value = file_get_contents("$path/setting.html");
			$setting = htmlspecialchars($this->_replaceENV($env, $value));
		}

		$mem->set($cache, json_encode($components));
		$mem->set("$cache/setting", $setting);
		return ['components'=>$components, 'setting'=>$setting];
	}



	/**
	 * 编译组件
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	protected function _build_component( $pathname, $env ) {

		$com = [];
		$ut = new Utils;

		if ( !file_exists("$pathname/package.json") ) {
			$e = new Excp( '编译组件错误(缺少package.json)', 404, ['pathname'=>$pathname]);
			$e->log();
			return false;
		}

		// 读取配置
		$json_text = file_get_contents("$pathname/package.json");
		$com = json_decode($json_text, true);
		if ( $com == null && json_last_error() ) {
			$e = new Excp( '编译组件错误(' . json_last_error_msg() .')', 500, ['pathname'=>$pathname]);
			$e->log();
			return false;
		}


		// 解析组件
		
		// Load
		foreach ($com['load'] as $name => $list ) {
			foreach ($list as $idx=>$val) {
				$com['load'][$name][$idx] = $this->_replaceENV($env, $val);
			}
		}

		// Source script setting
		$reads = ['source', 'script', 'setting'];
		foreach ($reads as $key) {		
			if ( $com[$key] != ""  && file_exists("$pathname/{$com[$key]}") ) {
				$value = file_get_contents("$pathname/{$com[$key]}");
				$com[$key] = htmlspecialchars($this->_replaceENV($env, $value));
			}
		}

		if ( $com['source.php'] != ""  && file_exists("$pathname/{$com['source.php']}") ) {
			$com['source.php'] = "$pathname/{$com['source.php']}";
		}

		if ( $com['script.php'] != ""  && file_exists("$pathname/{$com['script.php']}") ) {
			$com['script.php'] = "$pathname/{$com['script.php']}";
		}

		return $com;
	}

	/**
	 * 根据数据渲染组件
	 * @param  [type] $data [description]
	 * @param  [type] $slug [description]
	 * @return [type]       [description]
	 */
	protected function _renderComponent( $data,  $slug=null, $com=null, $ext=[]) {
		
		$slug  = (isset($data['slug'])) ? $data['slug'] : $slug;
		if ( $slug == null  && $com == null ) return '';

		$env = [
			'APP_HOME' => APP::$APP_HOME,
			'APP_ROOT' => APP::$APP_ROOT,
			'APP_HOME_PORTAL' => APP::$APP_HOME_PORTAL,
			'APP_HOME_NOFRAME' => APP::$APP_HOME_NOFRAME,
			'ROUTE_CONTROLLER' => $this->route['controller'],
			'APP_HOME_STATIC' => APP::$APP_HOME_STATIC,
			'DOMAIN' => Conf::G('general/domain'),
			'HOMEPAGE' => Conf::G('general/homepage'),
			'STATIC' => Conf::G('general/static'),
			'APPHOST' => Conf::G('general/apphost'),
			'API' => Conf::G('general/api'),
		];

		$env = array_merge( $env, $ext);


		$source = '';
		if ( $com == null ) {
			$comlist = $this->_components([$slug], []);
			$com = end($comlist['standard']);
		}

		if ( isset($data['rule']) ) {
			foreach ($data['rule'] as $vkey => $ru) {
				$data['rule'][$vkey]['message'] = $this->_replaceENV($data, $ru['message'] );
			}
		}


		$source = $this->_render($data, null, null, true, $com['source.php']);
		if (!defined($slug . '_source_once') ) {
			define($slug . '_source_once', true);
		}

		$source = $this->_replaceENV($env, $source);

		return $source;
	}



	/**
	 * 获取组件需要加载的数据列表
	 * @param  [type] $components [description]
	 * @return [type]             [description]
	 */
	protected function _getLoads( $components ) {
		$commap =  []; $stds = [];  $comlist = []; 
		$loads = ['js'=>[],'css'=>[]];
		$assets_url = Conf::G('general/static') . "/assets/";

		foreach ($components as $idx=>$data ) {
			$slug = $data['slug'];
			if ( !in_array($slug, $stds) ) {
				array_push($stds, $slug);
			}
		}

		$comlist = $this->_components($stds, []);
		foreach ($comlist['standard'] as $com ) {
			$slug = $com['slug'];
			$commap[$slug] = $com;
		}

		foreach ($commap as $com ) {
			if ( !empty($com['load']) ) {
				if ( is_array($com['load']['js'])) {
					$loads['js']= array_merge( $loads['js'], $com['load']['js']);
				}
				if ( is_array($com['load']['css'])) {
					$loads['css']= array_merge( $loads['css'], $com['load']['css']);
				}
			}
		}

		$loads['js'] = array_unique($loads['js']);
		$loads['css'] = array_unique($loads['css']);
		foreach ($loads as $type=>$arr ) {
			foreach ($arr as $idx=>$val ) {
				$loads[$type][$idx] = str_replace($assets_url, '', $val);
			}
		}

		return $loads;
	}



	/**
	 * 根据组件信息渲染脚本
	 * @param  [type] $components [description]
	 * @return [type]             [description]
	 */
	protected function _renderScripts( $components, $ext=[] ) {

		$commap =  []; $stds = [];  $comlist = []; 
		$scripts = [];

		$env = [
			'APP_HOME' => APP::$APP_HOME,
			'APP_ROOT' => APP::$APP_ROOT,
			'APP_HOME_PORTAL' => APP::$APP_HOME_PORTAL,
			'APP_HOME_NOFRAME' => APP::$APP_HOME_NOFRAME,
			'APP_HOME_STATIC' => APP::$APP_HOME_STATIC,
			'ROUTE_CONTROLLER' => $this->route['controller'],
			'DOMAIN' => Conf::G('general/domain'),
			'HOMEPAGE' => Conf::G('general/homepage'),
			'STATIC' => Conf::G('general/static'),
			'APPHOST' => Conf::G('general/apphost'),
			'API' => Conf::G('general/api'),
		];
		$env = array_merge( $env, $ext);


		foreach ($components as $idx=>$data ) {
			$slug = $data['slug'];
			if ( !in_array($slug, $stds) ) {
				array_push($stds, $slug);
			}
		}

		$comlist = $this->_components($stds, []);
		foreach ($comlist['standard'] as $com ) {
			$slug = $com['slug'];
			$commap[$slug] = $com;
		}

		foreach ($components as $idx=>$data ) {
			$slug = strtolower($data['slug']);
			if ( !isset($commap[$slug]) ) { continue; }
			$com = $commap[$slug];

			if ( !empty($com['script.php']) ) {
				try {


					$script = $this->_render($data, null, null, true, $com['script.php']);
					$script = $this->_replaceENV($env, $script);
					
				} catch (Exception $e ) {
					array_push($scripts, $e->getMessage() );
					continue;
				}

				if ( !empty(trim($script)) ) {
					array_push($scripts, $script);
				}

				if (!defined($slug . '_script_once') ) {
					define($slug . '_script_once', true);
				}
			}
		}

		return $scripts;
	}





	/**
	 * 每行插入数据
	 * @param  [type] $string [description]
	 * @param  [type] $source [description]
	 * @return [type]         [description]
	 */
	protected function lineInsert($string, $source ) {
		$result = '';
		$lines = explode("\n", $source);
		foreach ($lines as $line) {
			$result .= $string . $line . "\n";
		}
		return $result;
	}



	/**
	 * 替换变量
	 * @param  [type] $keys [description]
	 * @return [type]       [description]
	 */
	protected function _replaceENV( $evn, $string ) {
		foreach ($evn as $key => $val ) {
			$string = str_replace('{'.$key.'}', $val, $string );
			$string = str_replace('{'.strtoupper($key).'}', $val, $string );
		}
		return $string;
	}



	/**
	 * 数据生成器
	 * @param  [type] $data  [description]
	 * @param  string $title [description]
	 * @return [type]        [description]
	 */
	protected function _data( $data, $title='' ) {

		return array_merge([
				'_TITLE' => $title,
				'_ROUTE' => $this->route,
				'_HOME' => APP::R($this->route['controller'],'index'),
				'_NAME' =>  get_class($this->model),
				'_APP' => $this->headers['Xpmse-Appname'],
				'_TPLS' => [
					'tabforms'=>[
						'name'=>'标签表单组',
						'platform'=>'desktop'
					],
					'selist'=>[
						'name'=>'搜索分页列表',
						'platform'=>'desktop'
					]
				]

			], $data);

	}


	/**
	 * 渲染器
	 * @param  [type] $data [description]
	 * @param  [type] $view [description]
	 * @return [type]       [description]
	 */
	protected function _render($data, $sharp, $path='view', $return = false, $layout_file = null ) {
		
		if ($layout_file == null) {
			$ut = new utils;
			$root = $ut->getServiceRoot();
			$layout_file =  $root ."/assets/design/$path/" . $sharp . '.tpl.html';
		}


		if ( !file_exists($layout_file) ) {
			$e =  new Excp('载入模板错误(模板不存在)', 404, ['layout_file'=>$layout_file]);
			$e->log();
			if ( $return ) {
				ob_end_clean();
				return '载入模板错误(模板不存在 '.$layout_file.')';
			}

			echo '载入模板错误(模板不存在'.$layout_file.')';
			return;

		}

		if ( $return ) {
			ob_start();
		}

		@extract( $data );
		try {
			require( $layout_file );
		} catch ( Exception $e  ) {
			$e = new Excp('载入模板错误('.$e->getMessage().')', 500);
			$e->log();
			if ( $return ) {
				ob_end_clean();
				return '载入模板错误('.$e->getMessage().')';
			}

			echo '载入模板错误('.$e->getMessage().')'; 
			return;
		}

		if ( $return ) {
			$content = ob_get_contents();
		   	ob_end_clean();
		   	return $content;
		}
	}

}
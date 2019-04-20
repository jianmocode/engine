<?php
namespace Xpmse\Loader;

use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;


class Scaffold extends \Xpmse\Loader\Controller {
	
	private $model = null;
	private $model_name = null;

	function __construct( $model_name ) {
		$this->model = App::M($model_name);
		$this->model_name = $model_name;
	}



	/**
	 * 脚手架入口
	 * @return [type] [description]
	 */
	public function index() {		

		$data = $this->_data(['ok'=>"INDEX OK"], '脚手架');
		$this->_render($data, 'index');

		return  [	
					'js' => [
						'js/plugins/jquery-validation/jquery.validate.min.js',
						'js/plugins/select2/select2.full.min.js',
						'js/plugins/masked-inputs/jquery.maskedinput.min.js',
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
						'js/plugins/handsontable/zeroclipboard/ZeroClipboard.js'

					],
					'css'=>[
						'js/plugins/codemirror/lib/codemirror.css',
						'js/plugins/codemirror/addon/fold/foldgutter.css',
						'js/plugins/codemirror/addon/dialog/dialog.css',
						'js/plugins/codemirror/theme/monokai.css',
						'js/plugins/handsontable/handsontable.full.min.css',
					],
					'crumb' => [
		                "脚手架" => $data['_HOME'],
		                "{$data['_NAME']}" => ""
		        	]
            ];
	}


	/**
	 * 打印数据结构
	 * @return [type] [description]
	 */
	public function schema() {

		$sheet = $this->model->sheet();
		$columns = (isset($sheet['columns'])) ? $sheet['columns'] : [];
		$data = $this->_data(['columns'=>$columns,  'index'=>$this->model->indexName(false, true) ], '数据结构');
		$this->_render($data, 'schema');
	}


	/**
	 * 更新数据结构
	 * @return [type] [description]
	 */
	public function updateschema() {
		$this->model->__schema();
		echo json_encode(['code'=>0, 'message'=>'更新成功']);
	}


	/**
	 * 重建索引
	 * @return [type] [description]
	 */
	public function updateschemaindex(){
		App::M($this->model_name)->rebuildIndex();
		sleep(1);
		echo json_encode(['code'=>0, 'message'=>'更新成功']);
	}


	/**
	 * 重建数据结构
	 */
	public function rebuildschema() {
		$this->model->__destory();
		sleep(1);
		App::M($this->model_name)->__schema();
	}


	/**
	 * 更新/录入表单
	 * @return [type] [description]
	 */
	public function dataform() {

		$_id = (isset($_GET['_id'])) ? intval($_GET['_id']) : null;
		$sheet = $this->model->sheet();
		$columns = (isset($sheet['columns'])) ? $sheet['columns'] : [];

		$row = [];
		if ($_id !== null) {
			$row = $this->model->getLine("where _id=$_id LIMIT 1");
			foreach ($row as $key => $value) {

				$type = $sheet['_spt_schema_json'][$key]['type'];
				if ( $type=='BaseArray' || $type=='BaseNested' || $type=='BaseObject' ) {
					$row[$key] = json_encode($value);
				} else if ($type=='BaseBool' ) {
					$row[$key]  = ($value === false) ? "0" : "1";
				}
			}
		}

		$data = $this->_data(['columns'=>$columns, '_id'=>$_id, 'data'=>$row], '数据结构');
		$this->_render($data, 'dataform');

	}



	/**
	 * 删除一条记录
	 * @return [type] [description]
	 */
	public function datadelete() {

		$_id= intval($_POST['id']);
		$this->model->delete($_id);
		sleep(1);
		echo json_encode(['message'=>'操作成功','_id'=>$_id]);
	}




	/**
	 * 创建或者更新数据
	 * @return [type] [description]
	 */
	public function datasave() {

		$sheet = $this->model->sheet();
		$_id = (isset($_POST['_id']) ) ? intval($_POST['_id']) : null;
		foreach ($_POST as $key => $value) {
			
			$_POST[$key] = htmlspecialchars_decode($value);
			
			if ( $value == "" || !isset($sheet['_spt_schema_json'][$key]) ) {
				unset($_POST[$key]);
				continue;
			} 

			$type = $sheet['_spt_schema_json'][$key]['type'];
			if ( $type=='BaseArray' || $type=='BaseNested' || $type=='BaseObject' ) {
				$_POST[$key] = json_decode($_POST[$key], true);
				if ( $_POST[$key] === null ) {
					unset($_POST[$key]);
				}
			} else if ($type=='BaseBool' ) {
				if ( $_POST[$key] ==  "1" || $_POST[$key] == "on" ) {
					$_POST[$key] = true;
				} else {
					$_POST[$key] = false;
				}
			}
		}

		if ( $_id == null ) {
			$resp = $this->model->create($_POST);
		} else {
			$resp = $this->model->update($_id, $_POST);
		}

		if ( $resp === false ) {
			$extra = [];
			$errors = (is_array($this->model->errors)) ? $this->model->errors : [];

			foreach ($errors as $cname=>$error ) {
				$error = (is_array($error)) ? end($error) : [];
				$field = (isset($error['field'])) ? $error['field'] : 'error';
				$message = (isset($error['message'])) ? $error['message'] : '系统错误,请联系管理员。';
				$extra[] = ['_FIELD'=>$field,'message'=>$message];
			}

			$e = new Excp( '系统错误,请联系管理员。', '500', $extra);
				$e->log();
				echo $e->error->toJSON(); 

			return ;
		}
		
		echo json_encode($resp);
	}


	/**
	 * 数据列表页面
	 * @return [type] [description]
	 */
	public function find(){

		$ut = new Utils;
		$_sql = isset($_GET['sql']) ? $ut->unescape($_GET['sql']) : "WHERE";
		$sql = strtolower( $ut->unescape($_GET['sql']));
		$sql = str_replace('where', '', $sql);
		$page = (isset($_GET['page'])) ? $_GET['page'] : 1;
		$perpage = (isset($_GET['perpage'])) ? intval($_GET['perpage']) : 15;
		// $sheet = $this->model->sheet();
		$sheet = '';
		try{
			// $items = $this->model->vquery("$sql", $page, $perpage);
		} catch( Excp $e ) {
			$items=[];
			// return;
		}

		// $data = $items->toArray();
		$data = [];
		$heads = [];
		$headkeys = [];

		$error = null;
		$line = end($data);
		if (isset($line['code']) && $line['code'] > 0 ) {
			$error = $line;
		}

		if ( is_array($line) ) {
			foreach ($line as $key=>$value) {
				$heads[$key] = [];
			}
		}

		foreach ($sheet['_spt_schema_json'] as $key => $type) {
			$heads[$key] = $type['option'];
			$heads[$key]['type'] = $type['type'];
			//$headkeys[] = (isset($type['option']['screen_name']) ) ? "{$key}({$type['option']['screen_name'] })" : $key;
		}

		$heads["z"] = []; // 空白字段
		$colskeys = array_keys($heads);
		foreach ($colskeys as $idx=>$key ) {
			$type = $sheet['_spt_schema_json'][$key];
			$headkeys[$idx] = (isset($type['option']['screen_name']) ) ? "{$key}({$type['option']['screen_name'] })" : $key;
		}

		sort($headkeys);
		sort($colskeys);

		$ndata = [];
		foreach ($data as $idx=>$dt ) {
			$row = [];
			foreach ($colskeys as $hkey ) {
				$htype = $sheet['_spt_schema_json'][$hkey];
				$type = $htype['type'];
				$val = ( isset($dt[$hkey]) ) ?  $dt[$hkey] : "";

				if ( $type=='BaseArray' || $type=='BaseNested' || $type=='BaseObject' ) {
					$val = json_encode($data[$idx][$hkey]);

				} else if ($type=='BaseBool' ) {
					if ($dt[$hkey]  === true ) {
						$val = "1";
					} else {
						$val = "0";
					}
				}

				$row[] = $val;
			}

			$ndata[] = $row;
		}

		$rdata = $this->_data([
			'head'=>$headkeys,
			'data'=>$ndata, 
			// 'total'=>$items->total(), 
			// 'currTotal'=>$items->currTotal(),
			// 'perpage'=>$items->perpage(), 
			// 'currPage'=>$items->currPage(), 
			// 'nextPage'=>$items->nextPage(),
			// 'pages'=>$items->pages(),
			'error'=>$error, 
			// 'index'=>$this->model->indexName(false, true),
			'_sql'=>$_sql ],'数据清单');
		// echo json_encode($rdata);
		// return;
		$this->_render($rdata, 'find');
	}



	/**
	 * API 代码生成器
	 * @return [type] [description]
	 */
	function apibuilder() {

		$name =  strtolower(get_class($this->model));
		$stname = strtolower(str_replace('model', '', $name));

		$ut = new Utils;
		$apiname = (isset($_GET['apiname']) ) ? ucfirst(strtolower(trim($_GET['apiname']))) : ucfirst($stname . 'api'); // API 控制器名称
		$srcname = (isset($_GET['srcname']) ) ? ucfirst(strtolower(trim($_GET['srcname']))) : ucfirst($stname);		 // 资源名称
		$srccname = (isset($_GET['srccname']) )? $ut->unescape($_GET['srccname']) : ucfirst($stname);  // 资源中文名称
		$querykeys = (isset($_GET['querykeys'])) ? explode(',',$_GET['querykeys']) : ['_id'];  // 查询主键
		
		$sheet = $this->model->sheet();
		$columns = (isset($sheet['columns'])) ? $sheet['columns'] : [];

		$data = $this->_data([
			'columns' => $columns,
			'stname' =>  ucfirst($stname),  // 模型名称
			'apiname' => $apiname,  // API 控制器名称
			'srcname' => $srcname,  // 资源名称
			'querykeys' => $querykeys,  // 主键
			'srccname' =>$srccname,  //资源中文名

			],'API代码生成器');
		$this->_render($data, 'apibuilder');
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
				'_APP' => $this->headers['Xpmse-Appname']
			], $data);

	}



	/**
	 * 渲染器
	 * @param  [type] $data [description]
	 * @param  [type] $view [description]
	 * @return [type]       [description]
	 */
	protected function _render($data, $sharp, $return = false ) {
		$layout_file = __DIR__ .  '/../assets/scaffold/view/' . $sharp . '.tpl.html';

		if ( $return ) {
			ob_start();
		}

		@extract( $data );
		try {
			require( $layout_file );
		} catch ( Exception $e  ) {
			throw new Excp('载入模板错误('.$e->getMessage().')', 500);
		}

		if ( $return ) {
			$content = ob_get_contents();
		   	ob_end_clean();
		   	return $content;
		}
	}

}
<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Xpmse\Media as Media;
use \Xpmse\Model\Code;


/**
 * MINA 代码生成器 
 */
class minaCodemakerController extends privateController {

	function __construct() {
		parent::__construct([],['icon'=>'fa-code', 'icontype'=>'fa', 'cname'=>'开发工具']);
	}


	/**
	 * 代码生成器
	 * @return [type] [description]
	 */
	function index() {
		$this->history();
	}


	/**
	 * 扫描并更新代码
	 * @return [type] [description]
	 */
	function scan() {
		$code = new \Xpmse\Model\Code;
		$codes = $code->scan();
		$this->selectTemplate();
	}


	/**
	 * 扫描并模型清单
	 * @return [type] [description]
	 */
	function models(){

		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';

		$code = new \Xpmse\Model\Code;
		$models = $code->scanModelList();
		foreach ($models as & $mode ) {
			$k = str_replace('\\Xpmse\\Model\\', "", $mode );
			$k = str_replace('\\Model', "", $k);
			$k = implode('/',array_filter(explode('\\', $k)));
			$v = $mode;	
			$mode = ["name"=>$k, "class"=>$v];
		}
		header("Content-Type: application/json");
		Utils::out( $models );
	}


	/**
	 * 读取模型全量信息
	 * 
	 * 	 1. struct ( table & fields )
	 * 	 2. model user method
	 * 	 3. api user method
	 * 	 
	 * @return
	 */
	function modelSource() {

		$class = $_GET['class'];
		$code = new \Xpmse\Model\Code;

		$data = [
			"struct" => $code->getModelStruct( $class ),
			"model" => $code->getModelCode( $class ),
			"api" => $code->getAPICode( $class )
		];

		Utils::out( $data );
	}


	/**
	 * 数据模型方法代码
	 * @return 
	 */
	function modelCode() {

		$code = new \Xpmse\Model\Code;
		$class = $_GET['class'];
		$struct = $code->getModelCode( $class );
		Utils::out($struct);
	}


	/**
	 * 数据API方法代码
	 * @return 
	 */
	function apiCode() {
		
		$code = new \Xpmse\Model\Code;
		$class = $_GET['class'];
		$struct = $code->getAPICode( $class );
		Utils::out($struct);
	}


	/**
	 * 模型结构
	 * @return
	 */
	function modelStruct() {

		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		$class = $_GET['class'];
		$code = new \Xpmse\Model\Code;
		$struct = $code->getModelStruct( $class );
		Utils::out($struct);
	}


	/**
	 * 读取一组结构
	 * @return [type] [description]
	 */
	function modelStructs() {

		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = 'application/json';
		$classes = explode(',', $_GET['classes']);
		$resp = [];
		$code = new \Xpmse\Model\Code;
		foreach ($classes as $class) {
			$resp[$class] =  $code->getModelStruct( $class );
		}

		Utils::out($resp);
	}


	/**
	 * 项目列表
	 * @return
	 */
	function history(){

		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$code = new \Xpmse\Model\Code;
		$historys = $code->searchHistory(["perpage"=>20, "page"=>$page, "select"=>["code_id","history_id","name","process","next", "prev", "created_at", "updated_at"]]);

		$this->_crumb('开发工具', R('mina','codemaker','index') );
	    $this->_crumb('代码生成器');
	    $this->_active('mina/codemaker/index');
		$data = $this->_data([
			"_TITLE" =>"项目列表-开发工具/代码生成器", 
			"action" =>"history",
			"historys" => $historys
		]);

		
		if ( $_GET['debug'] ) {
			print_r($historys);
			return;
		}

		render( $data, 'mina/codemaker', 'index');
	}


	/**
	 * 切换到已有项目
	 * @return [type] [description]
	 */
	function edit(){
		$id = $_GET['id'];
		if ( empty($id) ) {
			throw new Excp("未知项目", 404,['id'=>$id]);
		}

		$code = new \Xpmse\Model\Code;
		$rs = $code->selectHistory( $id );
		Header('Location: ' . R('mina', 'codemaker', 'setOption', ['code_id'=>$rs['code_id']]));
	}



	/**
	 * 创建/复制/改名表单
	 * @return [type] [description]
	 */
	function create(){
		
		$id = $_GET["id"];  // copy from 
		$history = [];
		if ( !empty($id) ) {
			$code = new \Xpmse\Model\Code;
			$history = $code->getHistory($id);
		}

		$data = [
			'id'=>$id,
			'history'=>$history
		];
		render( $data, 'mina/codemaker', 'create');
	}


	/**
	 * 打包下载程序
	 * @return [type] [description]
	 */
	function download() {
		$id = $_GET["id"];  // copy from 
		if ( empty($id) ) {
			throw new Excp("未知项目", 404,['id'=>$id]);
		}
		
		$code = new \Xpmse\Model\Code;
		$code->codeDownload( $id );
	}


	/**
	 * 项目部署
	 * @return [type] [description]
	 */
	function deploy(){
		
		$id = $_GET["id"];  // copy from 
		if ( empty($id) ) {
			throw new Excp("未知项目", 404,['id'=>$id]);
		}

		$code = new \Xpmse\Model\Code;
		$history = $code->getHistory($id);

		$name = !empty( $_GET['name']) ? trim($_GET['name']) : "/README.md"; // 当前文件
		$files = $code->getCodeFiles( $history['history_id'], $name ); // 所有代码文件 Files 
		$file = $files['map'][$name];
		if ( empty($file) ) {
			throw new Excp( "代码不存在", 404, ['name'=>$name, 'files'=>$files]);
		}

		$data = [
			'id'=>$id,
			'history'=>$history,
			'file' => $file,
			"files" => $files['map']
		];
		
		render( $data, 'mina/codemaker', 'deploy');
	}


	/**
	 * 保存项目
	 * @return [type] [description]
	 */
	function saveProject() {

		$id = $_POST["id"];  // copy from 
		$history = [];
		$code = new \Xpmse\Model\Code;

		if ( !empty($id) ) {
			$history = $code->getHistory($id);
		}

		$history['name'] = $_POST['name'];
		if ( empty($_POST['rename']) ) {
			$history['history_id'] = time() . rand(100000, 999999);
			unset($history['_id']);
		}

		$hs = $code->saveHistory( $history );
		$_GET['id'] = $hs['history_id'];
		$this->edit();
	}


	/**
	 * 删除项目
	 * @return [type] [description]
	 */
	function removeProject(){
		$id = $_POST["id"];  // copy from 
		if ( empty($id) ) {
			throw new Excp("未知项目列表", 404,['id'=>$id]);
		}

		$code = new \Xpmse\Model\Code;
		$ret = $code->removeHistory($id);
		if ( $ret === false ) {
			throw new Excp("删除失败", 404,['id'=>$id]);
		}
		echo json_encode(['code'=>0, 'message'=>'删除成功']);
	}



	/**
	 * 第一步: 选择代码模板
	 */
	function selectTemplate() {

		$history_id = (isset($_GET['history_id'])) ? $_GET['history_id'] : "";
		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
	
		$code = new \Xpmse\Model\Code;
		$codes = $code->search(["select"=>["code_id","path","cname","images","summary", "org","name","version"]]);
		$history = empty( $history_id ) ?  $code->currentHistory() :  $code->getHistory($history_id);
		$code_id   = (isset($_GET['code_id'])) ? $_GET['code_id'] : $history['code_id'];

		$this->_crumb('开发工具', R('mina','codemaker','index') );
	    $this->_crumb('代码生成器');
	    $this->_active('mina/codemaker/index');

		$data = $this->_data([
			"_TITLE" =>"选择代码模板-开发工具/代码生成器", 
			"action" =>"select_template",
			"code_id" => $code_id,
			"codes" => $codes,
			"history" => $history
		]);

		render( $data, 'mina/codemaker', 'index');
	}

	/**
	 * 第二步: 设定代码母版变量
	 */
	function setOption() {

		$history_id = (isset($_GET['history_id'])) ? $_GET['history_id'] : "";
		$code_id = (isset($_GET['code_id'])) ? $_GET['code_id'] : "";
			
		$code = new \Xpmse\Model\Code;
		$history = empty( $history_id ) ?  $code->currentHistory() :  $code->getHistory($history_id);
		if ( empty($code_id) ) {
			$code_id = $history['code_id'];
		}


		// 未指定母版，跳转到母版选择页面
		if( empty($code_id) ) {
			$this->selectTemplate();
			return;
		}

		$options = $code->getOptions( $code_id );
		$page = !empty( $_GET['page']) ? trim($_GET['page']) : current( array_keys($options['pages']) ) ;
		$curr = $options['pages'][$page];

		// 从数据库中读取当前信息
		
		$process = $code->processOptions( $page, $history, $options['pages'] ); // 解析流程信息


	
		// 渲染表单
		$opts_data = empty($history['data']) ? [] : $history['data'];
		$opts_data[$page] = is_array( $opts_data[$page]) ?  $opts_data[$page] : [];
		$form = $code->renderOptionPage($curr['form'], $curr['template']['path'], $opts_data[$page], $opts_data);
		$validation = $code->getValidationJSCode($curr['form'], $curr['template']['path']);

		$this->_crumb('开发工具', R('mina','codemaker','index') );
	    $this->_crumb('代码生成器');
	    $this->_active('mina/codemaker/index');
		$data = $this->_data([
			"_TITLE" =>"设定参数-开发工具/代码生成器", 
			"action" =>"set_option",
			"code_id" => $code_id,
			"page" => $page,
			"curr" => $curr,
			"options" => $options,
			"process" => $process,
			"history" => $history,
			"form" => $form,
			"validation" =>$validation
		]);

		if ( $_GET['debug'] ) {
			echo "<!--\n";
			print_r($data);
			echo "-->\n";
			return;
		}

		render( $data, 'mina/codemaker', 'index');

	}



	/**
	 * 保存配置项数值
	 * @return
	 */
	function saveOption() {

		// print_r($_POST);

		// return;

		$history_id = (isset($_GET['history_id'])) ? $_GET['history_id'] : "";
		$code_id = (isset($_GET['code_id'])) ? $_GET['code_id'] : "";
		$page = !empty( $_GET['page']) ? trim($_GET['page']) : "";
			
		// 未指定母版，跳转到母版选择页面
		if( empty($code_id) ) {
			$this->selectTemplate();
			return;
		}

		$code = new \Xpmse\Model\Code;
		$options = $code->getOptions( $code_id );
		$page = !empty( $_GET['page']) ? trim($_GET['page']) : current( array_keys($options['pages']) ) ;

		// 从数据库中读取当前信息
		$history = empty( $history_id ) ?  $code->currentHistory() :  $code->getHistory($history_id);

		// 赋值
		if ( $history['code_id'] !== $code_id ) { // 更换code_id
			$history['data'] = [];
			$history['process'] = [];
		}

		$history['code_id'] = $code_id;
		$history['data'][$page] = $_POST;
		$history['process'][$page] = 'saved';

		// 保存
		$history = $code->saveHistory( $history );

		// 读取信息
		$process = $code->processOptions( $page, $history, $options['pages'] ); // 解析流程信息
		echo json_encode(['code'=>0, 'messages'=>"保存成功", 'history'=>$history, 'options'=>$options, 'process'=>$process]);

	}


	/**
	 * 导入配置
	 * @return [type] [description]
	 */
	function importOption() {
		$history_id = (isset($_POST['history_id'])) ? $_POST['history_id'] : "";
		$code_id = (isset($_POST['code_id'])) ? $_POST['code_id'] : "";

		$json_text = $_POST['data'];
		$data = json_decode($json_text, true );
		
		if ( $data === false ) {
			Utils::json_decode( $json_text );
			return;
		}

		$code = new \Xpmse\Model\Code;
		$options = $code->getOptions( $code_id );
		$history = empty( $history_id ) ?  $code->currentHistory() :  $code->getHistory($history_id);
		if ( empty($history['code_id']) ) {
			$history['code_id'] = $code_id;
		}

		foreach( $data as $page => $dt ) {
			$history['data'][$page] = $dt;
			if ( !empty($dt) ) {
				$history['process'][$page] = 'saved';
			}
		}

		// 保存
		$history = $code->saveHistory( $history );

		// 读取信息
		$options = $code->getOptions($history['code_id']);
		$process = $code->processOptions( $page, $history, $options['pages'] ); // 解析流程信息
		echo json_encode(['code'=>0, 'messages'=>"导入成功", 'history'=>$history, 'options'=>$options, 'process'=>$process]);
	}


	function getOption(){
		
		$history_id = (isset($_GET['id'])) ? $_GET['id'] : "";
		if( empty($history_id) ) {
			throw new Excp( "未指定项目", 404, ['name'=>$name, 'files'=>$files]);
			return;
		}

		$code = new \Xpmse\Model\Code;
		$history =  $code->getHistory($history_id);	
		$data = $history['data'];
		$code = Utils::get( $data );

		if ( $_GET['output'] ){ 
			echo htmlspecialchars($code );
		} else {
			echo $code;
		}
	}


	/**
	 * 第三步: 预览 & 下载代码
	 */
	function getCode() {
		
		$history_id = (isset($_GET['history_id'])) ? $_GET['history_id'] : "";
		$code_id = (isset($_GET['code_id'])) ? $_GET['code_id'] : "";
		$page = !empty( $_GET['page']) ? trim($_GET['page']) : "";

		$code = new \Xpmse\Model\Code;
		$history = empty( $history_id ) ?  $code->currentHistory() :  $code->getHistory($history_id);
		if ( empty($code_id) ) {
			$code_id = $history['code_id'];
		}


		$name = !empty( $_GET['name']) ? trim($_GET['name']) : "/README.md"; // 当前文件
		$files = $code->getCodeFiles( $history['history_id'], $name ); // 所有代码文件 Files 
		$file = $files['map'][$name];
		if ( empty($file) ) {
			throw new Excp( "代码不存在", 404, ['name'=>$name, 'files'=>$files]);
		}


		$content = $code->codeGetContent( $file['path'], $history['data'], $file, $history );


		$this->_crumb('开发工具', R('mina','codemaker','index') );
	    $this->_crumb('代码生成器');
	    $this->_active('mina/codemaker/index');

		$data = $this->_data([
			"_TITLE"=>"下载代码-开发工具/代码生成器", 
			'action'=>'get_code',
			"history" => $history,
			"code_id" =>$code_id,
			"page" => $page,
			"files" => $files['data'],
			"file" => $file,
			"content" => $content
		]);

		render( $data, 'mina/codemaker', 'index');
	}


	/**
	 * 代码模板详情 ( 视频 & 截图 )
	 * @return [type] [description]
	 */
	function templateDetail() {
		render( $data, 'mina/codemaker', 'template_detail');
	}


    /**
     * 导出 JSON 文档
     */
    function export() {
        $id = $_GET["id"];
        $code = new \Xpmse\Model\Code;
        $data = $code->export( $id );

        header("Content-Disposition:attachment;filename = {$data["name"]}.json" );
        Utils::out( $data );
    }

    /**
	 * 上传 JSON 文档表单
	 * @return [type] [description]
	 */
	function import(){
		$data = [];
		render( $data, 'mina/codemaker', 'import');
    }
    
    /**
     * 导入JOSN 文档
     */
    function doImport(){
        
        $file = $_POST["jsonfile"];
        $media = new Media();
        $json_text  = $media->blob($file);

        if ( empty($json_text) ) {
            throw New Excp("无法读取文档", 404, ["jsonfile"=>$file]);
        }

        $json_data = json_decode($json_text, true );
        if ( $json_data === false ) {
            throw New Excp("文件格式错误", 500, ["json_text"=>$json_text]);
        }

        $tpl = $json_data["templete"];
        unset( $json_data["templete"]);

        $code = new \Xpmse\Model\Code;
        $codeRs = $code->getBy("slug", $tpl["slug"] );
        if ( empty($codeRs) ) {
            throw New Excp("程序母版不存在({$codeRs['cname']})", 404, ["jsonfile"=>$file, "tpl"=>$tpl]);
        }

        $json_data["code_id"] = $codeRs["code_id"];
        $json_data["updated_at"] = date("Y-m-d H:i:s");
        $hs = $code->saveHistory( $json_data );
		$_GET['id'] = $hs['history_id'];
		$this->edit();
        
    }

	/**
	 * 上传代码模板 
	 * 
	 * @todo 
	 * 	  1. 代码模板制作文档
	 * 	  2. 应用商店可以售卖代码模板
	 * 	  3. 生成好的模板可以下载
	 * 
	 * @return
	 * 
	 */
	function uploadTemplate() {
	}


	

}
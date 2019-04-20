<?php 
/**
 * MINA Pages 模板编译器 
 * 
 * @package      \Mina\Template
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 * @example
 * 
 * <?php
 *  use \Mina\Template\Compiler;
 *  use \Mina\Template\DataCompiler;
 *
 * 	class MyDataCompiler extends DataCompiler {
 *  	
 * 	 	function __construct() {
 * 		 	parent::__construct(["home"=>"http://apps.minapages.com/1"]);
 * 	   	}
 * 
 *	  	function compile( $json_data ) {
 * 		 	return '
 * 		 			$data = ["bar"=>"foo"];
 * 		   		';
 *	  	}
 * 	}
 *  
 * 	$cp = new Compiler();
 * 	
 * 	$phpcode = $cp->load(__DIR__ . "/assets/detail.page",  __DIR__ . "/assets/detail.json")
 * 			      ->toPHP( 'MyDataCompiler' );
 * 	echo $phpcode;
 * 	...
 *  
 */

namespace Mina\Template;

use Mina\Template\HtmlParser;
use Mina\Template\XmlParser;
use Mina\Template\TextParser;
use Mina\Template\ComponentParser;
use Mina\Template\DataParser;
use Mina\Template\DataCompiler;


use \Exception;

class Compiler  {
	
	private  $page;
	private  $code;

	function __construct($options = []) {
		$this->options = $options;
		$this->hp = new HtmlParser;
		$this->xp = new XmlParser; // xml 解析器
		$this->tp = new TextParser; // Text 解析器
		$this->cp = new ComponentParser; // 组件解析器
		$this->dp = new DataParser;
	}

	function load( $page_file, $json_file, $options =[] ) {

		if ( !file_exists($page_file) ) {
			throw new Exception("文件不存在 ($page_file) ", 404 );
		}

		if ( !is_readable($page_file) ) {
			throw new Exception("文件无法访问 ($page_file)", 503 );
		}

		if ( !file_exists($json_file) ) {
			throw new Exception("文件不存在 ($json_file) ", 404 );
		}

		if ( !is_readable($json_file) ) {
			throw new Exception("文件无法访问 ($json_file)", 503 );
		}

		$options['script'] = !empty($options['script']) ? $options['script'] : [];
		$options['assets'] = !empty($options['assets']) ? $options['assets'] : [];

		$page = array_merge([
			"page" => file_get_contents($page_file),
			"json" => file_get_contents($json_file)
		], $options );



		return $this->loadPage( $page );
	}

	/**
	 * 载入Page 
	 * @param  Array $page [description]
	 * @return $this
	 */
	function loadPage( $page = [] ) {

		$this->page = array_merge_recursive( $this->options, $page );

		if ( !isset($this->page['marker']) ) { // 默认添加发行注记
			$this->page['marker'] = true;
		}

		$this->page['script'] = is_string($this->page['script']) ?  [$this->page['script']] : $this->page['script'];

		if ( empty($this->page['script'])) {
			$this->page['script'] = null;
		}

		$json = $this->page['json'];
		if ( is_string($json) ) {
			$json = json_decode($json, true);
		}

		// 支持不同解析器
		$type = empty($json['type']) ? 'html' : $json['type']; 
		switch ($type) {
			case 'html':
				$this->hp->loadHTML( $this->page['page'] );
				break;
			case 'xml':
				$this->hp = $this->xp;
				$this->hp->loadXml( $this->page['page'] );

				break;
			case 'text':
				$this->hp = $this->tp;
				$this->hp->loadText( $this->page['page'] );
				break;
			case 'component':
				$this->hp = $this->cp;
				$this->hp->loadComponent( $this->page['page'] );
				break;

			default:
				$this->hp->loadHTML( $this->page['page'] );
				break;
		}


		// Load Data
		$this->dp->loadJSON( $this->page['json'] );
		
		return $this;
	} 


	/**
	 * 编译成 PHP 代码
	 * @param  [type] $compiler [description]
	 * @return [type]           [description]
	 */
	function toPHP( $compiler = null  ) {
		
		$data_code = $this->dp->toPHP( $compiler );

		// 加载脚本
		if ( is_array($this->page['script']) ) {  
			foreach ($this->page['script'] as $script_code ) {
				$this->hp->insertScript( $script_code );
			}
		}

		// 加载 CSS & JS 
		if ( is_array($this->page['assets']) ) {
			$this->hp->insertAssets($this->page['assets'] );
		}

		// 加载结尾插入水印
		if ( $this->page['marker'] === true ) {
			$this->hp->insertWaterMarker();
		}

		$html_code = trim($this->hp->toPHP());
		$this->code = $phpcode = "<?php\nerror_reporting(E_ALL^E_NOTICE^E_WARNING);\n$data_code \n?>\n$html_code";

		return $phpcode;
	}


	/**
	 * 运行 php Code 生成HTML代码
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	function toHTML( $query=[], $data=[] ) {

		if ( empty( $this->code ) ) {
			throw new Exception("尚未编译,请先调用 toPHP() 编译", 404 );
		}
		
		$_GET = $query;
		$_POST = $data;
		$_REQUEST = array_merge( $_GET, $_POST );

		ob_start();

		eval("?> $this->code");
		$content = ob_get_contents();
        ob_clean();
        return $content;
	}
}











<?php 
/**
 * MINA Delta Node 
 * 
 * @package	  \Mina\Delta
 * @author	   天人合一 <https://github.com/trheyi>
 * @copyright	Xpmse.com
 * 
 * @example
 * 
 */

namespace Mina\Delta;

// const _FORMAT = [
// 	"embed" => [
// 		"image" => "_image",
// 		"formula" => "_formula"
// 	],
// 	"inline" =>[
// 		"italic" => "_italic",
// 		"bold" => "_bold",
// 		"code" => "_code",
// 		"link" => "_link",
// 		"size" => "_size",
// 		"color" => "_color",
// 		"background" => "_background"
// 	],
// 	"block"  => [
// 		"align" => "_align",
// 		"header" => "_header",
// 		"blockquote" => "_blockquote",
// 		"code-block" => "_code_block",
// 		"list" =>[
// 			"group" => "_list_group",
// 			"line" => "_list_line"
// 		]
// 	]
// ];


class Node {
	
	static protected $lastid=0;
	static protected  $images=[];
	static protected  $videos=[];
	static protected  $files=[];

	public  $open = null;
	public  $close = null;
	public  $format = null;
	public  $text = null;
	public  $children = [];
	public  $parent = null;


	static public $_FORMAT = [
		"embed" => [
			"image" => "_image",
			"cimage" => "_cimage",
			"cvideo" => "_cvideo",
			"cfile" => "_cfile",
			"formula" => "_formula"
		],
		"inline" =>[
			"italic" => "_italic",
			"bold" => "_bold",
			"code" => "_code",
			"link" => "_link",
			"size" => "_size",
			"color" => "_color",
			"background" => "_background"
		],
		"block"  => [
			"indent" => "_indent",
			"textindent" => "_textindent",
			"align" => "_align",
			"header" => "_header",
			"blockquote" => "_blockquote",
			"code-block" => "_code_block",
			"list" =>[
				"group" => "_list_group",
				"line" => "_list_line"
			]
		]
	];

	function __construct( $data = null ) {
		
		self::$lastid = ++self::$lastid;
		$this->children = [];
		if ( is_array($data) ){
			$this->open = $data[0];
			$this->close = $data[1];
		} else if ( is_string($data) ) {
			$this->text = $data;
		}
	}

	function fmt( $fmt = null ) {
		if ( empty($fmt) ) {
			return $this->format;
		}
		$this->format = $fmt;
		return $this->format;
	}

	/**
	 * 建议一个空行节点
	 * @param  [type] $newPG 是否新增段落，默认新增
	 * @return [type]        [description]
	 */
	public function newLine( $newPG=true ) {
		if ( $newPG === true ) {
			return new self(["", "\n"]);
		}
		return new self(["", "\n"]);
	}

	static public function lastid(){
		return self::$lastid;
	}


	static public function images(){
		return self::$images;
	}

	static public function videos(){
		return self::$videos;
	}

	static public function files(){
		return self::$files;
	}

	static public function reset() {
		self::$lastid = 0;
		self::$images = [];


	}


	/**
	 * 追加节点
	 * @param  Node|string|array $node 节点数据
	 * @return $this;
	 */
	function append( $node ) {

		if ( !is_a($node, '\Mina\Delta\Node') ) {
			$node = new \Mina\Delta\Node($node);
		}

		if ( $node->parent ) { // 清空现有节点数据
			$pk = array_search($node, $node->parent->children);
			if  ( $pk !== false ) {
				array_splice($node->parent->children, $pk, 1);
			}
		}

	    $node->parent = $this;
	    array_push( $this->children, $node );
	    return $this;
	}


	function filter( $line ) {
		return $line;
	}
	

	function render() {

		$text = '';

		if ( $this->open !== null ) {
			$text = $text . $this->open . "\n";
		}

		if ( $this->text !== null ) {
			$this->text = trim($this->text);
			if (!empty($this->text) ) {
				$text = $text . $this->text . "\n";
			}
		}

		// 处理子节点
		foreach ($this->children as $child ) {
			$text = $text . "\t" . $child->render() ;
		}

		if ( $this->close !== null ) {
			$text = $text . $this->close . "\n";
		}


		return $text;
	}


	function format( $method ) {
		$args = func_get_args();
		unset($args[0]);
		if ( method_exists($this, $method) ) {
			return $this->$method( ...$args );
		}
		return false;
	}

}
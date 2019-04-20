<?php 
/**
 * 解析page.json, 将取值逻辑编译成PHP程序
 * 
 * @package      \Mina\Template
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 * @example
 * <?php
 *  use \Mina\Template\DataParser;
 *  
 * 	$hp = new DataParser();
 * 	$phpcode = $hp->load(__DIR__ . "/assets/detail.json")
 * 			->toPHP();
 * 	echo $phpcode;
 * 	...
 *  
 */
namespace Mina\Template;

use \Mina\Template\DataCompiler; 
use Exception;

class DataParser {
	
	private  $compiler;
	private $options ;
	private $json_data; 

	function __construct( $options = [] ) {
		
		$this->options = $options;
	}

	/**
	 * 读取配置 
	 * @param  string $json_file 配置文件
	 * @return $this
	 */
	function load( $json_file  ) {

		if ( !file_exists($json_file) ) {
			throw new Exception("文件不存在 ($json_file) ", 404 );
		}

		if ( !is_readable($json_file) ) {
			throw new Exception("文件无法访问 ($json_file)", 503 );
		}

		$json_text = file_get_contents($json_file);
		if ( $json_text === false ){
			throw new Exception("打开文件失败 ($json_file)", 500 );
		}

		return $this->loadJSON( $json_text );
	}

	/**
	 * 读取配置
	 * @param  string $json_text JSON 字符串
	 * @return $this
	 */
	function loadJSON( $json_text ) {

		if ( empty($json_text) ) {
			throw new Exception("未传入任何 JSON 数据", 400 );
		}

		$json_data = self::json_decode( $json_text );
		$this->json_data = $json_data;
		return $this;
	}


	/**
	 * 输出为 JSON 字符串
	 * @param  boolean $pretty 是否美化输出，默认 true
	 * @return string JSON字符串
	 */
	function toJSON( $pretty = true ) {
		
		if ( $pretty ) {
			return  json_encode($this->json_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		return  json_encode($this->json_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}


	/**
	 * 输出为编译后PHP代码字符串
	 * @param  \Mina\Template\DataCompiler $compiler 编译器 ( 默认为 DataCompiler )
	 * @return string PHP代码字符串
	 */
	function toPHP( $compiler = null ) {
		
		if ( !empty($compiler) ) {
			$this->compiler = $compiler;
		}

		if ( !method_exists($this->compiler(), 'setOptions') ) {
			throw new Exception( "指定编译器错误, 没有 setOptions() 方法" , 500 );
		}

		if ( !method_exists($this->compiler, 'compile') ) {
			throw new Exception( "指定编译器错误, 没有 compile() 方法" , 500 );
		}

		$this->compiler->setOptions( $this->options );
		$code = $this->compiler->compile( $this->json_data );
		return $code;
	}


	/**
	 * 读取当前编译器实例
	 * @return [type] [description]
	 */
	function compiler() {

		if (empty( $this->compiler) ) {
			$this->compiler = new DataCompiler( $this->options );
		}

		return $this->compiler;
	}

	static public function json_decode( $json,  $flag = \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC ) {
        
        if ( file_exists($json) ) {
            $json = file_get_contents( $json );
        }

        $parser = new \Seld\JsonLint\JsonParser();
        
        $e = $parser->lint($json, $flag );

        if ( $e != null ) {
            throw new Exception( 'JSON解析错误 (' . $e->getMessage() . ')' , 400);
        }

        return $parser->parse($json, $flag);
    }
}


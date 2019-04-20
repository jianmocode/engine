<?php 
/**
 * 默认数据编译器，将 page.json 编译成 PHP代码
 * 
 * @package      \Mina\Template
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Template;

class DataCompiler {

	protected $options;

	function __construct( $options = [] ) {
		$this->options = $options;
	}

	final function setOptions( $options, $reset = false ) {

		if ( $reset ) { 
			$this->options = $options;
			return $this;
		}

		$this->options = array_merge($this->options, $options);
		return $this;
	}

	final function getOptions() {
		return $this->options;
	}


	function compile( $json_data ) {
		return "\t" .'$data = ["foo"=>"bar"];' . "\n\t@extract(\$data);" ;
	}
}
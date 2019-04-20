<?php 
/**
 * MINA Pages Delta to html 
 * 
 * @package	  \Mina\Delta
 * @author	   天人合一 <https://github.com/trheyi>
 * @copyright	Xpmse.com
 * 
 * @example
 * 
 * <?php
 * 		
 *  
 */

namespace Mina\Delta;

use Mina\Delta\Node; 
use \Exception;


class Html extends Node {

	function __construct( $data = null ) {
		parent::__construct( $data );	
	}

	public function newLine( $newPG=true ) {

		if ( $newPG === true ) {
			return new self(["<p>\n", "\n</p>"]);
		}
		return new self(["", "\n"]);
	}



	// 格式化相关工具
	
	function filter( $line ) {

		// echo "filter = {$this->open}  $line \n";


		return htmlspecialchars( $line );
	}


	private function addStyle( $tag, $key, $value ) {

		$tag = trim($tag);
		$styles = $this->getStyle($tag);
		$styles[$key] = $value;
		$arrs = [];
		foreach ($styles as $k => $v) {
			$arrs[] = "$k:$v";
		}
		$newStyles = implode(';', $arrs);
		$reg = "style=[\"\']{1}[ ]*(.+)[\"\']{1}";
		$tag = preg_replace("/$reg/", '', $tag);
		$tag = preg_replace('/^(<[a-zA-Z]+)[ ]*/', "$1 style=\"{$newStyles}\"", $tag);

		return $tag;
	}

	private function addStyles( $tag, array $addStyles ) {

		$tag = trim($tag);
		$styles = $this->getStyle($tag);
		$styles = array_merge($styles, $addStyles);
		$arrs = [];
		foreach ($styles as $k => $v) {
			$arrs[] = "$k:$v";
		}
		$newStyles = implode(';', $arrs);
		$reg = "style=[\"\']{1}[ ]*(.+)[\"\']{1}";
		$tag = preg_replace("/$reg/", '', $tag);
		$tag = preg_replace('/^(<[a-zA-Z]+)[ ]*/', "$1 style=\"{$newStyles}\"", $tag);

		return $tag;
	}



	private function replaceTag($tag) {

		if ( empty( $this->open ) ) {
			return;
		}

		$reg = "/^<([a-zA-Z]+)[ ]+/";
		if ( preg_match($reg, $this->open, $match) ) {
			$oldTag = $match[1];
			$this->open = str_replace("<{$oldTag}","<{$tag}", $this->open);
			$this->close = "</{$tag}>";
		}
	}


	private function getStyle( $tag ){
		$reg = "style=[\"\']{1}[ ]*(.+)[\"\']{1}";
		$styles = [];
		if ( preg_match("/$reg/", $tag, $match) ) {
			$arrs = explode(';', $match[1]);
			foreach ($arrs as $styl ) {
				$kv = explode(':', $styl);
				if ( isset($kv[0]) && isset($kv[1]) ) {
					$styles[$kv[0]] = $kv[1];
				}
			}
		}

		return $styles;
	}

	private function isInline() {
		if ( strpos( $this->open, '<span') !== false ) {
			return true;
		} else if ( strpos( $this->open, '<strong') !== false ) {
			return true;
		} else if ( strpos( $this->open, '<code') !== false ) {
			return true;
		} else if ( strpos( $this->open, '<a') !== false ) {
			return true;
		}

		return false;
	}



	// == Embed ====================== 
	protected  function _image( $src, $attrs ) {

		$attrs['src'] = $src;
		array_push( self::$images, $attrs);
		
		$this->append( "<img src=\"{$src}\" width=\"{$attrs['width']}\" height=\"{$attrs['height']}\" >");
	}

	protected  function _cimage( $attrs, $v ) {

		array_push( self::$images, $attrs); 
		$allowAttrs  = ['width', 'height', 'src'];
		$attrstring = "";
		foreach ($attrs as $key => $value) {
			if (in_array($key, $allowAttrs) ) {
				$name = $key ;
			}  else {
				$name = "data-{$key}";
			}

			if ( $key == 'url') {
				$attrstring .= " src=\"{$value}\" ";
			}

			$attrstring .= "  {$name}=\"{$value}\" ";
		}
		
		$this->append( "<img $attrstring class=\"mp-cimage\" >");
	}


	protected  function _cvideo( $attrs, $v ) {

		array_push( self::$videos, $attrs); 
		$allowAttrs  = ['width', 'height'];
		$attrstring = ""; $code = "";
		foreach ($attrs as $key => $value) {
			if (in_array($key, $allowAttrs) ) {
				$name = $key ;
			}  else {
				$name = "data-{$key}";
			}

			if ( !is_array($value) ) {
				$attrstring .= "  {$name}=\"".htmlspecialchars($value)."\" ";
			}
		}

		$this->append( "<div $attrstring  style=\"width:{$attrs['width']}px;height:{$attrs['height']}px;\" class=\"mp-cvideo\" >{$attrs['code']} </div>");
	}


	protected  function _cfile( $attrs, $v ) {

		array_push( self::$files, $attrs); 
		$attrstring = "";
		foreach ($attrs as $key => $value) {
			$name = "data-{$key}";
			$attrstring .= "  {$name}=\"{$value}\" ";
		}
		
		$this->append( "
			<a href=\"{$attrs['url']}\" target=\"_blank\" $attrstring  class=\"mp-file\" 
			   style=\"display:inline-block;padding:.5em 1em .5em 1em;border:1px solid #e9e9e9; background:#f5f5f5;\">
				<img src=\"{$attrs['small']}\" width=\"48\" style=\"vertical-align:middle\" > 
				{$attrs['title']}
			</a>
		");
	}


	protected function _formula( $v, $attrs ) {
		$this->append('<span class="formula" data-value="'.$v.'"></span>');
	}

	// == Inline ====================== 
	
	protected function _size( $size ) {
		if ( $this->isInline() === false ) {
			return  ["<span style=\"font-size:{$size}\" >", "</span>"];
		} else {
			if ( $this->open != null){
				$this->open = $this->addStyle($this->open, 'size', $size);
			}
		}
	}

	protected function _color( $color) {
	    // echo " _color: {$this->open}".	var_dump(strpos( $this->open, '<span')) . "\n";

		if ( $this->isInline() === false ) {
			return  ["<span style=\"color:{$color}\" >", "</span>"];
		} else {
			if ( $this->open != null ){
				$this->open = $this->addStyle($this->open, 'color', $color);
			}
		}
	}

	protected function _background( $background) {

		if ( $this->isInline() === false ) {
			return  ["<span style=\"background:{$background}\" >", "</span>"];
		} else {
			if ( $this->open != null ){
				$this->open = $this->addStyle($this->open, 'background', $background);
			}
		}
		// return  ["<span style=\"background:{$background}\" >", "</span>"];
	}

	protected  function _italic() {

		if ( $this->isInline() === false ) {
			return ['<i>', '</i>'];
		} else {
			$this->replaceTag('i');
		}


		// return ['<i>', '</i>' ];
	}

	protected function _bold() {

		if ( $this->isInline() === false ) {
			return ['<strong>', '</strong>'];
		} else {
			$this->replaceTag('strong');
		}

		// return ['<strong>', '</strong>'];
	}

	protected function _code() {
		if ( $this->isInline() === false ) {
			return ['<code>', '</code>'];
		} else {
			$this->replaceTag('code');
		}

		// return ['<code>', '</code>'];
	}

	protected function _link( $href ){
		if ( $this->isInline() === false ) {
			return ["<a href=\"{$href}\" >", "</a>"];
			
		} else {
			 // $this->open  =  str_replace('<span', "<a href=\"{$href}\" ", $this->open );
			 $this->replaceTag("a href=\"{$href}\" ");
			 // $this->close = '</a>';
		}
	}


	// == block  ======================
	protected function _header( $line, $h ) {
		$this->open = "<h{$h}>";
		$this->close = "</h{$h}>";
	}

	protected function _align( $line, $type ) {
	
		if ( $this->open != null ){
			$this->open = $this->addStyle($this->open, 'text-align', $type);
		}
	}

	protected function _indent( $line, $value ) {
	
		if ( $this->open != null ){
			$this->open = $this->addStyle($this->open, 'padding-left', $value. 'em');
		}
	}

	protected function _textindent( $line, $value ) {
	
		if ( $this->open != null ){
			$this->open = $this->addStyle($this->open, 'text-indent', $value. 'em');
		}
	}

	
	

	protected function _blockquote( $quote ) { // 暂时只支持2级引用
		$styles = [];
		if ( $this->open != null ){
			$styles = $this->getStyle( $this->open );
			if ( $this->parent != null && $this->parent->open != null) {
				$styles =  array_merge($styles, $this->getStyle( $this->parent->open ));
			}
		}

		$this->open = $this->addStyles("<blockquote>", $styles);
		$this->close = "</blockquote>";
	}

	protected function _code_block( $codeblock ) {
		$this->open = "<code>";
		$this->close = "</code>";
	}

	protected function _list_group( $type ) {
		if ($type == 'ordered' ) {
			$this->open = "<ol>";
			$this->close = "</ol>";
			return $this;
		}

		$this->open = "<ul>";
		$this->close = "</ul>";
		return $this;
	}

	protected function _list_line( $line, $av, $group = null ) {
		// echo "li : {$line} {$av} {$this->text} \n";
		$this->open = "<li>";
		$this->close = "</li>";
		$this->text = $line;
		$group['el']->append($this);
	}
}
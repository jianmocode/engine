<?php 
/**
 * MINA Pages Wxapp to Delta 
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


use \Masterminds\HTML5;
use \Mina\Cache; 
use \Exception;

class Wxapp  {

	private $html_text;
	private $html5;
	private $dom;
	private $wxnodes = [];
	private $images = [];
	private $videos;
	private $audios;

	function __construct( $options = [] ) {
		$this->options = array_merge([
			"preserveWhiteSpace" => false,
			"formatOutput" => true
		], $options);

		$this->html5 = new HTML5( $this->options) ;
	}


	function importHtml( $html_text ) {

		$html = str_replace("\r", "\n", $html_text);
		$this->dom = $node = $this->html5->loadHTML($html_text);
		// $this->dom = $this->html5->loadHTML( "<p class='hello'> HELLO </p> <p>World</p>");
		$this->wxnodes = $wxNodes = $this->getChildren( $this->dom );

		if ( count($wxNodes) == 1 ) {
			$wxNodes = current($wxNodes);
			$wxNodes = empty($wxNodes['children']) ?  [] : $wxNodes['children'];
		}

		$this->wxnodes = $wxNodes;
		return $wxNodes;
	}


	function saveImageNode( $node, & $attrs ) {

		$attrs['width'] = !empty($attrs['width']) ? $attrs['width'] : 1;
		$attrs['height'] = !empty($attrs['height']) ? $attrs['height'] : 1;
		if ( intval($attrs['height']) == 0 ) {
			$attrs['height'] = 1;
		}
		$attrs['src'] = !empty($attrs['src']) ? $attrs['src'] : "";
		$src = !empty($attrs['data-src']) ? $attrs['data-src'] : $attrs['src'];
		$path = !empty($attrs['data-path']) ? $attrs['data-path'] : '';
		$size = !empty($attrs['data-s']) ? $attrs['data-s'] : $attrs['width'] . ",". $attrs['height'];
		$width = !empty($attrs['data-w']) ? $attrs['data-w'] : $attrs['width'];
		$height = !empty($attrs['data-h']) ? $attrs['data-h'] : $attrs['height'];
		$radio = !empty($attrs['data-ratio']) ? $attrs['data-ratio'] : $attrs['width']/$attrs['height'];
		$type = !empty($attrs['data-type']) ? $attrs['data-type'] : 'png';

		$height = $radio * $width;

		// if ( $width == 1 ) {
		// 	$width = null;
		// 	$height = null;

		// 	// $width = 1 * 100;
		// 	// $width = $width . '%';
		// 	// $height = $height . '%';
		// }

		if ( $width == 'null' || $width == 1  || $width == null ) {
			$attrs['width'] = $width = "100%";
			$attrs['height'] =  $height = "auto";
		}


		$img = [
			"height"=>$height,
			"width" => $width,
			"url" => $src,
			"origin" => $src,
			"path"=> $path,
			"src" => $src,
			"data-src" => $src,
			"data-type" => $type,
			"data-s" => $size,
			"data-ratio"=> $radio,
		];

		// Fix 图片呈现越界
		$attrs['style'] = "max-width:100%;height:auto;";

		array_push($this->images, $img);
	}



	function getChildren( $node ) {
		
		if ( $node === false || $node == null ) {
			return [];
		}

		if ( $node->hasChildNodes() ) {
			$children = [];
			foreach( $node->childNodes as $child ) {
				$n = [];
				if ( XML_ELEMENT_NODE === $child->nodeType ) { 
					$n['name'] = $child->tagName;
					$n['type'] = 'node';
					foreach ($child->attributes as $attr) { 
						$n['attrs'][$attr->name] = $attr->value;
					}

					// 图片
					if ( $child->tagName == 'img') {
						$this->saveImageNode( $child, $n['attrs']);
					}

					$n['children'] = $this->getChildren( $child );
					array_push($children, $n);


					

				} else if ( XML_TEXT_NODE === $child->nodeType ) {
					$n['type'] = 'text';
					$n['text'] = $child->nodeValue;
					array_push($children, $n);
				}
			}

			return $children;
		}

		return [];

	}


	/**
	 * 遍历子节点
	 * @param  dom $node 父节点
	 * @param  mix $resp 用于存储回调函数返回值 
	 * @param  function  $fn 处理函数  function( $node, $depth, & $resp ){} 
	 * @param  integer $depth 当前节点深度，默认为0
	 * @return null
	 */
	static function scan(  $node, & $resp,  $fn, $depth=0 ) {

		if ( $node === false || $node == null ) {
			return ;
		}

		$depth ++;
		if ( $node->hasChildNodes() ) {

			for( $i=0; $i<$node->childNodes->length; $i++ ) {
				
				$child = $node->childNodes->item($i);
				$break = null;

				// @ See http://php.net/manual/en/dom.constants.php
				if ( XML_ELEMENT_NODE === $child->nodeType ) {  // DOMElement
					$break = $fn( $child, $depth,  $resp, $node );
					if ( $break  !== true  ) {
						self::scan( $child, $resp, $fn, $depth);
					}

				} else if ( XML_TEXT_NODE === $child->nodeType ) { // DOMText 
					$break = $fn( $child, $depth,  $resp, $node );
				}
			}
		}
	}


	function images() {
		return $this->images;
	}

	function videos(){
		return $this->videos;
	}
}
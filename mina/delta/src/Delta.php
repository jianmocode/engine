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


use \Masterminds\HTML5;
use \Exception;



class Delta {

	private $html5;
	private $dom;
	private $delta;
	private $images = [];
	private $videos = [];
	private $files  = [];


	static public $_FORMAT_MAP = [

		"embed" => [
			"image" => "_image",
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


	/**
	 * 文章正文允许的标签
	 * @var [type]
	 */
	static public $_ALLOWTAGS = [
		"a" => ["class", "style", "href"],
		"abbr" => ["class", "style"],
		"b" => ["class", "style"],
		"blockquote" => ["class", "style"],
		"br" => ["class", "style"],
		"code" => ["class", "style"],
		"col" => ["class", "style", 'span', 'width'],
		"colgroup" => ["class", "style", 'span', 'width'],
		"dd" => ["class", "style"],
		"del" => ["class", "style"],
		"div" => ["class", "style"],
		"dl" => ["class", "style"],
		"dt"  => ["class", "style"],
		"em" => ["class", "style"],
		"fieldset" => ["class", "style"],
		"h1" => ["class", "style"],
		"h2" => ["class", "style"],
		"h3" => ["class", "style"],
		"h4" => ["class", "style"],
		"h5" => ["class", "style"],
		"h6" => ["class", "style"],
		"hr" => ["class", "style"],
		"i" => ["class", "style"],
		"img" => ["class", "style", "alt", "src", "height", "width","data-s","data-type","data-src","data-ratio","data-w", "data-path"],
		"ins" => ["class", "style"],
		"label" => ["class", "style"],
		"legend" => ["class", "style"],
		"li" => ["class", "style"],
		"ol" => ["class", "style", "start", "type"],
		"p" => ["class", "style"],
		"section" => ["class", "style"],
		"q" => ["class", "style"],
		"span" => ["class", "style"],
		"strong" => ["class", "style"],
		"sub" => ["class", "style"],
		"sup" => ["class", "style"],
		"table" => ["class", "style", "width"],
		"tbody" => ["class", "style"],
		"td" => ["class", "style", "colspan", "height","rowspan","width"],
		"tfoot" => ["class", "style"],
		"th" => ["class", "style", "colspan", "height","rowspan","width"],
		"thead" => ["class", "style"],
		"tr"=> ["class", "style"],
		"ul"=>["class", "style"]
	];


	function __construct( $options = [] ) {
		$this->options = array_merge([
			"preserveWhiteSpace" => false,
			"formatOutput" => true
		], $options);

		$this->html5 = new HTML5( $this->options) ;
	}


	function importHtml( $html ) {

		$html = str_replace("\r", "\n", $html);
		$this->dom = $this->html5->loadHTML($html);
		// $this->dom = $this->html5->loadHTML( '<html>' .$html ."</html>");
		$ops = [];

		self::scan( $this->dom, $tree, function( $node, $depth ) use ( & $ops ) {

			$op =  $this->nodeToDelta( $node );
			$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;

			if ( isset($op['ops']) && is_array($op['ops']) ) {
				$op = $op['ops'];
			}

			if ( !empty($op)  ) {
				$ops = array_merge($ops, $op);
			}

			// 在函数中处理子节点
			if ( $break == true  ) {
				return true;
			}

		});
		
		return ["ops"=>$ops];

	}


	function nodeToDelta( $node, $newline="\n", $group=null ) {

		if ( XML_ELEMENT_NODE == $node->nodeType ) { 

			if ( !isset(self::$_ALLOWTAGS[$node->tagName]) ) {
				return null;
			}

			$allowAttrs = self::$_ALLOWTAGS[$node->tagName]; $attrs = [];
			foreach ($node->attributes as $attr) { 
				
				if ( !in_array($attr->name, $allowAttrs) ) {
					continue;
				}
				$attrs[$attr->name] = $attr->value;
			}

			

			// 处理段落
			switch ($node->tagName) {
				case 'section':
					return $this->_pToDelta($node, $attrs, $this->realHasChildNodes($node), $newline );
					break;
				case 'div':
					return  $this->_pToDelta($node, $attrs, $this->realHasChildNodes($node), $newline );
					break;
				case 'p':
					return $this->_pToDelta($node, $attrs, $this->realHasChildNodes($node), $newline);
					break;
				case 'blockquote':
					return $this->_blockquoteToDelta($node, $attrs, $this->realHasChildNodes($node), $newline );
				case 'ol':
					return $this->_olToDelta($node, $attrs, $this->realHasChildNodes($node), $newline );
					break;
				case 'ul':
					return $this->_ulToDelta($node, $attrs, $this->realHasChildNodes($node), $newline );
					break;
				case 'li':
					return $this->_liToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, $group );
					break;
				case 'span':
					return $this->_spanToDelta($node, $attrs, $this->realHasChildNodes($node) );
					break;
				case 'strong':
					return $this->_strongToDelta($node, $attrs, $this->realHasChildNodes($node) );
					break;
				case 'i':
					return $this->_italicToDelta($node, $attrs, $this->realHasChildNodes($node) );
					break;
				case 'img':
					return $this->_imageToDelta($node, $attrs, $this->realHasChildNodes($node) );
					break;
				case 'em':
					break;
				case 'br':
					return $this->_brToDelta($node, $attrs, $newline );
					break;
				case 'code':
					break;
				case 'h1':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 1);
					break;
				case 'h2':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 2);
					break;
				case 'h3':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 3);
					break;
				case 'h4':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 4);
					break;
				case 'h5':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 5);
					break;
				case 'h6':
					return $this->_headerToDelta($node, $attrs, $this->realHasChildNodes($node), $newline, 6);
					break;	
				default:
					# code...
					break;
			}

		} else if (  XML_TEXT_NODE === $node->nodeType ) {
			if ( trim($node->nodeValue) == "") {
				return;
			}

			if ( $newline == "" ) {
				$node->nodeValue = trim($node->nodeValue);
			}
			return [["insert"=>$node->nodeValue]];
		}
	}


	function realHasChildNodes( $node ) {

		return $node->hasChildNodes();

		if ( $node->hasChildNodes() == false) return false;
		foreach ( $node->childNodes as $c) {
			if ( XML_ELEMENT_NODE == $c->nodeType ) {
				return true;
			}
		}

		return false;
	}


	function _pToDelta( $node, $attrs, $hasChildNodes, $newline="\n" ) {

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$st = $this->getStyles($attrs['style']);
		if (isset($st['display']) &&  $st['display'] == 'inline-block' ) {
			$resp =  $this->_spanToDelta( $node, $attrs, $hasChildNodes, "" );
			return $resp;
		}

		$ops = [];
		// 遍历 Inline Nodes 
		if ( $hasChildNodes === true ) {

			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {

				$op = $this->nodeToDelta($node,  "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}

			});

			if ( $newline == "\n" ) {
				$ops =  array_merge($ops,[["insert"=>"\n"]]);
			} 

		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			if ( !empty($text) && substr($text,0,1) != "\n") {
				$text =  "\n". $text;
			}

			$ops  = [["insert"=>$text]];
		}

		$ops = $this->applyStyle($attrs['style'], $ops,[], $newline);
		return ['break'=>true, 'ops'=>$ops];
	}


	function _blockquoteToDelta( $node, $attrs, $hasChildNodes, $newline="\n") {

		$ops = [];
		if ( $hasChildNodes === true ) {
			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}
			});
		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			$ops  = [["insert"=>$text]];
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle( $attrs['style'], $ops, ['blockquote'=> true], "\n");
		return ['break'=>true, 'ops'=>$ops];

	}

	function _ulToDelta( $node, $attrs, $hasChildNodes, $newline="\n") {

		$ops = [];
		if ( $hasChildNodes === true ) {
			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "", 'bullet');
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}
			});
		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			$ops  = [["insert"=>$text]];
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle( $attrs['style'], $ops, [], "\n");
		return ['break'=>true, 'ops'=>$ops];

	}


	function _olToDelta( $node, $attrs, $hasChildNodes, $newline="\n") {

		$ops = [];
		if ( $hasChildNodes === true ) {
			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "", 'ordered');
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}
			});
		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			$ops  = [["insert"=>$text]];
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle( $attrs['style'], $ops, [], "\n");
		return ['break'=>true, 'ops'=>$ops];

	}

	function _liToDelta( $node, $attrs, $hasChildNodes, $newline="\n", $group=null ) {

		$ops = [];
		if ( $hasChildNodes === true ) {
			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}
			});
		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			$ops  = [["insert"=>$text]];
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle( $attrs['style'], $ops, ['list'=> $group], "\n");
		return ['break'=>true, 'ops'=>$ops];

	}

	function _headerToDelta( $node, $attrs, $hasChildNodes, $newline="\n", $header=null ) {

		$ops = [];
		if ( $hasChildNodes === true ) {
			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}
			});
		} else {

			$text = $node->nodeValue;
			if ( !empty($text) && substr($text,-1) != "\n") {
				$text = $text . "\n";
			}
			$ops  = [["insert"=>$text]];
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle( $attrs['style'], $ops, ['header'=> $header], "\n");
		return ['break'=>true, 'ops'=>$ops];

	}



	function _italicToDelta( $node, $attrs, $hasChildNodes, $newline="") {
		$ops = [];
		// 遍历 Inline Nodes 
		if ( $hasChildNodes === true ) {

			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}

			});
		} else {
			$ops  = [["insert"=>$node->nodeValue]];	
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle($attrs['style'], $ops,['italic'=> true], $newline);
		return ['break'=>true, 'ops'=>$ops];


		// $text = trim($text);
		// if (empty($text) || $hasChildNodes == true || $text == "\n"  ) {
		// 	$ops = [];
		// } else {
		// 	$ops  = [["insert"=>$text]];	
		// }

		// if( empty($attrs['style']) ) {
		// 	$attrs['style'] = '';
		// }
		
		// return $this->applyStyle( $attrs['style'], $ops, ['italic'=> true]);

	}


	function _strongToDelta( $node, $attrs, $hasChildNodes, $newline="") {

		$ops = [];
		// 遍历 Inline Nodes 
		if ( $hasChildNodes === true ) {

			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}

			});
		} else {
			$ops  = [["insert"=>$node->nodeValue]];	
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle($attrs['style'], $ops,['bold'=> true], $newline);
		return ['break'=>true, 'ops'=>$ops];



		// $text = trim($text);
		// if (empty($text) || $hasChildNodes == true || $text == "\n"  ) {
		// 	$ops = [];
		// } else {
		// 	$ops  = [["insert"=>$text]];	
		// }

		// if( empty($attrs['style']) ) {
		// 	$attrs['style'] = '';
		// }
		
		// return $this->applyStyle( $attrs['style'], $ops, ['bold'=> true]);

	}

	function _brToDelta( $node, $attrs, $newline="\n" ) {
		$ops = [];
		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}
		$ops = $this->applyStyle($attrs['style'], $ops,[], $newline);
		return ['break'=>true, 'ops'=>$ops];
	}


	function _spanToDelta( $node, $attrs, $hasChildNodes , $newline="") {

		$ops = [];
		// 遍历 Inline Nodes 
		if ( $hasChildNodes === true ) {

			self::scan( $node, $tree, function( $node, $depth ) use ( & $ops ) {
				$op = $this->nodeToDelta($node, "");
				$break = ( isset($op['break']) && $op['break'] === true  ) ? true : false;
				if ( isset($op['ops']) && is_array($op['ops']) ) {
					$op = $op['ops'];
				}

				if ( !empty($op) ) {
					$ops = array_merge( $ops, $op );
				}

				// 在函数中处理子节点
				if ( $break == true  ) {
					return true;
				}

			});
		} else {
			if ( $newline == "" ) {
				$node->nodeValue = trim($node->nodeValue);
			}
			$ops  = [["insert"=>$node->nodeValue]];	
		}

		if( empty($attrs['style']) ) {
			$attrs['style'] = '';
		}

		$ops = $this->applyStyle($attrs['style'], $ops,[], $newline);
		return ['break'=>true, 'ops'=>$ops];



		// $text = trim($text);

		// if (empty($text) || $hasChildNodes == true || $text == "\n"  ) {
		// 	$ops = [];
		// } else {
		// 	$ops  = [["insert"=>$text]];	
		// }

		// if( empty($attrs['style']) ) {
		// 	$attrs['style'] = '';
		// }

		// return $this->applyStyle($attrs['style'], $ops);

	}


	function _imageToDelta( $node, $attrs, $hasChildNodes , $newline="") {

		$ops = [];
		$attrs['width'] = !empty($attrs['width']) ? $attrs['width'] : 1;
		$attrs['height'] = !empty($attrs['height']) ? $attrs['height'] : 1;
		$attrs['src'] = !empty($attrs['src']) ? $attrs['src'] : "";
		$src = !empty($attrs['data-src']) ? $attrs['data-src'] : $attrs['src'];
		$path = !empty($attrs['data-path']) ? $attrs['data-path'] : '';
		if ( $src == "") {
			return ['break'=>true, 'ops'=>null];
		}


		$size = !empty($attrs['data-s']) ? $attrs['data-s'] : $attrs['width'] . ",". $attrs['height'];
		$width = !empty($attrs['data-w']) ? $attrs['data-w'] : $attrs['width'];
		$height = !empty($attrs['data-h']) ? $attrs['data-h'] : $attrs['height'];
		$radio = !empty($attrs['data-ratio']) ? $attrs['data-ratio'] : $attrs['width']/$attrs['height'];
		$type = !empty($attrs['data-type']) ? $attrs['data-type'] : 'png';

		$height = $radio * $width;

		if ( $width == 1 ) {
			$width = null;
			$height = null;

			// $width = 1 * 100;
			// $width = $width . '%';
			// $height = $height . '%';
		}


		// if ( $attrs['width'] == 1 && $attrs['height']== 1 ) {
		// 	$attrs['width'] = '100%';
		// 	$attrs['height'] = '100%';
		// }

		// if ( $width == 1 && $attrs['height']== 1  ) {
		// 	$width = '100%';
		// 	$height = '100%';
		// } else {
		// 	$height = $radio * $width   .  'px';
		// 	$width = $width .'px';
		// }
		

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

		array_push($this->images, $img);

		return ['break'=>true, 'ops'=>[[
			// "attributes" => [],
			"insert" => [
				"cimage" => $img
			]
		]]];
	}

	function applyStyle( $style, $ops=[], $attributes =[], $newline = "" ) {

		if ( empty($style)  && empty( $attributes) ) {
			return $ops;
		}

		$st = $this->getStyles($style);
		foreach ($st as $n => $v) {
			switch ($n) {
				case 'font-size':
					$attributes['size'] = $v;
					break;
				case 'color':
					$attributes['color'] = $v;
					break;
				case 'background':
					$attributes['background'] = $v;
					break;
				case 'background-color':
					$attributes['background'] = $v;
					break;
				case 'text-indent':
					if ( substr($v, -2) == 'px' ) {
						$v = intval(str_replace('px', '',$v));
						$v = ceil($v/16 );
					}
					$attributes['textindent'] = $v;
					break;
				case 'indent':
					if ( substr($v, -2) == 'px' ) {
						$v = intval(str_replace('px', '',$v));
						$v = ceil($v/16 );
					}
					$attributes['indent'] = $v;
					break;
				case 'text-align':
					$attributes['align'] = $v;
					break;
			}
		}

		if ( empty($attributes) ) {
			return $ops;
		}

		$i = count($ops) - 1;
		if ( $i < 0 ) $i=0;
		if ( isset($ops[$i]) && isset($ops[$i]['insert'])) {
			if ( !isset($ops[$i]["attributes"]) ) {
				$ops[$i]["attributes"] = [];
			}
			if ( $newline == "\n" && is_string($ops[$i]['insert']) && substr($ops[$i]['insert'], -1) != "\n") {
				$ops[$i]['insert'] = $ops[$i]['insert'] . $newline;
			}
			$ops[$i]["attributes"] = array_merge($ops[$i]["attributes"], $attributes);

		} else {
			array_push( $ops, [
				"attributes" => $attributes,
				"insert" => $newline
			]);
		}

		return $ops;

	}

	function getStyles( $style ) {
		if ( $style == null ) {
			return  [];
		}

		$styles = explode(';', $style );
		$st = [];
		foreach ($styles as $styl ) {
			$arr = explode(':', $styl );
			if ( count($arr) == 2){
				$n = trim($arr[0]);
				$v = trim( $arr[1]);
				$st[$n] = $v;
			}
		}

		return $st;


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
					$break = $fn( $child, $depth,  $resp );
					if ( $break  !== true  ) {
						self::scan( $child, $resp, $fn, $depth );
					}

				} else if ( XML_TEXT_NODE === $child->nodeType ) { // DOMText 
					$break = $fn( $child, $depth,  $resp );
				}
			}
		}
	}

	function importMarkdown( $markdown ) {
	}


	function images() {
		return $this->images;
	}

	function videos(){
		return $this->videos;	
	}

	function files(){
		return $this->files;	
	}
	

}
<?php 
/**
 * MINA Delta Utils
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

use \Mina\Cache; 
use \Mina\Delta\Html;
use \Exception;


class Utils  {

	private $options = [];
	private $delta = [];
	private $html = null;
	private $nodeClass = null;

	function __construct( $delta=null, $options = [] ) {

		$this->options = $options;
		if ( !empty($delta)) {
			$this->load( $delta );
		}

		// 允许标签清单
		$this->tags = [
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
			"img" => ["class", "style", "alt", "src", "height", "width"],
			"ins" => ["class", "style"],
			"label" => ["class", "style"],
			"legend" => ["class", "style"],
			"li" => ["class", "style"],
			"ol" => ["class", "style", "start", "type"],
			"p" => ["class", "style"],
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
	}


	private $c = [
		"root" => null,
		"group" => null,
		"line" => null,
		"el" => null,
		"activeInline" => [],
		"beginningOfLine" => null
	];


	/**
	 * 载入 Delta 数据
	 * @param  [type] $delta [description]
	 * @return [type]        [description]
	 */
	function load( $delta ) {

		if ( is_string($delta) ) {
			$this->delta = $this->decode( $delta );
		} else if ( is_array($delta) ) {
			$this->delta = $delta;
		} else {
			throw new Exception("Delta 数据格式不正确" , 400 );
		}
		return $this;
	}


	function newGroup( $op ) {
		$lines = explode("\n", $op['insert']);
		foreach ($lines as $j => $line) { // 遍历每一行
			foreach( $op['attributes'] as $k=>$av ) {
				if(isset(\Mina\Delta\Node::$_FORMAT["block"][$k])) {
					$fn = \Mina\Delta\Node::$_FORMAT["block"][$k];
					if ( is_array($fn) ) { // 处理 List 等节点

						if ( !empty($this->c['group']) 
							&& $this->c['group']['type'] !== $k ) {
							$this->c['group'] = null;
						}

						if ( empty($this->c['group']) && !empty($fn['group']) ) {
							$this->c['group'] = [
								"el" =>$this->c['el']->format( $fn['group'], $av ),
								"type" => $k,
								"value" => $av,
								"fn" => $fn['line'],
								"distance" => 0
							];
							$this->newLine();
						}

						if ( is_array($this->c['group']) ) {
							// echo " Group={$av} $line \n";
							// $this->c['group']['el']->append($line);
							$this->c['group']['distance'] = 0;
						}

						// $fn = $fn['line'];

					} else {

						$this->c['el']->format($fn, $line, $av, $this->c['group'] );
						$this->newLine();

					} // END if ( is_array($fn) )	

					// $nextLine = ''; $prevLine ='';
					// if(isset($this->delta['ops'][$i+1]['insert']) ) {
					// 	$nextLine = $this->delta['ops'][$i+1]['insert'];
					// };

					// if(isset($this->delta['ops'][$i-1]['insert']) ) {
					// 	$prevLine = $this->delta['ops'][$i-1]['insert'];
					// };

					// $nextAttrs = []; $prevAttrs = [];
					// if(isset($this->delta['ops'][$i+1]['attributes']) ) {
					// 	$nextAttrs = $this->delta['ops'][$i+1]['attributes'];
					// };

					// if(isset($this->delta['ops'][$i-1]['attributes']) ) {
					// 	$prevAttrs = $this->delta['ops'][$i-1]['attributes'];
					// };

					// echo "fn {$fn} $line \n";
					// $this->c['el']->format($fn, $line, $av, $this->c['group'] );
					// $this->newLine();
					break;

				} // END if(\Mina\Delta\Node::$_FORMAT["block"][$k])

			} // END foreach( $op['attributes'] as $k=>$av )

		} // END foreach ($lines as $j => $line)

		$this->c['beginningOfLine'] = true;

	}
	

	public function images() {
		if ( $this->nodeClass != null ) {
			eval('$images = '. $this->nodeClass. '::images();');
			return $images;	
		}

		return [];
	}

	public function videos() {
		if ( $this->nodeClass != null ) {
			eval('$videos = '. $this->nodeClass. '::videos();');
			return $videos;	
		}
		return [];
	}

	public function files() {
		if ( $this->nodeClass != null ) {
			eval('$files = '. $this->nodeClass. '::files();');
			return $files;	
		}
		return [];
	}


	function convert( $node_class=null ) {
		
		if ( $node_class != null ) {
			if ( is_string($node_class) && class_exists($node_class) ) {
				$this->nodeClass = $node_class;	
			}
		}

		if ( $this->nodeClass == null ) {

			$this->nodeClass = '\\Mina\\Delta\\Html';
		}

		// $this->nodeClass::reset();
		eval( $this->nodeClass. '::reset();');
		$this->c['root'] = new $this->nodeClass();
		$this->newLine();

		foreach ($this->delta['ops'] as $i => $op ) {
				
			if ( !isset($op['attributes']) ) {
				$op['attributes'] = [];
			}

			if ( is_array($op['insert']) ) {  // 处理 Embed
				foreach( $op['insert'] as $k=>$v ) {
					
					if ( isset(\Mina\Delta\Node::$_FORMAT["embed"][$k]) ) {
						$fn = \Mina\Delta\Node::$_FORMAT["embed"][$k];
						$this->applyStyles( $op['attributes'] );
						$this->c['el']->format($fn, $v, $op['attributes'] );
					}
				}
			} else {  // Inline or Block
				$lines = explode("\n", $op['insert']);
				if ( $this->isBlock($op['attributes']) ) {
					$this->newGroup( $op );

				} else {  // else END if ( $this->isBlock($op['attributes']) ) 
					

					// 给每一行添加文字
					foreach ($lines as $j => $line) { // 遍历每一行

						if ( 
							($j >0 || $this->c['beginningOfLine']) &&
							is_array($this->c['group']) &&
							++$this->c['group']['distance'] >= 2
						) {
							$this->c['group'] = null;
						}

						$nextAttrs = []; $prevAttrs = [];
						if(isset($this->delta['ops'][$i+1]['attributes']) ) {
							$nextAttrs = $this->delta['ops'][$i+1]['attributes'];
						};

						if(isset($this->delta['ops'][$i-1]['attributes']) ) {
							$prevAttrs = $this->delta['ops'][$i-1]['attributes'];
						};

					
						// $this->c['el']->append("{$line}");

						$line = $this->c['el']->filter($line);

						// 处理 LI 列表呈现 ( 这个逻辑应该优化 )
						if ( isset($prevAttrs['list']) || isset( $nextAttrs['list']) || $this->c['el']->open == '<li>' ) {
							$line = str_replace("\n", '', $line);
							if ( !empty( trim($line) ) ) {

								if (  $this->c['group'] === null ) {
									// echo " NEXX OR PREV $line " . var_dump(isset($nextAttrs['list'])) . "\n";
									if ( isset($nextAttrs['list']) ) {
										$this->newLine();
										$this->newGroup($this->delta['ops'][$i+1]);
									}
								}

								if (  $this->c['group'] !== null ) {
									$fn = $this->c['group']['fn'];
									$this->c['el']->format($fn, $line, $this->c['group']['value'],  $this->c['group']);
									$this->newLine();
								}

							}

							continue;
						}
						
						$this->applyStyles( $op['attributes'],  $nextAttrs, $line ); // 处理 Inline 样式


						if ( $j < count($lines) - 1 ) {
							$this->newLine();
						}

					} // END foreach ($lines as $j => $line) 

					$this->c['beginningOfLine'] = false;
			
				} // END if ( $this->isBlock($op['attributes']) ) 
			
			} // END if ( is_array($op['insert']) )

		} // END foreach ($this->delta['ops'] as $i => $op ) 

		return $this->c['root'];

	} // function convert


	/**
	 * 新建一行
	 * @param  boolean $newPG [description]
	 * @return [type]         [description]
	 */
	private function newLine( $newPG=true, $parent = null ) {
		if ( $parent == null ){
			$parent = $this->c['root'];
		}

		$this->c['el'] = $this->c['line'] = $parent->newLine( $newPG );
		$parent->append( $this->c['line']);
		$this->c['activeInline'] = [];
	}


	/**
	 * 检查类型
	 * @param  [type]  $attrs [description]
	 * @return boolean        [description]
	 */
	private function isBlock( $attrs ) {
		foreach ($attrs as $k => $v) {
			// echo "{$k}=";
			// var_dump(isset(\Mina\Delta\Node::$_FORMAT["block"][$k]));
			if ( isset(\Mina\Delta\Node::$_FORMAT["block"][$k]) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * 处理参数
	 * @param  [type] $attrs     [description]
	 * @param  array  $nextAttrs [description]
	 * @return [type]            [description]
	 */
	private function applyStyles( $attrs, $nextAttrs = [],  $line =null ) {


		$first = []; $then = []; $styles = [];
		$tag = $this->c['el']; $seen = [];

		if ( !is_array($attrs) ) {
			$attrs = [];
		}

		while ($tag->fmt() !== null) {

			$fmt = $tag->fmt();
			$seen[$fmt] = true;
			if ( isset($attrs[$fmt]) ) {
				foreach ($seen as $k=>$sn ) {
					if ( isset($this->c['activeInline'][$k])) {
						unset( $this->c['activeInline'][$k] );
					}
				}

				$this->c['el'] = $tag->parent;
			}

			$tag = $tag->parent;
		}


		foreach ($attrs as $k => $v ) {
			if ( isset(\Mina\Delta\Node::$_FORMAT["inline"][$k]) ) {

				if( isset($this->c['activeInline'][$k] )) {

					if ( $this->c['activeInline'][$k] != $v ) {  // ?????
						// ???
					} else {
						continue;
					}
				}

				array_push($styles, $k);

				// if ( isset($nextAttrs[$k]) && $v == $nextAttrs[$k] ) {
				// 	// echo "\n next \n";
				// 	array_push( $first, $k );
				// } else {
				// 	array_push( $then, $k );
				// }

				$this->c['activeInline'][$k]  = $v;
			} // END INLINE
		}



		$el = $this->c['el']; $aflag = false;
		foreach ($styles as $idx=>$fmt ) {
			// echo  "\n $idx => $fmt \n";

			$fn = \Mina\Delta\Node::$_FORMAT["inline"][$fmt];
			$resp  = $el->format($fn, $attrs[$fmt] );
			if ( is_array($resp) ) {
				$newEl = new $this->nodeClass($resp);
				$el->append($newEl);
				$el = $newEl;

				if ( !is_null($line) ) {
					$el->append($line);
					$aflag = true;
				}
			}
		}

		if ( $aflag  === false ) {  // 如果未添加行号
			$this->c['el']->append( $line );
		}

		return $this;


		// $fmts = array_merge($first, $then);
		

		// foreach ($fmts as $fmt ) {

		// 	$fn = \Mina\Delta\Node::$_FORMAT["inline"][$fmt];
		// 	$newEl = $this->c['el']->format($fn, $attrs[$fmt]);
		// 	if ( is_array($newEl) ) {
		// 		 $newEl = new $this->nodeClass($newEl);
		// 	}

		// 	$newEl->fmt( $fmt );
		// 	$this->c['el']->append($newEl);
		// 	$this->c['el'] = $newEl;
		// }


		// foreach ($first as $fmt ) {

		// 	// echo " FIRST = {$fmt} \n";

		// 	$fn = \Mina\Delta\Node::$_FORMAT["inline"][$fmt];
		// 	$newEl = $this->c['el']->format($fn, $attrs[$fmt]);
		// 	if ( is_array($newEl) ) {
		// 		 $newEl = new $this->nodeClass($newEl);
		// 	}

		// 	$newEl->fmt( $fmt );
		// 	$this->c['el']->append($newEl);
		// 	$this->c['el'] = $newEl;
		// }

		// foreach ($then as $fmt ) {

		// 	$fn = \Mina\Delta\Node::$_FORMAT["inline"][$fmt];
		// 	$newEl = $this->c['el']->format($fn, $attrs[$fmt] );
		// 	if ( is_array($newEl) ) {
		// 		$newEl = new $this->nodeClass($newEl);
		// 	}
		// 	$newEl->fmt( $fmt );
		// 	$this->c['el']->append($newEl);
		// 	$this->c['el'] = $newEl;
		// }

	}


	/**
	 * 解码 Delta
	 * @param  string $json_text delta 字符串
	 * @return Array $delta 
	 */
	function decode( $json_text ) {

		$parser = new \Seld\JsonLint\JsonParser();
        $e = $parser->lint($json_text, \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC );
        if ( $e != null ) {
            throw new Exception("JSON解析错误" . $e->getDetails() , 400 );
        }

        $delta = $parser->parse($json_text, \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC);
        if ( !is_array($delta['ops']) ) {
        	throw new Exception("Delta 数据格式不正确" . $e->getDetails() , 400 );
        }

        return $delta;
	}

}
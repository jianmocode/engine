<?php 
/**
 * 将模板文件( {{var}} ) 编译成PHP原生模板 ( <?=$var?> )
 * 
 * @package      \Mina\Template
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 * @example
 * <?php
 *  use \Mina\Template\HtmlParser;
 *  
 * 	$hp = new HtmlParser();
 * 	$html = $hp->load(__DIR__ . "/assets/detail.page")
 * 			   ->insertAssets(["test.css", "test.js"])
 * 			   ->insertScript("
 * 			   		var foo = 'bar';
 * 			   		console.log( foo );
 * 			   	")
 * 			   ->insertWaterMarker()
 * 			   ->toPHP();
 * 	echo $html;
 * 	...
 *  
 */
namespace Mina\Template;
use Masterminds\HTML5;
use Exception;

define( 'COMPILE_FORMAT_PHP', 1);
define( 'COMPILE_FORMAT_PHPVAR', 2);
define( 'COMPILE_FORMAT_VNODE',3);
define( 'COMPILE_FORMAT_IGNORE', 4);
define( 'COMPILE_FORMAT_REST', 5);

class HtmlParser {

	private $template; 
	private $options;
	private $content;
	private $html5;
	private $dom;


	function __construct( $options=[] ) {

		$this->options = array_merge([
			"preserveWhiteSpace" => false,
			"formatOutput" => true,
		], $options);

		$this->html5 = new HTML5( $this->options) ;
	}


	/**
	 * 读取模板
	 * @param  string $template 模板文件地址
	 * @return $this
	 */
	function load( $template ) {
		if ( !file_exists($template) ) {
			throw new Exception("文件不存在 ($template) ", 404 );
		}

		if ( !is_readable($template) ) {
			throw new Exception("文件无法访问 ($template)", 503 );
		}

		return $this->loadHTML( file_get_contents($template) );
	}


	/**
	 * 读取模板 
	 * @param  string $template 模板正文
	 * @return $this
	 */
	function loadHTML( $template ) {

		$this->template = $template;
		$this->template =preg_replace("/<html[^>]*>/", "", $this->template);
		$this->template =preg_replace("/<\/html>/", "", $this->template);
        $this->dom = $this->html5->loadHTML( $this->template );
		return $this;
	}



	/**
	 * 编译
	 * @return [type] [description]
	 */
	function compile() {

		if ( empty($this->dom) ) {
			throw new Exception("未载入模板，请使用 load()/loadHTML() 方法载入", 500 );
		}

		$resp = [];
		self::scan( $this->dom,  $resp, function( $node, $depth, & $resp ) {

			if ( XML_ELEMENT_NODE == $node->nodeType ) {
				
				// 编译列表渲染
				HtmlParser::COMPILE_LOOP( $node );

				// 编译条件渲染
				HtmlParser::COMPILE_IF( $node );
				HtmlParser::COMPILE_ESIF( $node );
				HtmlParser::COMPILE_ELSE( $node );

				
				// 编译模板
				HtmlParser::COMPILE_TPL( $node );

				// 编译属性
				HtmlParser::COMPILE_ATTRS( $node );


			} else if (  XML_TEXT_NODE === $node->nodeType ) {

				// 渲染正文中的数值
			 	$node->nodeValue  =  HtmlParser::COMPILE_VAR( $node->nodeValue );

			 	// 渲染正文中的表达式
			 	$node->nodeValue  =  HtmlParser::COMPILE_EXP( $node->nodeValue );
			}
		});

		return $this;
	}

	/**
	 * 将 HTML 翻译为微信APP 格式
	 * 
	 * @return [type] [description]
	 */
	static function HTMLToWxapp( $html ) {

	}


	/**
	 *  编译为 Vdom 
	 */
	function toVdom() {

		// 1. 映射 Vdom node 结构  '<div  class="xxx"> {{}}</div>'
		// 2. 记录 ID 与 Vdom node 对应关系 ( Dom : vdom )
		// 3. SetData 的时候
		// 		FOR/IF/ 等应该如何渲染？
		// 		第一次:  解析模板， patch ( domNode, vdom  ); 存储当前 vdom 
		// 		第二次:  解析模板， patch ( vdom, vom1 );  存储当前 vdom 
		// 		第三次:  解析模板， patch ( vom1, vom2 ); 存储当前 vdom 
		// 		第四次:  ...

	}



	/**
	 * 输出为编译后PHP代码字符串
	 * @param  \Mina\Template\DataCompiler $compiler 编译器 ( 默认为 DataCompiler )
	 * @return string PHP代码字符串
	 */
	function toPHP() {

		if ( empty($this->dom) ) {
			throw new Exception("未载入模板，请使用 load()/loadHTML() 方法载入", 500 );
		}

		$this->compile();

        $html = $this->dom->saveHTML();		
        $html = html_entity_decode($html,ENT_QUOTES,"UTF-8");
		$html = str_replace('__PHP_SHORT_TAG_BEGIN__', '<?', $html );
		$html = str_replace('__PHP_SHORT_TAG_END__', '?>', $html );
		$html = str_replace('__HTML_AMP__', '&', $html );
		$html = str_replace('="__BOOLEAN_ATTR__"', '', $html );
		$html = str_replace('__VAR_TAG_BEGIN__', '{{', $html );
		$html = str_replace('__VAR_TAG_END__', '}}', $html );
		$html = str_replace('mp:status="compiled"', '', $html );
		$html = preg_replace("/\{\{(.+)(&gt;)(.+)\}\}/i", '{{$1>$3}}', $html);
		$html = preg_replace("/\{\{(.+)(&lt;)(.+)\}\}/i", '{{$1<$3}}', $html);
 
		return $html;
	}


	/**
	 * 插入 JS & CSS 等
	 * @return [type] [description]
	 */
	function insertAssets( $assets = [], $pos="head" ) {

		if ( empty($this->dom) ) {
			throw new Exception("未载入模板，请使用 load()/loadHTML() 方法载入", 500 );
		}

		if ( !is_array($assets) ) {
			throw new Exception( "assets 格式不正确 ", 500 );
		}

		$elm  = null;
		$elms = $this->dom->getElementsByTagName($pos);

		if ($elms == null ) return $this;
		foreach ( $elms as $item ) { $elm = $item; break; }
		if ( $elm == null ) return $this;


		foreach ( $assets as $file ) {
			if ( substr($file, -3) == 'css' ) {

				$style = $this->dom->createDocumentFragment();
				$style->appendXML("\t<link rel=\"stylesheet\" href=\"$file\" />\n");
				$elm->appendChild( $style );
				
			} else if ( substr($file, -2) == 'js' )  {

				$script = $this->dom->createDocumentFragment();
				$script->appendXML( "\t<script type=\"text/javascript\" src=\"$file\" ></script>\n" );
				$elm->appendChild( $script );
			}
		}

		return $this;
	}


	/**
	 * 插入 Javascript 脚本
	 * @param  [type] $script_code [description]
	 * @return [type]              [description]
	 */
	function insertScript( $script_code , $pos='body') {

		if ( empty($this->dom) ) {
			throw new Exception("未载入模板，请使用 load()/loadHTML() 方法载入", 500 );
		}

		$elm  = null;
		$elms = $this->dom->getElementsByTagName($pos);

		if ($elms == null ) return $this;
		foreach ( $elms as $item ) { $elm = $item; break; }
		if ( $elm == null ) return $this;

		$script = $this->dom->createDocumentFragment();
		$script->appendXML("\n<script type=\"text/javascript\">\n".$script_code."\n</script>\n");
		$elm->appendChild( $script );


		// $script = $this->dom->createElement('script', $script_code );
		// $script->setAttribute('type', 'text/javascript' );
		// $elm->appendChild( $script );

		return $this;
	}



	/**
	 * 结尾插入水印
	 * @return [type] [description]
	 */
	function insertWaterMarker() {

		if ( empty($this->dom) ) {
			throw new Exception("未载入模板，请使用 load()/loadHTML() 方法载入", 500 );
		}

		$elms = $this->dom->getElementsByTagName('body');
		if ($elms == null ) return $this;
		foreach ( $elms as $item ) { $elm = $item; break; }
		if ( $elm == null ) return $this;

		$watermarker = '
			<div style="text-align:center;color:#ccc;font-size:12px;width:100%;margin-top:20px;display:block;" > 
				本页采用 <a href="https://www.minapages.com/thanks" target="_blank"
					style="color:#46c37b;text-decoration:none;font-size:12px; margin-left:.5em; margin-right:.5em;font-family: \'Helvetica Neue\',\'Hiragino Sans GB\', Arial,sans-serif;"
				>
				<svg 
					t="1494852508558" class="icon" style=""  viewBox="0 0 1024 1024" 
					version="1.1" xmlns="http://www.w3.org/2000/svg" 
					p-id="9814" xmlns:xlink="http://www.w3.org/1999/xlink" width="12" height="12">
					<defs><style type="text/css"></style></defs><path d="M176.704 819.456c4.864-22.784 11.072-47.04 19.392-73.344 25.6-81.088 63.744-156.544 112.192-223.808 60.992-83.84 139.968-155.2 234.176-210.624 4.16-2.752 9.024-4.864 13.824-4.864 10.432 0 19.456 4.864 25.024 14.592 7.552 13.184 3.392 31.104-9.728 38.08-158.656 95.616-266.752 231.488-320.768 403.84-10.368 33.984-18.048 65.792-22.848 93.568 6.848 4.224 13.824 7.616 19.392 9.536 376.896 143.552 572.864-153.664 577.728-377.408 4.864-224.448 185.024-295.808 185.024-295.808S896.512 0 565.312 0C176 22.144 28.416 290.944 14.656 478.656 4.928 618.624 97.728 750.912 176.704 819.456M159.424 998.272c1.344 15.168 15.232 27.008 30.464 25.536 15.936-0.704 27.712-13.76 27.008-29.696-0.704-1.408-4.16-56.128 11.072-137.216-15.936-9.024-33.344-21.568-51.264-37.504C155.264 921.344 159.424 994.112 159.424 998.272" p-id="9815" fill="#46c37b"></path></svg>  

					MINA Pages 
 				</a> 制作发布
			</div>
		';
		$fragment = $this->dom->createDocumentFragment();
		$fragment->appendXML( $watermarker );
		$elm->appendChild( $fragment );
		return $this;
	}



	// =================================================================
	//   工具函数
	// =================================================================

	
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

				// @ See http://php.net/manual/en/dom.constants.php
				if ( XML_ELEMENT_NODE === $child->nodeType ) {  // DOMElement
					if ( $child->tagName != 'component' ) { // 暂时忽略组件，标签
						$fn( $child, $depth,  $resp );	
						self::scan( $child, $resp, $fn, $depth );
					}
				} else if ( XML_TEXT_NODE === $child->nodeType ) { // DOMText 

					$fn( $child, $depth,  $resp );
				}
			}
		}
	}

	static function toPHPVar( $var_name ) {

		$phpvar = '';
		$vars = explode('.', $var_name);

		foreach ($vars as $var ) {

			if ( $var == '__get' ) {
				$var = '_GET';
			} else if ( $var == '__var' ) {

				$var = 'GLOBALS[\'_VAR\']';

			} else if ( $var == '__se' ) {

				$var = '_SERVER';
			} else if ( $var == '__sys' ) {

				$var = 'GLOBALS[\'_SYS\']';
			}

			if ( empty($phpvar) ) {
				$phpvar = '$' . $var;
			} else {
				$phpvar = $phpvar . "['{$var}']";
			}
		}

		return $phpvar;
	}

	static function toPHPV( $text ) {

		$text = trim($text);
		$text = str_replace('{{', '', $text);
		$text = str_replace('}}', '', $text);

		$varPart = "[a-zA-Z\_]{1}[0-9a-zA-Z\.\_]+";
		$valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\/\-\,\:\=\'\"]+";
		// $boolPart = "true|false|([1]{1})|([0]{1})";
		$boolPart = "true|false";

		if ( preg_match("/^$boolPart$/", $text, $match) ) {
			return $text;

		} else if ( preg_match("/^$varPart/", $text, $match) ) {
			
			return self::toPHPVar( $text );

		} else if ( preg_match("/$valPart/u", $text, $match) )  {
			return $text;
		}
	}


	static function toPHPCond( $left, $op=null, $right=null ) {

		$phpleft = ''; $phpright = '';

		$varPart = "[a-zA-Z\_]{1}[0-9a-zA-Z\.\_]+";
		// $valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\=\'\"]+";
		$valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\/\-\,\:\=\'\"]+";
		// $boolPart = "true|false|([1]{1})|([0]{1})";
		$boolPart = "true|false";

		if ( preg_match("/^$boolPart$/", $left, $match) ) {
			$phpleft = $left;

		} else if ( preg_match("/^$varPart/", $left, $match) ) {
			
			$phpleft = self::toPHPVar( $left );

		} else if ( preg_match("/$valPart/u", $left, $match) )  {
			$phpleft = $left;
		}


		if ( preg_match("/^$boolPart$/", $right, $match) ) {
			$phpright = $right;
		} else if ( preg_match("/^$varPart/", $right, $match) ) {
			$phpright = self::toPHPVar( $right );
		} else if ( preg_match("/$valPart/u", $right, $match) )  {
			$phpright = $right;
		}

		if ( empty( $op ) || $right == "" ) {
			return "$phpleft";
        }
        
       

		return "$phpleft $op $phpright";

	}


	


	/**
	 * 在元素前后插入节点
	 * @param  [type] $node   XML_ELEMENT_NODE
	 * @param  [type] $before XML_NODE
	 * @param  [type] $after  XML_NODE
	 * @return  null
	 */
	static function insertOuter( $node,  $before=null,  $after=null ) {

		$parentElement = $node->parentNode;
		$nextElement = $node->nextSibling;

		if ( !empty($before) ) {
			$parentElement->insertBefore( $before, $node );
		}

		if ( !empty($after) ) {
			if ( $nextElement != null ) {
				$parentElement->insertBefore( $after, $nextElement );
			} else {
				$parentElement->appendChild($after );
			}
		}

		return [$before,  $after];
	}


	static function hasAttribute( $node, $name ) {
		foreach ($node->attributes as $attr ) {
			if ( $attr->name == $name ) return true;
		}

		return false;
	}


	/**
	 * 生成唯一ID ( 用于 VNODE 数据绑定)
	 * @param  string $prefix [description]
	 * @return [type]         [description]
	 */
	static function mpid ( $prefix = '' ) {
		return uniqid($prefix, true);
	}


	// =================================================================
	//   模板编译
	// =================================================================


	/**
	 * 列表渲染
	 * @param 
	 */
	static function COMPILE_LOOP( $node,  $format = COMPILE_FORMAT_PHP  ) {

		$loop_var  = $node->getAttribute('mp:for');
		$loop_from =$node->getAttribute('mp:for-from');
		$loop_to  = $node->getAttribute('mp:for-to');

		if ( ($loop_var == null) && ($loop_from == null) && ($loop_to == null) ) return ;

		if ( $node->getAttribute('mp:status') == 'compiled' ) return;

		$loop_index = !empty($node->getAttribute('mp:for-index')) ? $node->getAttribute('mp:for-index') : '{{index}}';
		$loop_item = !empty($node->getAttribute('mp:for-item')) ? $node->getAttribute('mp:for-item') : '{{item}}';
		
		if ( $format == COMPILE_FORMAT_PHP ) {

			$_INDEX = self::COMPILE_VAR( $loop_index, COMPILE_FORMAT_PHPVAR );
			$_ITEM = self::COMPILE_VAR( $loop_item, COMPILE_FORMAT_PHPVAR );
			
			if ( $loop_from != null ) {
				
				$_VAR = null;
				$_LOOP_FROM = self::COMPILE_VAR( $loop_from, COMPILE_FORMAT_PHPVAR );
				$_LOOP_FROM = self::COMPILE_EXP( $_LOOP_FROM, COMPILE_FORMAT_PHPVAR );


				$_LOOP_TO = $_LOOP_FROM;
				if ( $loop_var != null ) {
					$_VAR = self::COMPILE_VAR( $loop_var, COMPILE_FORMAT_PHPVAR );
					$_LOOP_TO = "count({$_VAR})";
				}

				if ( $loop_to  != null ) {
					$_LOOP_TO = self::COMPILE_VAR( $loop_to, COMPILE_FORMAT_PHPVAR );
					$_LOOP_TO = self::COMPILE_EXP( $_LOOP_TO, COMPILE_FORMAT_PHPVAR );
				}

				if ( !empty($_VAR)) {
					$__LOOP_BEGIN_ELM = $node->ownerDocument->createComment("<?php for( {$_INDEX}=$_LOOP_FROM; {$_INDEX}<({$_LOOP_TO}); {$_INDEX}++) : {$_ITEM}={$_VAR}[{$_INDEX}] ;?>");
				} else {
					$__LOOP_BEGIN_ELM = $node->ownerDocument->createComment("<?php for( {$_INDEX}=$_LOOP_FROM; {$_INDEX}<({$_LOOP_TO}); {$_INDEX}++) :?>");
				}
				$__LOOP_END_ELM = $node->ownerDocument->createComment("<?php endfor; ?>");

			} else {

				$_VAR = self::COMPILE_VAR( $loop_var, COMPILE_FORMAT_PHPVAR );
				$__LOOP_BEGIN_ELM = $node->ownerDocument->createComment("<?php foreach( {$_VAR} as {$_INDEX} => {$_ITEM} ) :?>");
				$__LOOP_END_ELM = $node->ownerDocument->createComment("<?php endforeach; ?>");
			}

			// 标记为已编译 & 设定 MP ID 用于绑定 Vnode
			$node->setAttribute('mp:status', 'compiled');
			$node->setAttribute('mp:id', self::mpid('for_'));

			self::insertOuter( $node, $__LOOP_BEGIN_ELM, $__LOOP_END_ELM);

			self::COMPILE_IF($node, $format, true);
		}

	}


	/**
	 * 条件渲染 IF 
	 * @param [type] $node [description]
	 */
	static function COMPILE_IF ( $node,  $format = COMPILE_FORMAT_PHP, $dontcheckstatus=false  ) {

		$if_exp  = $node->getAttribute('mp:if');

		if ( empty($if_exp) ) return ;
		if ( $node->getAttribute('mp:status') == 'compiled' && $dontcheckstatus === false ) return;

		if ( $format == COMPILE_FORMAT_PHP ) {
			
			$nextElement = $node->nextSibling;
			while ( $nextElement  != null  && (
				XML_TEXT_NODE == $nextElement->nodeType ||
				XML_COMMENT_NODE == $nextElement->nodeType 
				)) {
				$nextElement = $nextElement->nextSibling;
			}

			$endif = '' ;
			if ( $nextElement == null) {
				$endif = "<?php endif; ?>";
			} else if (  empty($nextElement->getAttribute('mp:elif')) && !self::hasAttribute($nextElement, 'mp:else') ) {
				$endif = "<?php endif; ?>";
			}


			if ( $dontcheckstatus === true ) {
				$endif = "<?php endif; ?>";
			}

            $_EXP = self::COMPILE_COND( $if_exp, COMPILE_FORMAT_PHPVAR );

            if ( empty($_EXP) ) {
                
                $_EXP =self::COMPILE_EXP( $if_exp, COMPILE_FORMAT_PHPVAR);

                if ( empty($_EXP) ) {
                    $_EXP = "true";
                }
            }
            
			$__IF_BEGIN_ELM = $node->ownerDocument->createComment("<?php if( $_EXP ) :?>");
			$__IF_END_ELM = $node->ownerDocument->createComment($endif);

			// 标记为已编译 & 设定 MP ID 用于绑定 Vnode
			if ( $dontcheckstatus === false ) {
				$node->setAttribute('mp:status', 'compiled');
			}

			$node->setAttribute('mp:id', self::mpid('if_'));
			self::insertOuter( $node, $__IF_BEGIN_ELM, $__IF_END_ELM);
		}

	}

	/**
	 * 条件渲染 ELIF
	 */
	static function COMPILE_ESIF ( $node,  $format = COMPILE_FORMAT_PHP ) {
		$if_exp  = $node->getAttribute('mp:elif');
		if ( empty($if_exp) ) return ;
		if ( $node->getAttribute('mp:status') == 'compiled' ) return;

		if ( $format == COMPILE_FORMAT_PHP ) {

			$nextElement = $node->nextSibling;
			
			// NextElement 有BUG, 忽略注释BUG ?
			// 
			// while ( $nextElement  != null  && XML_TEXT_NODE == $nextElement->nodeType ) {
			while ( $nextElement  != null  && XML_ELEMENT_NODE != $nextElement->nodeType ) {
				$nextElement = $nextElement->nextSibling;
			}

			$endif = '';
			if (  $nextElement !=  null ) {


				if (  empty($nextElement->getAttribute('mp:elif')) && !self::hasAttribute($nextElement, 'mp:else') ) {
					$endif = "<?php endif; ?>";
				}
			}

			$_EXP = self::COMPILE_COND( $if_exp, COMPILE_FORMAT_PHPVAR );
			$__IF_BEGIN_ELM = $node->ownerDocument->createComment("<?php elseif( $_EXP ) :?>");
			$__IF_END_ELM = $node->ownerDocument->createComment("$endif");

			// 标记为已编译 & 设定 MP ID 用于绑定 Vnode
			$node->setAttribute('mp:status', 'compiled');
			$node->setAttribute('mp:id', self::mpid('elseif_'));
			self::insertOuter( $node, $__IF_BEGIN_ELM, $__IF_END_ELM);

		}
	}

	/**
	 * 条件渲染 ELSE
	 */
	static function COMPILE_ELSE (  $node,  $format = COMPILE_FORMAT_PHP, $dontcheckstatus=false ) {

		$resp = self::hasAttribute($node, 'mp:else');
		if ( !$resp  ) return;
		if ( $node->getAttribute('mp:status') == 'compiled' && $dontcheckstatus === false ) return;

		if ( $format == COMPILE_FORMAT_PHP ) {

			$__IF_BEGIN_ELM = $node->ownerDocument->createComment("<?php else :?>");
			$__IF_END_ELM = $node->ownerDocument->createComment("<?php endif; ?>");

			// 标记为已编译 & 设定 MP ID 用于绑定 Vnode
			if ( $dontcheckstatus === false ) {
				$node->setAttribute('mp:status', 'compiled');
			}
			$node->setAttribute('mp:else', '__BOOLEAN_ATTR__');
			$node->setAttribute('mp:id', self::mpid('else_'));

			self::insertOuter( $node, $__IF_BEGIN_ELM, $__IF_END_ELM);
		}
	
	}


	/**
	 * 渲染属性中的数值
	 * @param [type] $node   [description]
	 * @param [type] $format [description]
	 */
	static function COMPILE_ATTRS( $node,  $format = COMPILE_FORMAT_PHP  ) {

		$except = ['mp:if', 'mp:elif',  'mp:else', 'mp:for', 'mp:for-index', 'mp:for-item', 'mp:for-from', 'mp:for-to', "mp:id", "mp:status"];
		$exceptTags = ['template'];
		if ( in_array($node->tagName, $exceptTags) ) {
			return;
		}

		foreach ($node->attributes as $name => $attrNode ) {

			if ( in_array($name, $except) ) continue;

            $value = $node->getAttribute($name);
			
			$value = self::COMPILE_VAR( $value , COMPILE_FORMAT_PHP );
            $value = self::COMPILE_EXP( $value, COMPILE_FORMAT_PHP );
            	
			if ( !empty($value) ) {
				$node->setAttribute($name, $value);
			}
		}
	}



	/**
	 * 处理模板
	 * @param [type] $node [description]
	 */
	static function COMPILE_TPL(  $node ) {
		
		if ( $node->tagName  != 'template' ) return;
		if ( $node->getAttribute('mp:status') == 'compiled' ) return;

		// 处理 <template is="{{message.type}}" data="{{message}}" />
		$is_tpl  = $node->getAttribute('is');
		if ( !empty($is_tpl) ) {

			$tpl = null;
			$name = self::COMPILE_VAR($is_tpl, COMPILE_FORMAT_PHPVAR );
			$prefix  = $node->getAttribute('data');
			$templates = $node->ownerDocument->getElementsByTagName('template');
			
			$mpid = self::mpid('tpl_');
			$node->setAttribute('mp:status', 'compiled');
			$node->setAttribute('mp:id', $mpid );

			foreach ($templates as $tpl_node ) {

				$tpl_name = $tpl_node->getAttribute('name');
				if ( empty($tpl_name) ) continue;
				if ( empty($tpl_node->childNodes) ) continue;

				$__TPL_BEGIN_ELM = $node->ownerDocument->createComment("<?php if ($name == \"$tpl_name\")  : ?>");
				$__TPL_END_ELM = $node->ownerDocument->createComment("<?php endif; ?>");

				self::insertOuter($node, $__TPL_BEGIN_ELM );
				$after = $__TPL_BEGIN_ELM;

				// 复制模板内容
				foreach ($tpl_node->childNodes as $tpl_child ) {

					$tpl_child_new = $tpl_child->cloneNode(true);
					self::COMPILE_NODE_VAR( $tpl_child_new, COMPILE_FORMAT_REST );
					self::COMPILE_NODE_VAR( $tpl_child_new, COMPILE_FORMAT_PHP, $prefix );
					
					if ( XML_ELEMENT_NODE == $tpl_child_new->nodeType ) {
						$tpl_child_new->setAttribute('mp:id', $mpid);
					}

					$elms = self::insertOuter($after, null, $tpl_child_new );
					$after = $elms[1];
				}
				self::insertOuter($after, null, $__TPL_END_ELM );
			}

			// echo $tpl_node->getAttribute('name')  . " $name \n";


			return;
		} 

		// 忽略模板内变量编译
		// 处理模板定义 <template name="hello"><div>xxx</div></template>
		self::COMPILE_NODE_VAR( $node, COMPILE_FORMAT_IGNORE );

	}


	static function COMPILE_NODE_VAR( $node, $format = COMPILE_FORMAT_IGNORE, $prefix="" ) {
		
		$resp['format'] = $format;
		$resp['prefix'] = $prefix;
		self::scan( $node,  $resp, function( $node, $depth, $resp ) {
			if (  XML_TEXT_NODE === $node->nodeType ) {
			 	$node->nodeValue  =  HtmlParser::COMPILE_VAR( $node->nodeValue, $resp['format'], $resp['prefix'] );
			}
		});

	}



	/**
	 * 处理表达式
	 */
	static function COMPILE_COND( & $text, $format = COMPILE_FORMAT_PHP, $replace=false ) {
		
		// $varPart = "[0-9a-zA-Z\.\_]+";
		$varPart = "[a-zA-Z\_]{1}[0-9a-zA-Z\.\_]+";
		// $valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\=\'\"]+";
		$valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\/\-\,\:\=\'\"]+";
		$opPart = "===|!==|!=|==|=>|>=|<=|=<|>|<";
		// $boolPart = "true|false|1|0";
		$boolPart = "true|false";
		$op = [">", "<", "!=", "==", ">=", "<=", "===", "!==" ];

		$condRE = "/\{\{($varPart|$valPart)[ ]*($opPart)[ ]*($varPart|$valPart)\}\}/u";
		if ( !preg_match_all($condRE, $text, $match ) ) {
			if ( !preg_match_all("/\{\{($boolPart)\}\}/", $text, $match ) ) {  // true/false/1/0

				if ( !preg_match_all("/\{\{($varPart)\}\}/", $text, $match ) ) { // $some_var
					return;
				}
			}
		}


		switch ($format) {
			case COMPILE_FORMAT_PHP:
				if ( count($match[0]) == 1 ) {

					$match[1] = empty($match[1]) ? [""] : $match[1];
					$match[2] = empty($match[2]) ? [""] : $match[2];
					$match[3] = empty($match[3]) ? [""] : $match[3];


					$cond = '__PHP_SHORT_TAG_BEGIN__=('.self::toPHPCond( $match[1][0], $match[2][0], $match[3][0]) . ')__PHP_SHORT_TAG_END__';
					if ( $replace == true) {
						$text = str_replace($match[0][0], $cond, $text);
						return $text;
					}

					return $cond;
				} else {
					$resp = [];
					foreach ($match[0] as $idx=>$val ) {
						$match[1] = empty($match[1]) ? [""] : $match[1];
						$match[2] = empty($match[2]) ? [""] : $match[2];
						$match[3] = empty($match[3]) ? [""] : $match[3];
						
						$resp[] = $cond = '__PHP_SHORT_TAG_BEGIN__=(' . self::toPHPCond( $match[1][$idx], $match[2][$idx], $match[3][$idx] ) . ')__PHP_SHORT_TAG_END__';

						if ( $replace == true) {
							$text = str_replace($match[0][$idx], $cond, $text);
						}
					}

					if ( $replace == true ) {
						return $text;
					}

					return $resp;
				}
				break;
			case COMPILE_FORMAT_PHPVAR:

				if ( count($match[0]) == 1 ) {
					$match[1] = empty($match[1]) ? [""] : $match[1];
					$match[2] = empty($match[2]) ? [""] : $match[2];
					$match[3] = empty($match[3]) ? [""] : $match[3];

					return self::toPHPCond( $match[1][0], $match[2][0], $match[3][0]);
				} else {
					$resp = [];
					foreach ($match[0] as $idx=>$val ) {
						$match[1] = empty($match[1]) ? [""] : $match[1];
						$match[2] = empty($match[2]) ? [""] : $match[2];
						$match[3] = empty($match[3]) ? [""] : $match[3];
						$resp[] = self::toPHPCond( $match[1][$idx], $match[2][$idx], $match[3][$idx] );
					}
					return $resp;
				}
				break;
			
			default:
				# code...
				break;
		}

	}


	/**
	 * 处理表达式
	 */
	static function COMPILE_EXP( $text, $format = COMPILE_FORMAT_PHP ) {

		// $varPart = "[0-9a-zA-Z\.\_]+";
		$varPart = "[a-zA-Z\_]{1}[0-9a-zA-Z\.\_]+";
		$valPart = "[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\/\-\,\:\=\'\"]+";
		$opPart = "===|!==|!=|==|=>|>=|<=|=<|>|<";
		$opPart2 = "\+|-|\*|\/|\%";
		// $boolPart = "true|false|1|0";
		$boolPart = "true|false";
		$condPart = "($varPart|$valPart)[ ]*($opPart)[ ]*($varPart|$valPart)";

		
		// Helper 函数  EG: {{REPLACE('helo','world', var.name)}}
		// [A-Z]+\(([0-9a-zA-Z\.\_]+|[0-9a-zA-Z\x{4e00}-\x{9fa5}\ \.\_\=\'"]+[, ]*)*\)
		$helperRE = "/\{\{[ ]*([A-Z][A-Za-z]+)\((($varPart|{$valPart}[, ]*)*)\)[ ]*\}\}/u";
		if ( preg_match_all($helperRE, $text, $match) ) {
			foreach ($match[0] as $idx => $origin ) {
				$text = self::COMPILE_EXP_HELPER( $text, $format, [
					"origin" => $origin,
					"helper" => $match[1][$idx],
					"params" => $match[2][$idx]
				]);
			}
		}

		// 二元运算 EG {{var.name + 50}} , {{var.name + 'hello'}}
		$binaryRE = "/\{\{[ ]*($varPart|$valPart)[ ]*($opPart2)[ ]*($varPart|$valPart)[ ]*\}\}/u";
		if ( preg_match_all($binaryRE, $text, $match) ) {
			foreach ($match[0] as $idx => $origin ) {
				$text = self::COMPILE_EXP_BINARY( $text, $format, [
					"origin" => $origin,
					"left" => $match[1][$idx],
					"op" => $match[2][$idx],
					"right" => $match[3][$idx]
				]);
			}
		}

		
		// 三元运算符 EG: {{(var.name == var.name2) ? 'OK' : ''}}
		$ternaryRE = "/\{\{[ ]*\([ ]*({$condPart}|{$boolPart}|{$varPart})[ ]*\)[ ]*\?[ ]*($varPart|$valPart)[ ]*:[ ]*($varPart|$valPart)[ ]*\}\}/u";
		if ( preg_match_all($ternaryRE, $text, $match) ) {

			foreach ($match[0] as $idx => $origin ) {
				$text = self::COMPILE_EXP_TERNARY( $text, $format, [
					"origin" => $origin,
					"condition" => $match[1][$idx],
					"true" => $match[5][$idx],
					"false" => $match[6][$idx]
				]);
			}
		}


		// 三元运算符简写  EG: {{(var.name == var.name2) ? 'OK'}}
		$ternaryShortRE = "/\{\{[ ]*\([ ]*({$condPart}|{$boolPart}|{$varPart})[ ]*\)[ ]*\?[ ]*($varPart|$valPart)[ ]*\}\}/u";;
		if ( preg_match_all($ternaryShortRE, $text, $match) ) {
			foreach ($match[0] as $idx => $origin ) {

				$text = self::COMPILE_EXP_TERNARY( $text, $format, [
					"origin" => $origin,
					"condition" => $match[1][$idx],
					"true" => $match[5][$idx],
					"false" => ''
				]);
			}
		}

		return $text;
		
	}


	/**
	 * 处理二元运算表达式
	 * {{var.name + var.name2}} => 
	 *  <?=(is_numberic($var["name"]) && is_numberic($var["name2"]) ) ? $var["name"] + $var["name2"] : $var["name"] . $var["name2"] ?>
	 * 
	 * @param [type] $text   待处理文本
	 * @param [type] $format 返回值格式  COMPILE_FORMAT_PHP | COMPILE_FORMAT_PHPVAR
	 * @param [type] $options 匹配信息
	 *                 ["origin"] 原始字符串
	 *                 ["left"] 左侧数据
	 *                 ["op"]   运算符 "\+|-|\*|\/|%";
	 *                 ["right"]   右侧数据
	 *                        
	 */
	static function COMPILE_EXP_BINARY( $text, $format, $options ) {

		$exp = '';
		$left = self::toPHPV( $options['left'] );
		$right = self::toPHPV( $options['right'] );

		if ( $options['op'] == '+' ) {
			$exp = "(is_numeric({$left}) __HTML_AMP____HTML_AMP__ is_numeric({$right})) ? {$left} + {$right} : strval({$left}) . strval({$right})";
		} else if ( $options['op'] == '/' ) {
			$exp = "{$left} {$options['op']} ( ({$right}!=0) ? {$right} : 1)" ;
		} else {
			$exp = "{$left} {$options['op']} {$right}";
		}

		switch ($format) {
			case COMPILE_FORMAT_PHP:
				$text = str_replace(trim($options['origin']), 
					'__PHP_SHORT_TAG_BEGIN__=('. $exp . ')__PHP_SHORT_TAG_END__',
					$text);
				return $text;
			break;
			case COMPILE_FORMAT_PHPVAR:
				return $exp;

			break;
		}


		return $text;
	}



	/**
	 * 处理三元运算表达式
	 * @param [type] $text   待处理文本
	 * @param [type] $format 返回值格式  COMPILE_FORMAT_PHP | COMPILE_FORMAT_PHPVAR
	 * @param [type] $options 匹配信息
	 *                 ["origin"] 原始字符串
	 *                 ["condition"] 条件表达式
	 *                 ["true"] 条件成功赋值
	 *                 ["false"] 条件失败赋值
	 *                        
	 */
	static function COMPILE_EXP_TERNARY( $text, $format, $options) {

		$varRE = "/\{\{([0-9a-zA-Z\.\_]+)\}\}/";
		$condition_text = '{{' .$options['condition'] . '}}' ;
		$options['false'] = !empty($options['false']) ? $options['false'] : "''";
		if ( preg_match_all($varRE, '{{' .$options['true'] . '}}', $match ) ) {
			$options['true'] = self::COMPILE_VAR( '{{' .$options['true'] . '}}', COMPILE_FORMAT_PHPVAR);
		}

		if ( preg_match_all($varRE, '{{' .$options['false'] . '}}', $match ) ) {
			$options['false'] = self::COMPILE_VAR( '{{' .$options['false'] . '}}', COMPILE_FORMAT_PHPVAR);
		}

		$condition = self::COMPILE_COND($condition_text, COMPILE_FORMAT_PHPVAR);
		$exp = "({$condition}) ? {$options['true']} : {$options['false']}";

		switch ($format) {
			case COMPILE_FORMAT_PHP:
				$text = str_replace(trim($options['origin']), 
					'__PHP_SHORT_TAG_BEGIN__='. $exp . '__PHP_SHORT_TAG_END__',
					$text);
				return $text;
			break;
			case COMPILE_FORMAT_PHPVAR:
				return $exp;
			break;
		}
	
		return $text;
	}


	/**
	 * 处理 HELPER 函数
	 * @param [type] $text   待处理文本
	 * @param [type] $format 返回值格式  COMPILE_FORMAT_PHP | COMPILE_FORMAT_PHPVAR
	 * @param [type] $options  匹配信息
	 *                 ["origin"] 原始字符串
	 *                 ["helper"] 函数名称
	 *                 ["params"] 函数参数
	 */
	static function COMPILE_EXP_HELPER( $text, $format, $options ) {

		$method = trim($options['helper']);

		if ( !method_exists('Mina\\Template\\Helper', $method) ) {
			return $text;
		}

		$params = explode(',', $options['params']);
		foreach ($params as $idx=>$p ) {
			$params[$idx] = self::toPHPV( $p );
		}
		$params_text = implode(',', $params);

		$exp = "\\Mina\\Template\\Helper::{$method}($params_text)";

		switch ($format) {
			case COMPILE_FORMAT_PHP:
				$text = str_replace(trim($options['origin']), 
					'__PHP_SHORT_TAG_BEGIN__='. $exp . '__PHP_SHORT_TAG_END__',
					$text);
				return $text;
			break;
			case COMPILE_FORMAT_PHPVAR:
				return $exp;
			break;
		}

		return $text;
	}


	/**
	 * 处理变量
	 * @param [type] $text [description]
	 */
	static function COMPILE_VAR( $text, $format = COMPILE_FORMAT_PHP, $prefix="" ) {

		if ( $format == COMPILE_FORMAT_REST ) {

			$text = str_replace('__VAR_TAG_BEGIN__', '{{', $text );
			$text = str_replace('__VAR_TAG_END__', '}}', $text );
			return $text;
		}

		$varRE = "/\{\{([0-9a-zA-Z\.\_]+)\}\}/";

		if ( !preg_match_all($varRE, $text, $match ) ) {
			return $text;
		}

		$prefix = empty($prefix) ? $prefix : $prefix . ".";
		$prefix = str_replace('{{', '', $prefix );
		$prefix = str_replace('}}', '', $prefix );

		switch ($format) {
			case COMPILE_FORMAT_PHP:

				foreach ($match[0] as $idx => $str ) {
					$text = str_replace(   $match[0][$idx], '__PHP_SHORT_TAG_BEGIN__=' . self::toPHPVar($prefix .  $match[1][$idx]) .'__PHP_SHORT_TAG_END__', $text  );
				}
				return $text;

				break;
			case COMPILE_FORMAT_PHPVAR:

				if ( count($match[1]) == 1 ) {
					return self::toPHPVar( $prefix .  end($match[1]) );
				} else {
					foreach ($match[1] as $idx=>$name ) {
						$match[1][$idx] = self::toPHPVar( $prefix . $name );
					}
					return $match[1];
				}
				break;

			case COMPILE_FORMAT_IGNORE:

				foreach ($match[0] as $idx => $str ) {
					$text = str_replace(  $match[0][$idx], '__VAR_TAG_BEGIN__' . $match[1][$idx] .'__VAR_TAG_END__', $text  );
				}
				return $text;
			
			default:
				# code...
				break;
		}

	}




}
<?php
namespace Xpmse;
use Masterminds\HTML5;

/**
 * 简单 Dom 节点对象
 */
class Dom {

    /**
     * 父节点 
     * @var Dom 
     */
    public $parent = null;

    /**
     * 标签名称
     * @var string
     */
    public $tag = null;

    /**
     * DOM属性
     * @var array $attrs[":name"] = ":value"
     */
    public $attrs = [];
    

    /**
     * 文本
     * @var string 
     */
    public $text = null;

    /**
     * 节点类型 ( 有效值 node: 元素节点 text:文本节点 )
     */
    public $type = "node";

    /**
     * 子节点
     * @var array [ Dom child ... ]  
     */
    public $children = [];


    /**
     * 简单DOM节点对象 new Dom("div", ["class"=>"jm-node"]);  / new Dom("这是文本节点", "text" );
     * @param string $tag 标签名称
     * @param array|string $attrs DOM属性($attrs[":name"] = ":value" ) | 或者 "text"
     */
    function __construct( string $tag=null, $attrs=[]) {

        if ( is_array($attrs) && !empty($tag) ) {
            $this->tag = $tag;
            $this->attrs = $attrs;
            $this->type  = "node";

        } else if ( is_string($attrs) && $attrs == "text" ) {
            $this->type = "text";
            $this->text = $tag;
        }

    }

    function __toString(){
        return $this->toHTML();
    }

    /**
     * HTML Node To Array 
     */
    public static function htmlNodeToArray( $node ) {

        $nodes = [];

        if ( $node === false || $node == null ) {
            return [];
        }

        if ( XML_ELEMENT_NODE === $node->nodeType ) {  // DOMElement

            $attrs = [];
            foreach( $node->attributes as $name => $nodeAttr ) {
                $attrs[$name] = trim($node->getAttribute($name));
            }

            $nodes = [
                "name" => $node->tagName,
                "type" => "node",
                "attrs" => $attrs,
                "children" => []
            ]; 

            

        } else if ( XML_TEXT_NODE === $node->nodeType ) { // DOMText 
            $value = trim($node->nodeValue);
            if ( !empty($value) ) {
                $nodes = [
                    "text" => $node->nodeValue,
                    "type" => "text"
                ]; 
            }
        } else if ( XML_DOCUMENT_NODE === $node->nodeType) {

            $nodes = [
                "name" => "document",
                "type" => "document",
                "children" => []
            ]; 
        }

        if ( $node->hasChildNodes() ) {

            for( $i=0; $i<$node->childNodes->length; $i++ ) {
                $child = $node->childNodes->item($i);
                $childNode = self::htmlNodeToArray( $child );
                if ( !empty($childNode) ) {
                    $nodes["children"][] = $childNode;
                }
            }
        }

        return $nodes;
    }


    /**
     * 通过HTML文本载入
     */
    public static function loadHTML( string $html, $withHtmlTag=false ) {

        $h5 = new HTML5([
			"preserveWhiteSpace" => false,
			"formatOutput" => true
        ]);

        if ( self::isText( $html) ) {
            $html = "<p>{$html}</p>";
        }

        // echo $html;

		$html =preg_replace("/<html[^>]*>/", "", $html);
		$html =preg_replace("/<\/html>/", "", $html);
        $root = $h5->loadHTML($html);

        $nodes = self::htmlNodeToArray( $root);

        // 过滤掉 document 
        if (  $nodes["type"] == "document" ) {
            $nodes = current($nodes["children"]);
        }

        // 过滤掉 html
        if ( $nodes["name"] == "html" &&  count($nodes["children"]) == 1 && $withHtmlTag === false ) {
            $nodes = current($nodes["children"]);
        }

        return self::load( $nodes );
    }

    /**
     * 检查文本内容是否为纯文本
     * @param string $content 文本内容
     * @return array 成功返回 true, 失败返回 false
     */
    public static function isText( string $content ) {
        if ( strpos($content, "<") === 0 ) {
            return false;
        }
        return true;
    }

    /**
     * 通过JSON文本载入
     */
    public static function loadJSON( string $json_text ) {
        $data = json_decode( $json_text, true );
        if ( $data  === false ) {
            return new Dom;
        }

        return self::load( $data );
    }


    /**
     * 通过Node数组载入
     */
    public static function load( array $nodes ) {  
        $dom = new Dom;
        if ( $nodes["type"] == "node" ) {
            $dom = new Dom( $nodes["name"], $nodes["attrs"] );
        } else if ( $nodes["type"] == "text") {
            $dom = new Dom( $nodes["text"], "text" );
        }

        if ( array_key_exists("children", $nodes) && is_array($nodes["children"]) ) {
            foreach( $nodes["children"] as $idx=>$child ) {
                $dom->children[$idx] = self::load( $child );
                if ( $dom->children[$idx]->type == "node"  ) {
                    $dom->children[$idx]->parent = $dom;
                }
            }
        }

        return $dom;
    }


    /**
     * 设定标签
     * @param string $tag 标签名称
     */
    public function setTag( string  $tag ) {
        $this->tag = $tag;
        return $this;
    }

    /**
     * 设定属性
     * @param array $attrs DOM属性 ( $attrs[":name"] = ":value" )
     */
    public function setAttrs( array $attrs ) {
        $this->attrs = $attrs;
        return $this;
    }


    /**
     * 设定文本
     * @param string $text 文本正文
     */
    public function text( string $text ) {
        $this->text = $text;
        return $this;
    }

    /**
     * 设定/读取元素内部文本 
     * @param string|bool $text 文本正文
     * @return 如果文本正文不为false 返回 $this(写入操作), 否则返回文本正文(读取操作)
     */
    public function innerText( $text = false ) {

        if ( $text  === false ) {
            return strip_tags($this->innerHTML());
        }

        $this->children = [];
        $this->append( new Dom($text, "text") );
        return $this;
    }

    /**
     * 设定/读取属性
     * @param string $name 属性名称
     * @param mix $value 属性数值, 默认为null
     * @return 如果属性数值不为null 返回 $this(写入操作), 否则返回该属性的数值(读取操作)
     */
    public function attr( string $name, $value=null ) {

        if ( $value === null ) {
            return array_key_exists( $name, $this->attrs) ?  $this->attrs[$name] : false;
        }
        $this->attrs[$name] = ($name == $value) ? true : $value;
        return $this;
    }

    /**
     * 删除属性
     * @param string $names... 属性名称
     * @return  返回 $this(写入操作)
     */
    public function removeAttr( ...$names ) {
        foreach($names as $name) {
            if ( isset($this->attrs[$name]) ) {
                unset( $this->attrs[$name] );
            }
        }
        return $this;
    }


    /**
     * 设定/读取样式 
     * @param string $name 样式名称
     * @param mix $value 样式数值, 默认为null
     * @return 如果样式数值不为null 返回 $this(写入操作), 否则返回该样式的数值(读取操作)
     */
    public function css( string $name, $value=null ) {

        $styles = array_key_exists( "style", $this->attrs) ? array_map("trim", explode(";", $this->attrs["style"])) : [];
        $css = [];
        array_walk( $styles, function( $style ) use( & $css ) {
            if ( empty($style)) {
                return;
            }
            $vals = array_map("trim", explode(":", $style));
            list( $key, $val ) = $vals;
            $css[trim($key)] = trim($val);
        });

        
        if ( $value === null ) {
            return array_key_exists($name, $css) ? $css[$name] : false;
        }
        $style = "";
        $css[$name] = $value;
        foreach( $css as $key =>$val ) {
            $style = $style . "{$key}:$val;";
        }
        $this->attrs["style"] = $style;
        return $this;
    }


    /**
     * 添加样式表
     * @param string $name 样式表名称
     * @return $this
     */
    public function addClass( string $name ) {

        $class = array_key_exists( "class", $this->attrs) ? array_map("trim",  explode(" ", $this->attrs["class"])) : [];
        $idx = array_search( $name, $class );

        if ( $idx === false ) {
            array_push( $class,  $name );
            $this->attrs["class"] = implode(" ", $class);
        }
        return $this;
    }


    /**
     * 删除样式表
     * @param string $name 样式表名称
     * @return $this
     */
    public function removeClass( string $name ) {

        $class = array_key_exists( "class", $this->attrs) ? array_map("trim", explode(" ", $this->attrs["class"])) : [];
        $idx = array_search( $name, $class );
        if ( $idx !== false ) {
            array_splice( $class, $idx, 1);
            $this->attrs["class"] = implode(" ", $class);
        }
        return $this;
    }


    /**
     * 删除当前节点
     * @return bool 成功删除返回 true, 失败返回 false;
     */
    public function remove() {

        if ( $this->parent == null ) {
            return false;
        }

        $idx = array_search( $this, $this->parent->children );
        if ( $idx !== false ) {
            array_splice( $this->parent->children, $idx, 1);
            return true;
        }
        return false;
    }


    /**
     * 追加一个子节点
     * @param Dom $n  插入的子节点
     */
    public function append( Dom $n ) {
        $n->parent = & $this;
        array_push( $this->children, $n );
    }


    /**
     * 查找当前节点的下一个节点
     */
    public function next() {

        if ( is_null($this->parent) ) {
            return null;
        }

        $idx = array_search( $this, $this->parent->children );
        $next = $idx + 1;

        if ( $next >= count( $this->parent->children) ) {
            return null;
        }
        return $this->parent->children[$next];
    }


    /**
     * 查找当前节点的上一个节点
     */
    public function prev() {

        if ( is_null($this->parent) ) {
            return null;
        }

        $idx = array_search( $this, $this->parent->children );
        $prev = $idx - 1;
        
        if ( $prev < 0 ) {
            return null;
        }
        return $this->parent->children[$prev];
    }


    /**
     * 向前插入一个兄弟节点
     * @param Dom $n  插入的兄弟节点
     */
    public function before( Dom $n ) {

        if ( is_null($this->parent) ) {
            $this->parent = new Dom('root');
            $this->parent->append( $this );
        }

        $idx = array_search( $this, $this->parent->children );

        if ( $idx === false ) {
            return false;
        }

        // 在之前插入
        $n->parent = $this->parent;
        array_splice( $this->parent->children, $idx, 0, [$n] );
    }


    /**
     * 向后插入一个兄弟节点
     * @param Dom $n  插入的兄弟节点
     */
    public function after( Dom $n ) {
        if ( is_null($this->parent) ) {
            $this->parent = new Dom('root');
            $this->parent->append( $this );
        }

        $idx = array_search( $this, $this->parent->children );

        if ( $idx === false ) {
            return false;
        }

        // 在之后插入
        $n->parent = $this->parent;
        array_splice( $this->parent->children, $idx + 1, 0, [$n] );

    }

    /**
     * 遍历根节点
     * @param Dom $n 根节点
     * @param function $callback( & Dom $node, $depth ); 回调函数 ( $Dom  当前节点,  $depth 深度) 
     * @param int $depth 深度
     */
    public static function each( Dom & $n, $callback, $depth = 0 ){
        if ( !\is_callable( $callback) ) {
            return;
        }

        if ( $callback( $n, $depth ) === false ){
            return;
        }

        $depth ++ ;
        if ( !empty($n->children) ) {
            foreach ( $n->children as & $child ) {
                self::each( $child, $callback, $depth );
            }
        }
    }

    /**
     * 转换为数组
     */
    public function toArray() {
        if ( $this->type == "text" ) {
            $nodes = [
                "text" => $this->text,
                "type" => "text"
            ]; 

        } else {

            $nodes = [
                "name" => $this->tag,
                "type" => "node",
                "attrs" => $this->attrs,
                "children" => []
            ]; 
        
            foreach ( $this->children as $child ) {
                $nodes["children"][] = $child->toArray();
            }

        }

        return $nodes;
    }

    /**
     * 设定/读取内部HTML
     */
    public function innerHTML($html = false) {
        
        if ( $html  === false ) {
            $html = "";
            foreach ( $this->children as $child ) {
                $html .= $child->toHTML();
            }
            return $html;
        }

        $node = Dom::loadHTML( "<div>" . $html  . "</div>");
        $this->children = [];
        foreach(  $node->children as $child ) {
            $this->append($child);
        }
        return $this;
    }


    /**
     * 转换为HTML输出
     */
    public function toHTML() {

        if ( $this->type == "text" ) {
            
            $html = $this->text;

        } else {

            // 合并属性
            $attrs = "";
            foreach( $this->attrs as $name =>$value ) {
                $str = ($value === true  || $name === $value ) ?  "{$name} " : "{$name}=\"".htmlspecialchars($value)."\" ";
                $attrs = $attrs . $str;
            }

            $html = "<{$this->tag} {$attrs}>";
            foreach ( $this->children as $child ) {
                $html .= $child->toHTML();
            }
            $html .= "</{$this->tag}>";

        }

        return $html;
    }

    /**
     * 转换为JSON
     */
    public function toJSON( $json_option = JSON_UNESCAPED_UNICODE ) {
        $nodes = $this->toArray();
        return json_encode($nodes, $json_option );
    }
}

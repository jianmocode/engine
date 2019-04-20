<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/npl/baidu/AipNlp.php');

use \Exception;
use \AipNlp;
use \Xpmse\Excp;
use \Xpmse\Err;
use \Xpmse\Conf;
use \Xpmse\Dom;
use \Mina\Cache\Redis as Cache;


/**
 * 内容处理
 */
class Content {

   

    /**
     * 默认受信的HTML标签和属性
     */
    protected  $allow_tags = [
        
        // 小程序许可标签内容
        "wxapp" =>  [
            "a" => [],
            "abbr" => [],
            "b" => [],
            "blockquote" => [],
            "br" => [],
            "code" => [],
            "col" => ["span", "width"],
            "colgroup" => ["span", "width"],
            "dd" => [],
            "del" => [],
            "div" => [],
            "dl" => [],
            "dt" => [],
            "em" => [],
            "fieldset"  => [],
            "h1" => [],
            "h2" => [],
            "h3" => [],
            "h4" => [],
            "h5" => [],
            "h6" => [],
            "hr" => [],
            "i" => [],
            "img"=> ["alt","src","height","width"],
            "ins" => [],
            "label" => [],
            "legend" => [],
            "li" => [],
            "ol"=>["start","type"],
            "p" => [],
            "q" => [],
            "span" => [],
            "strong" => [],
            "sub" => [],
            "sup" => [],
            "table" => ["width"],
            "tbody" => [],
            "td" => ["colspan","height","rowspan","width"],
            "tfoot" => [],
            "th"=>["colspan","height","rowspan","width"],
            "thead" => [],
            "tr" => [],
            "ul" => []
        ],

        // 移动客户端许可的内容
        "app" =>  [
            "a" => [],
            "abbr" => [],
            "b" => [],
            "blockquote" => [],
            "br" => [],
            "code" => [],
            "col" => ["span", "width"],
            "colgroup" => ["span", "width"],
            "dd" => [],
            "del" => [],
            "div" => [],
            "dl" => [],
            "dt" => [],
            "em" => [],
            "fieldset"  => [],
            "h1" => [],
            "h2" => [],
            "h3" => [],
            "h4" => [],
            "h5" => [],
            "h6" => [],
            "hr" => [],
            "i" => [],
            "img"=> ["alt","src","height","width"],
            "ins" => [],
            "label" => [],
            "legend" => [],
            "li" => [],
            "ol"=>["start","type"],
            "p" => [],
            "q" => [],
            "span" => [],
            "strong" => [],
            "sub" => [],
            "sup" => [],
            "table" => ["width"],
            "tbody" => [],
            "td" => ["colspan","height","rowspan","width"],
            "tfoot" => [],
            "th"=>["colspan","height","rowspan","width"],
            "thead" => [],
            "tr" => [],
            "ul" => []
        ],
        
        // 网页许可的标签内容
        "html"=>[
            "a" => [],
            "abbr" => [],
            "b" => [],
            "blockquote" => [],
            "br" => [],
            "code" => [],
            "col" => ["span", "width"],
            "colgroup" => ["span", "width"],
            "dd" => [],
            "del" => [],
            "div" => [],
            "dl" => [],
            "dt" => [],
            "em" => [],
            "fieldset"  => [],
            "h1" => [],
            "h2" => [],
            "h3" => [],
            "h4" => [],
            "h5" => [],
            "h6" => [],
            "hr" => [],
            "i" => [],
            "img"=> ["alt","src","height","width", "data-src", "data-width", "data-height", "data-caption"],
            "ins" => [],
            "label" => [],
            "legend" => [],
            "li" => [],
            "ol"=>["start","type"],
            "p" => [],
            "q" => [],
            "span" => ["data-url", "data-name"],
            "strong" => [],
            "sub" => [],
            "sup" => [],
            "table" => ["width"],
            "tbody" => [],
            "td" => ["colspan","height","rowspan","width"],
            "tfoot" => [],
            "th"=>["colspan","height","rowspan","width"],
            "thead" => [],
            "tr" => [],
            "ul" => [],
            "figure" => ["data-trix-content-type", "data-trix-attachment"],
            "figcaption" => [],
            "audio" => ["controls"],
            "video" => ["controls", "width", "height"],
            "source" => ["src", "type"]
        ]
    ];

    /**
     * 缓存类
     */
    private $cache = null;

    /**
     * 自然语言处理
     */
    private $nlp = null;

    /**
     * 数据配置
     */
    protected  $option ;

    /**
     * 文章内容
     */
    private $content  = null;


    /**
     * 原始 Dom 对象
     */
    private $dom = null;


    /**
     * 用于HTML的 Dom对象
     */
    public $html = null;

    /**
     * 用于小程序的 Dom对象
     */
    public $wxapp = null;

    /**
     * 用于APP 的Dom对象
     */
    public $app = null;


    /**
     * 资源集 图片
     */
    private $images = [];

    /**
     * 资源集 视频
     */
    private $videos = [];

    /**
     * 资源集 音频
     */
    private $audios = [];

    /**
     * 资源集 附件
     */
    private $attachments = [];

    /**
     * 资源集 附件(图片/视频/音频)等
     */
    private $figures = [];

    /**
     * 提取关键词
     */
    private $keywords = [];

    /**
     * 提取摘要
     */
    private $summary = "";


    /**
	 * 内容解析处理
	 * @param array  $option   配置信息
	 */
	function __construct( $option = [] ) {
	
        $this->option = array_merge([
			"preserveWhiteSpace" => false,
			"formatOutput" => true
		], $option);

		
        $this->cache = new Cache( [
            "prefix" => "_system:content:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);

    }


    /**
	 *  绑定自然语言处理
	 * @param array  $conf   配置信息
	 * @param string $engine 处理引擎，默认为百度AI
     * @return $this;
	 */
    public function withNLP( $conf, $engine="baidu" ) {
        $this->nlp = new NLP( $conf, $engine );
        return $this;
    }


    /**
     * 读取并解析内容
     */
    public function loadContent( $content ) {
        $this->content = $content;
        return $this;
    }

    
    /**
     * 解析数据
     */
    private function parserFor( $type = "html" ) {

        if ( is_null( $this->content) ) {
            return $this;
        }

        if ( is_null( $this->dom) ) {
            $this->dom = Dom::loadHTML( $this->content );
        }

        $self = $this; $dom = null; $filters = [];
        switch ( $type ) {

            case "html":
                $this->html = Dom::loadHTML( $this->content );
                $dom = & $this->html;
                $filters = $this->allow_tags["html"];
                $html_tag = "div";
                break;

            case "wxapp":
                $this->wxapp = Dom::loadHTML( $this->content );
                $dom = & $this->wxapp;
                $filters = $this->allow_tags["wxapp"];
                $html_tag = "div";
                break;

            case "app" : 
                $this->app = Dom::loadHTML( $this->content );
                $dom = & $this->app;
                $filters = $this->allow_tags["app"];
                $html_tag = "div";
                break;

            default: 
                $this->html = Dom::loadHTML( $this->content );
                $dom = & $this->html;
                $filters = $this->allow_tags["html"];
                $html_tag = "div";
                break;
        }
        

        if ( is_null( $dom ) ) {
            return $this;
        }

        // 重置资源文件
        $this->figures = []; $this->images = []; $this->videos = []; $this->audios = []; $this->attachments = [];
        Dom::each( $dom, function(& $node, $dept) use( & $self, $filters, $html_tag, $type) {
           
            if ( $node->tag == "html" ) {
                $node->setTag($html_tag);
            } 

            // 解析附件
            if ( $node->tag == "figure" )  {
                $self->parserFigure( $node,  $type );
                array_push( $self->figures, $node );
                return false;
            }

            // 解析图片
            if ( $node->tag == "img" )  {
                $self->parserImage( $node,  $type );
                array_push( $self->images, $node );
                return false;
            }

            // 解析视频
            if ( $node->tag == "video" )  {
                $self->parserVideo( $node,  $type );
                array_push(  $self->videos, $node );
                return false;
            }

            // 解析音频
            if ( $node->tag == "audio" )  {
                $self->parserAudio( $node,  $type );
                array_push(  $self->audios, $node );
                return false;
            }

            if ( $node->tag == "p-align-center" ) {
                $node->setTag('p')
                     ->css("text-align", "center");
            }

            if ( $node->tag == "p-align-left" ) {
                $node->setTag('p')
                     ->css("text-align", "left");
            }

            if ( $node->tag == "p-align-right" ) {
                $node->setTag('p')
                     ->css("text-align", "right");
            }

            // 过滤标签
            if ( !isset( $filters["{$node->tag}"] ) ) {
                $node->remove();
                return false;
            }

            // 过滤属性
            $allow_attrs = ["class", "style"];
            if ( is_array($filters["{$node->tag}"]) ) {
                $allow_attrs = array_merge( $allow_attrs, $filters["{$node->tag}"] );
            }

        });

        return $this;
    }


    /**
     * 处理 Figure
     */
    private function parserFigure( & $node, $type="html" ) {
        

        $contetType = $node->attr("data-trix-content-type") ? $node->attr("data-trix-content-type") : null;
        if ( !empty($contetType) ) {
            
            // 处理附件呈现
            if ( 
                strpos($contetType, "image") === false && 
                strpos($contetType, "video") === false && 
                strpos($contetType, "audio") === false 
            ) {
                $this->parserAttachment( $node, $type );
                array_push( $this->attachments, $node );
                return;
            }
        }


        // 查找数据
        $self = $this;
        Dom::each( $node, function(& $n, $dept) use( & $self, & $node, $type) {

            // 读取 Caption
            $caption = "";
            $next = $n->next();
            if ( !is_null( $next) && $next->tag == "figcaption") {
                $caption = htmlspecialchars($next->innerHtml());
            }
            
            // 解析图片
            if ( $n->tag == "img" )  {
                $n->attr("caption", $caption);
                $self->parserImage( $n, $type );
                $node = $n;
                array_push( $self->images, $node );
                return false;
            }

            // 解析视频
            if ( $n->tag == "video" )  {
                $n->attr("caption", $caption);
                $self->parserVideo( $n, $type );
                $node = $n;
                array_push(  $self->videos, $node );
                return false;
            }

            // 解析音频
            if ( $n->tag == "audio" )  {
                $n->attr("caption", $caption);
                $self->parserAudio( $n, $type );
                $node = $n;
                array_push(  $self->audios, $node );
                return false;
            }
        });

    }

    /**
     * 处理 Image
     */
    private function parserImage( & $node, $type="html" ) {

        if ( $type == "html" ) {
            $node->attr("data-src", $node->attr("src"));
            $node->attr("data-caption", $node->attr("caption"));
            $node->attr("data-width", $node->attr("width"));
            $node->attr("data-height", $node->attr("height"));
            $node->addClass("jm-content-image");
            $node->removeAttr( "src", "caption", "width", "height");
            
        // 微信 APP 增加 Max-width 属性
        }  else if ( $type == 'wxapp') {
            
            $node->attr("style", "max-width:100%; height:auto;");
            $img = $node->toHTML();
            // echo "\n$img\n";
            $caption = trim(htmlspecialchars_decode($node->attr("caption")));
            $node->setTag("div")->setAttrs([]);
            $node->addClass("jm-content-image");
            if ( !empty($caption) ) {
                $node->innerHTML("
                    {$img}
                    <div class=\"caption\">{$caption}</div>
                ");
            } else {
                $node->innerHTML("{$img}");
            }
        } else {
            
            $img = $node->toHTML();
            $caption = trim(htmlspecialchars_decode($node->attr("caption")));
            $node->setTag("div")->setAttrs([]);
            $node->addClass("jm-content-image");
            if ( !empty($caption) ) {
                $node->innerHTML("
                    {$img}
                    <div class=\"caption\">{$caption}</div>
                ");
            } else {
                $node->innerHTML("{$img}");
            }
        }
    }

    /**
     * 处理 Video
     */
    private function parserVideo( & $node, $type="html" ) {
        
        // if ( $type == "html" ) {
        if ( true ) {
            $video = $node->toHTML();
            $caption = trim(htmlspecialchars_decode($node->attr("caption")));
            $node->setTag("div")->setAttrs([]);
            $node->addClass("jm-content-video");
            if ( !empty($caption) ) {
                $node->innerHTML("
                    {$video}
                    <div class=\"caption\">{$caption}</div>
                ");
            } else {
                $node->innerHTML("{$video}");
            }
        }
    }
    

    /**
     * 处理 Audio
     */
    private function parserAudio( & $node, $type="html" ) {

        // if ( $type == "html" ) {
        if ( true ) {
            $audio = $node->toHTML();
            $caption = trim(htmlspecialchars_decode($node->attr("caption")));
            $node->setTag("div")->setAttrs([]);
            $node->addClass("jm-content-audio");
            if ( !empty($caption) ) {
                $node->innerHTML("
                    {$audio}
                    <div class=\"caption\">{$caption}</div>
                ");
            } else {
                $node->innerHTML("{$audio}");
            }
        }
    }

    private function parserAttachment( & $node, $type="html" ) {
        // if ( $type == "html" ) {
        if ( true ) {

            $contentType = $node->attr("data-trix-content-type");
            $href = current( $node->children );
            if ( is_null( $href ) ) {
                $node->setTag("span")->setAttrs([]);
                return;
            }

            $span = current( $href->children );
            if ( is_null( $span ) ) {
                $node->setTag("span")->setAttrs([]);
                return;
            }

            $url = $span->attr("data-url");
            $name = $span->attr("data-name");
            $node->setTag("div")->setAttrs([]);
            $node->addClass("jm-content-attachment");
            $node->innerHTML("
                <span class=\"attachment\" data-url=\"{$url}\" data-type=\"{$contentType}\" >{$name}</span>
            ");
        }
    }

 


    /**
     * 提取图片
     */
    public function images() {
        $imgs = [];
        foreach( $this->images as $img ) {

            if ( count($img->children) > 0 ){
                $img = current($img->children);
                array_push($imgs, [
                    "url" => $img->attr("src"),
                    "width" => $img->attr("width"),
                    "height" => $img->attr("height"),
                    "caption" => htmlspecialchars_decode( $img->attr("caption"))
                ]);
            } else {

                array_push($imgs, [
                    "url" => $img->attr("data-src"),
                    "width" => $img->attr("data-width"),
                    "height" => $img->attr("data-height"),
                    "caption" => htmlspecialchars_decode( $img->attr("data-caption"))
                ]);
            }
        }

        return $imgs;
    }


    /**
     * 提取视频
     */
    public function videos() {
        $videos = [];
        foreach( $this->videos as $video ) {
            $video =  current( $video->children);
            $source = current( $video->children);
            array_push( $videos, [
                "url" => $source->attr('src'),
                "type" => $source->attr('type'),
                "width" => $video->attr('width'),
                "height" => $video->attr('height')
            ]);
        }

        return $videos;
    }
    

    /**
     * 提取音频
     */
    public function audios() {

        $audios = [];
        foreach( $this->audios as $audio ) {

            $audio =  current( $audio->children);
            $source = current( $audio->children);
           
            array_push( $audios, [
                "url" => $source->attr('src'),
                "type" => $source->attr('type')
            ]);
        }

        return $audios;
    }

    /**
     * 提取附件
     */
    public function attachments() {
        $attachments = [];
        foreach( $this->attachments as $attachment ) {

            $attachment =  current( $attachment->children);

            array_push( $attachments, [
                "url" => $attachment->attr('data-url'),
                "type" => $attachment->attr('data-type'),
                "name" => $attachment->innerText()
            ]);
        }

        return $attachments;
    }


    /**
     * 提取文章关键词
     * @param string $title 标题
     * @param string $content 正文
     */
    public function keywords( $title=null, $content = null ) {

        if ( is_null( $this->nlp ) || is_null( $this->dom) ) {
            return [];
        }

        if ( $content == null ) {
            $content = strip_tags($this->dom->toHTML());
        }

        if ( $title == null ) {
            $title = $this->title( 80, $content );
        }

        $resp = $this->nlp->keyword( $title, $content );

        if ( !\is_array($resp['items']) ) {
            return  [];
        }

        $tags = array_column($resp['items'], "tag");
        return $tags;
    }

    /**
     * 提取文章摘要(纯文本格式)
     * @param int $length 截取长度
     * @param string $content 正文
     */
    public function summary( $length=300, $content = null ) {

        if ( empty($this->dom) ) {
            return null;
        }

        if ( $content == null ) {
            $content = strip_tags($this->dom->toHTML());
        }

        $content = trim(strip_tags($content));
		$content = str_replace('。', '.', $content);
		$arrs = explode('.', $content);
		$summary = current($arrs);
		$summary = mb_substr(trim($summary), 0, $length, 'UTF-8');
		return $summary;
    }


    /**
     * 提取标题
     * @param int $length 截取长度
     * @param string $content 正文
     */
    public function title( $length=80, $content = null ) {

        if( !empty($this->option["title"]) ){
            return $this->option["title"];
        }

        if ( $content == null ) {
            $content = strip_tags($this->dom->toHTML());
        }

        $content = trim(strip_tags($content));
		$content = str_replace('。', '.', $content);
		$arrs = explode('.', $content);
		$title = current($arrs);
		$title = mb_substr(trim($summary), 0, $length, 'UTF-8');
		return $title;
    }


    /**
     * 桌面HTML格式
     */
    public function html( $option = [] ) {
        $this->parserFor("html");
        return $this->html->toHTML();
    }

    /**
     * 手机HTML格式
     */
    public function mobile( $option = [] ) {
        $this->parserFor("html");
        return $this->html->toHTML();
    }

    /**
     * HTML 预览内容
     */
    public function htmlPreview( $option = [] ) {
    }


    /**
     * 小程序格式
     */
    public function wxapp( $option = [] ){
        $this->parserFor("wxapp");
        return $this->wxapp->toArray();
    }


    /**
     * 小程序预览内容
     */
    public function wxappPreview( $option = [] ) {

    }


    /**
     * 客户端格式
     */
    public function app( $option = [] ){
        $this->parserFor("app");
        return $this->app->toArray();
    }


    /**
     * 客户端预览内容
     */
    public function appPreview( $option = [] ) {
    }



}
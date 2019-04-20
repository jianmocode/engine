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



/**
 * 自然语言处理 (目前支持百度AI)
 * @see https://ai.baidu.com/docs#/NLP-API/top
 */
class NLP {

	private $conf = [];
	private $engine = 'baidu';
	private $client = null;

	static public $pos = [
		"n"=>"普通名词","f"=>"方位名词	","s"=>"处所名词","t"=>"时间名词",
		"nr"=>"人名	","ns"=>"地名","nt"=>"机构团体名","nw"=>"作品名",
		"nz"=>"其他专名","v"=>"普通动词","vd"=>"动副词","vn"=>"名动词",
		"a"=>"形容词","ad"=>"副形词","an"=>"名形词","d"=>"副词",
		"m"=>"数量词","q"=>"量词","r"=>"代词","p"=>"介词",
		"c"=>"连词","u"=>"助词","xc"=>"其他虚词","w"=>"标点符号"
	];

	/**
	 * 自然语言处理
	 * @param array  $conf   配置信息
	 * @param string $engine 处理引擎，默认为百度AI
	 */
	function __construct( $conf = [], $engine='baidu' ) {
		$this->conf = $conf;
		$this->engine = $engine;

		switch ($engine) {
			case 'baidu':
				try {
					$this->client = new AipNlp( $conf['appid'], $conf['apikey'], $conf['secretkey'] );
				} catch ( Exception $e ) {
					throw new Excp("百度API错误({$e})", 500, ['engine'=>$engine, 'conf'=>$conf]);
				}
				break;
			
			default:
				throw new Excp("暂不支持{$engine}处理引擎", 404, ['engine'=>$engine, 'conf'=>$conf]);
				break;
		}

	}


	/**
	 * 词法分析 
	 * 分词、词性标注、专名识别
	 * @param  String 	$text 待分析文本
	 * @return Array 	$rs
	 * 	       String 	$rs['text'] 原始文本条目
	 * 	       Array	$rs['items']  词汇数组，每个元素对应结果中的一个词
	 * 	       String 	$rs['items'][n]['item']  词汇的字符串
	 * 	       String	$rs['items'][n]['ne']  	 命名实体类型，命名实体识别算法使用。词性标注算法中，此项为空串
	 * 	       String	$rs['items'][n]['pos']   词性，词性标注算法使用。命名实体识别算法中，此项为空串
	 * 	       Int 		$rs['items'][n]['byte_offset']  在text中的字节级offset（使用GBK编码）
	 * 	       Int 		$rs['items'][n]['byte_length']  字节级length（使用GBK编码）
	 * 	       String 	$rs['items'][n]['uri']  链指到知识库的URI，只对命名实体有效。对于非命名实体和链接不到知识库的命名实体，此项为空串
	 * 	       String	$rs['items'][n]['formal']  词汇的标准化表达，主要针对时间、数字单位，没有归一化表达的，此项为空串
	 * 	       Array 	$rs['items'][n]['basic_words']  基本词成分
	 * 	       Array 	$rs['items'][n]['loc_details']  地址成分，非必需，仅对地址型命名实体有效，没有地址成分的，此项为空数组。
	 * 	       String 	$rs['items'][n]['loc_details']['type'] 成分类型，如省、市、区、县
	 * 	       Int		$rs['items'][n]['loc_details']['byte_offset'] 在item中的字节级offset（使用GBK编码）
	 * 	       Int	 	$rs['items'][n]['loc_details']['byte_length'] 字节级length（使用GBK编码）
	 * 	       			
	 * @see https://ai.baidu.com/docs#/NLP-API/top
	 */
	function lexer( $text ) {
		
		$textGBK =  mb_convert_encoding($text,'GBK', 'UTF-8');
		if ( strlen( $textGBK ) > 65535 ) {
			$textGBK = mb_substr($textGBK , 0, 32767, 'GBK');
			$text = mb_convert_encoding( $textGBK, 'UTF-8','GBK');
		}
		return $this->client->lexer($text);
	}



	/**
	 * 文章标签
	 * 根据文章标题和正文内容，提取关键词
	 * @param  String 	$title   文章标题，最大80字节
	 * @param  String 	$content 文章内容，最大65535字节
	 * @return Array  	$rs
	 *         Array	$rs['items'] 分析结果数组
	 *         String 	$rs['items'][n]['tag'] 内容标签
	 *         Float 	$rs['items'][n]['score'] 权重值，取值范围[0,1]
	 * 
	 */
	function keyword( $title, $content ) {

		$titleGBK =  mb_convert_encoding($title,'GBK', 'UTF-8');
		$contentGBK =  mb_convert_encoding($content,'GBK', 'UTF-8');

		if (  strlen( $titleGBK ) > 80 ) {
			$titleGBK = mb_substr($titleGBK , 0, 40, 'GBK');
			$title = mb_convert_encoding( $titleGBK, 'UTF-8','GBK');
		}

		if (  strlen( $contentGBK ) > 65535 ) {
			$contentGBK = mb_substr($contentGBK , 0, 32767, 'GBK');
			$content = mb_convert_encoding( $contentGBK, 'UTF-8','GBK');
		}

		if ( empty($content) ) {
			$content = $title;
		}

		return $this->client->keyword($title, $content);
	}

}

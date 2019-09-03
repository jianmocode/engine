<?php
/**
 * Class Utils
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \Yao\Str;

/**
 * 常用方法
 */
class Utils {

	/**
	 * 解析JSON字符串，可以准确通报错误 （ 但效率较低 )
	 * 
	 * @param  string  $json JSON字符串或文件
	 * @param  integer $flag 默认为 0  
	 *					   DETECT_KEY_CONFLICTS 删除重复键
	 *					   ALLOW_DUPLICATE_KEYS 允许重复键
	 *					   PARSE_TO_ASSOC 解析为 OBJECT   
	 *					   EG:  PARSE_TO_ASSOC & DETECT_KEY_CONFLICTS
	 * 
	 * @return mix 解析后的变量
	 * @see https://github.com/Seldaek/jsonlint
	 * 
	 */
	static public function json_decode( $json, $flag = \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC ) {
		
		if ( file_exists($json) ) {
			$json = file_get_contents( $json );
		}

		$parser = new \Seld\JsonLint\JsonParser();
		$e = $parser->lint($json, $flag );
		if ( $e != null ) {
            $message = $e->getMessage();
			throw Excp::create("$message", 400, ['details'=>$e->getDetails()]);
		}

		return $parser->parse($json, $flag);
    }
    

    /**
     * 读取JSON解析错误
     * @return string 错误描述
     */
    static public function json_error() {

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No errors';
            break;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                return 'Unknown error';
            break;
        }
    }
}
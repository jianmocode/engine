<?php

namespace Wechat\Encoder;

include_once __DIR__ . "/errorCode.php";
use \Wechat\Encoder\ErrorCode as ErrorCode;

/**
 * PKCS7Encoder class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7EncoderV2
{
	public static $block_size = 32;

	/**
	 * 对需要加密的明文进行填充补位
	 * @param $text 需要进行填充补位操作的明文
	 * @return 补齐明文字符串
	 */
	function encode( $text )
	{
		$block_size = PKCS7EncoderV2::$block_size;
		$text_length = strlen( $text );
		//计算需要填充的位数
		$amount_to_pad = PKCS7EncoderV2::$block_size - ( $text_length % PKCS7EncoderV2::$block_size );
		if ( $amount_to_pad == 0 ) {
			$amount_to_pad = PKCS7EncoderV2::$block_size;
		}
		//获得补位所用的字符
		$pad_chr = chr( $amount_to_pad );
		$tmp = "";
		for ( $index = 0; $index < $amount_to_pad; $index++ ) {
			$tmp .= $pad_chr;
		}
		return $text . $tmp;
	}

	/**
	 * 对解密后的明文进行补位删除
	 * @param decrypted 解密后的明文
	 * @return 删除填充补位后的明文
	 */
	function decode($text)
	{	


		$pad = ord(substr($text, -1));
		if ($pad < 1 || $pad > PKCS7EncoderV2::$block_size) {
			$pad = 0;
		}

		return substr($text, 0, (strlen($text) - $pad));
	}

}

/**
 * Prpcrypt class
 *
 * 
 */
class PrpcryptV2
{
	public $key;

	public function __construct( $k )
	{
		$this->key = $k;
	}

	/**
	 * 对密文进行解密
	 * @param string $aesCipher 需要解密的密文
     * @param string $aesIV 解密的初始向量
	 * @return string 解密得到的明文
	 */
	public function decrypt( $aesCipher, $aesIV )
	{

		try {
			$decrypted = openssl_decrypt($aesCipher, 'aes-128-cbc', $this->key, true, $aesIV);

		} catch (Exception $e) {
			return array(ErrorCode::$IllegalBuffer, null);
		}

		try {
			//去除补位字符
			$pkc_encoder = new PKCS7EncoderV2;
			$result = $pkc_encoder->decode($decrypted);
			// echo "\n=====\n";
			// print $result;
			// echo "\n=====\n";

		} catch (Exception $e) {
			// print $e;
			return array(ErrorCode::$IllegalBuffer, null);
		}
		return array(0, $result);
	}
}

?>
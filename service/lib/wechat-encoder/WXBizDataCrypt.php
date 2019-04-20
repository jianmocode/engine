<?php
namespace WeChat\Encoder;

// include_once "pkcs7Encoder.php";
// include_once "errorCode.php";

include_once __DIR__ . "/errorCode.php";
// include_once __DIR__ . "/sha1.php";
// include_once __DIR__ . "/xmlparse.php";
include_once __DIR__ . "/pkcs7EncoderV2.php";


use \Wechat\Encoder\ErrorCode as ErrorCode;
// use \Wechat\Encoder\SHA1 as SHA1;
// use \Wechat\Encoder\XMLParse as XMLParse;
use \Wechat\Encoder\PKCS7EncoderV2 as PKCS7EncoderV2;
use \Wechat\Encoder\PrpcryptV2 as PrpcryptV2;



class WXBizDataCrypt
{
    private $appid;
	private $sessionKey;

	/**
	 * 构造函数
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 * @param $appid string 小程序的appid
	 */
	public function __construct( $appid, $sessionKey)
	{
		$this->sessionKey = $sessionKey;
		$this->appid = $appid;
	}


	/**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData( $encryptedData, $iv, &$data )
	{
		if (strlen($this->sessionKey) != 24) {
			return ErrorCode::$IllegalAesKey;
		}
		$aesKey=base64_decode($this->sessionKey);

        
		if (strlen($iv) != 24) {
			return ErrorCode::$IllegalIv;
		}
		$aesIV=base64_decode($iv);

		$aesCipher=base64_decode($encryptedData, true);

		$pc = new PrpcryptV2($aesKey);
		$result = $pc->decrypt($aesCipher,$aesIV);

        
		if ($result[0] != 0) {
			return $result[0];
		}
        $dataObj=json_decode( $result[1] );
        if( $dataObj  == NULL )
        {
            return ErrorCode::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return ErrorCode::$IllegalBuffer;
        }
		$data = $dataObj;
		return ErrorCode::$OK;
	}

}
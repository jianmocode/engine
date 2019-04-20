<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Log.php');

use \Exception as Exception;


/**
 * XpmSE异常处理类定义
 */
class Excp  extends Exception {
	
	public $error;

	function __construct( $error, $code=null, $extra=null ) {
		
		if ( is_a($error, 'Xpmse\Err') || is_a($error, '\Xpmse\Err') ) {
			$this->error = $error;
			parent::__construct( $error->message, $error->code );
		} else if ( is_a($error, 'Exception') ) {
			$this->error = new Err( $error->getCode(),  $error->getMessage(), $extra );
			parent::__construct( $error->getMessage(), $error->getCode() );

		} else if ( is_string($error) ) {
			$this->error = new Err( $code,  $error, $extra );
			parent::__construct( $error, intval($code) );
		} else if ( is_null($error) ) {
                  $this->error = new Err( $code,  "未知错误", $extra );
            }
	}


	function getExtra() {
		return $this->error->extra;
	}

	function setExtra($name, $value) {
		$this->error->extra[$name] = $value;
	}


	function log(){
		$message = $this->getMessage();
		$error = (empty($this->error) ) ?  '':  $this->error->toArray();
		$log = new Log('Exception');
		return $log->error($message, [
            'code'=>$this->getCode(),
			'line'=>$this->getLine(),
			'file'=>$this->getFile(),
            // 'extra'=>$this->error->extra,
            // 'error' => $error,
			// 'trace'=>$this->getTrace(),
		]);
    }
    

	public static function elog( Exception $e ) {

		$message = $e->getMessage();
		$log = new Log('Exception');
		return $log->error($message, [
            'code'=>$e->getCode(),
			'line'=>$e->getLine(),
			'file'=>$e->getFile(),
			// 'trace'=>$e->getTrace(),
		]);
	}

	function toJSON() {
		return $this->error->toJSON();
	}

	function toArray() {
        if ( is_a($this->error, "\\Xpmse\\Err") ) {
            return $this->error->toArray();
        }
        $log = new Log('Exception');
        $log->error("未知错误",500, []);
        return [];
	}

	public static function etoJSON(  Exception $e  ) { 

		$code = ( intval($e->getCode()) == 0 ) ? 500 : $e->getCode();
		$data = [
			'message' => $e->getMessage(),
			'code' => $code,
			'extra' => [
				'line'=>$e->getLine(),
				'file'=>$e->getFile()
			]
		];

		echo json_encode($data);
	}

	public static function etoArray(  Exception $e  ) { 

		$code = ( intval($e->getCode()) == 0 ) ? 500 : $e->getCode();
		$data = [
			'message' => $e->getMessage(),
			'code' => $code,
			'extra' => [
				'line'=>$e->getLine(),
				'file'=>$e->getFile()
			]
		];

		return $data;
	}


	public static function erender( Exception $e, $templete ) {

		$data = [
			'message' => $e->getMessage(),
			'line'=>$e->getLine(),
			'file'=>$e->getFile(),
			'trace'=>$e->getTrace(),
		];

		if ( file_exists($templete) ) {
			@extract( $data );
			require( $templete );
		}
	}


	function render( $templete ) {

		$extra = $this->getExtra();
		$trace = $this->getTrace();
		if ( is_array($extra['__trace__'])) {
			$trace = array_merge( $extra['__trace__'], $trace );
		}
		
		$data = [
			'message' => $this->getMessage(),
			'line'=>$this->getLine(),
			'file'=>$this->getFile(),
			'error' => $this->error->toArray(),
			'trace'=>$trace,
		];

		if ( file_exists($templete) ) {
			@extract( $data );
			require( $templete );
		}
	}

}

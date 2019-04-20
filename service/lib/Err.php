<?php

namespace Xpmse;


/**
 * XpmSE错误对象
 */
class Err {

	public $code;
	public $message;
	public $extra=[];

	function __construct( $code, $message, $extra=[] ) {
		$this->code = $code;
		$this->message = $message;
		$this->extra = $extra;
	}

	static function isError( $obj  ) {
		return is_a($obj, '\Xpmse\Err');
	}

	function toJSON() {
		return  json_encode($this->toArray(), JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES );
	}
	
	function toArray(){
		return [
			'code' => $this->code,
			'message' => $this->message,
			'extra' => $this->extra
		];
	}
}


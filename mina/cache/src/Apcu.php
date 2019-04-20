<?php
/**
 * MINA Pages Apcu 缓存
 * 
 * @package      \Mina\Cache
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Cache;
use Mina\Cache\Base;
use \Exception;

class Apcu extends Base {

	private $apcu = null ;

	function __construct( $options = [] ) {
		parent::__construct( $options );
		
		if ( function_exists('\apcu_add') ) {
			$this->apcu = true ;
		}

	}

	public function ping(){
		if ( empty($this->apcu) ) return false;
		return $this->redis->ping();
	}

}
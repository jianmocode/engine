<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Mem.php');

use \Exception as Exception;

/**
 * XpmSE配置文件
 */
class Conf {

	public $conf;
	private $conf_name;
	private $mem = null;

	function __construct( $config_file = null ) {
		$this->renew( $config_file, true );
	}


	function clearCache() {
		$this->mem->del($this->conf_name);
	}

	function renew( $config_file = null, $cache=false ) {

		$filename = !empty($config_file) ? basename( $config_file ) : 'config.json' ;
		$file =  !empty($config_file) ? $config_file : _XPMSE_CONFIG_FILE ;
		$this->conf_name = $filename;
		
		$this->mem = $m = new Mem(false, 'Config:');
		if ( $cache === false )  {
			$m->del("$file");
		}

		$conf = $m->get("$file");
		if ( $conf === false ) {
			if ( file_exists($file) ) {
				$conf = file_get_contents($file);
				$m->set("$file", $conf);
			} else {
				$conf = '{}';	
			}
		}
		$this->conf = json_decode($conf, true);
		if ( $this->conf === null ||  json_last_error() ) {
			echo "解析配置文件失败 ( " .json_last_error_msg() . ")";
			echo "\n<br/><pre>\n";
			echo $conf;
			$m->del("$file");
			die();
		}
		
		return $this->conf;
	}

	public static function G( $name, $replace_domain=true ){
		$c = new Conf;
		return $c->get( $name, $replace_domain);
	}

	function get( $name, $replace_domain=true ) {
		if ( $this->conf == null ) {
			return null;
		}

		$r = explode('/', $name);
		if ( is_array($this->conf)) {
			$ret = $this->conf;
			foreach ($r as $n ) {
				if ( !isset($ret[$n]) ) {
					return null;
				}
				$ret = $ret[$n];
			}

			// Utils::out([$name, $ret]);

			if ( $replace_domain === true &&   in_array(trim($name), ['general/homepage','general/domain', 'general/static'])) {

				$real_host = ( !empty($_SERVER['FROM_HOST']) ) ? $_SERVER['FROM_HOST'] : null;
				if ( $real_host == null ) {
					$real_host = (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];
				}

				if ( trim($name) == 'general/domain' ) {
					$ret = $real_host;
				} else if ( in_array(trim($name), ['general/homepage', 'general/static']) ) {
					$domain = $this->get('general/domain', false);
					// Utils::out("name=$name domain=$domain\n");
					// return;
					
					if ( empty($ret) && $name == 'general/static') {
						$ret  = '//' . $real_host . '/static';
						return $ret;
					}

					if ( empty($ret) || $ret[0] == '/' ) {
						$ret  = '//' . $real_host . $ret;
						return $ret;
					}

					if( !empty($domain) ) {
						$ret = str_replace($domain, $real_host, $ret );
					}

				}
			}

			return $ret;
		}
	}

}

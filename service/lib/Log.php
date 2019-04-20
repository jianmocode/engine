<?php
namespace Xpmse;
require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
use \Exception as Exception;
use \Monolog\Logger as Logger;
use \Monolog\Formatter\LineFormatter as LineFormatter;
use \Monolog\Handler\StreamHandler as StreamHandler;
use \Monolog\Handler\RavenHandler as RavenHandler;
use \Raven_Client as Raven_Client;
use \Xpmse\Conf as Conf;
use \Psr\Log\AbstractLogger  as AbsPsrLogger;

/**
 * XpmSE日志类
 */
class Log {

	private $option = null;
	private $conf = null;
    private $logger=null;
	
	function __construct( $channel=null, $file = null ) {

		$this->logger = new Logger( $channel );
				   $c = new Conf;

		$this->conf = $c->get('log/server');
		$this->option = $c->get('log/option');

		$this->option['levels'] = (isset($this->option['report_levels'])) ? array_flip($this->option['report_levels']) : [];

		// $name = "core";
		// $path_info = dirname($_SERVER['SCRIPT_FILENAME']);
		// if ( strpos($path_info, _XPMAPP_ROOT) !== false ) {
		// 	$path =  str_replace(_XPMAPP_ROOT . '/', '',  $path_info);	
		// 	$info = explode('/', $path);
		// 	$name = $info[0];  // APP NAME
        // }
        
        // 指定文件
        if ( $file != null && is_writable($file) ) {
            $this->option['file_report'] = true;
            $this->conf['file'] = $file;
        }

		// 本地文件日志
		if ( isset($this->conf['file']) && $this->option['file_report'] === true ) {  

			$handler_stream = null;
			if ( file_exists($this->conf['file']) ) {
				if (is_writable($this->conf['file']) ) {
					$handler_stream = new StreamHandler($this->conf['file']);
				}
			} else if( is_writable(dirname($this->conf['file']))  ) {
				touch($this->conf['file']);
				chmod($this->conf['file'], 0777);
				$handler_stream = new StreamHandler($this->conf['file']);
			}

			if ( $handler_stream != null ) {
				$handler_stream->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% (release:".__VERSION.")\n"));
				$this->logger->pushHandler($handler_stream );
			}

		}


		// 本地Sentry服务器
		if ( isset($this->conf['local']) && $this->option['local_report'] === true ) {  
			$client_local = new Raven_Client( $this->conf['local'] ,[
				'release'=>_XPMSE_REVISION,
				'name'=>$name,
				"site"=>$_SERVER["HTTP_HOST"],
			]);
			$handler_local = new RavenHandler($client_local);
			$handler_local->setFormatter(new LineFormatter("[$channel]%message%\n"));
			$this->logger->pushHandler($handler_local);
		}


		// 远程 Sentry服务器
		if ( isset($this->conf['remote']) && $this->option['remote_report'] === true ) {  
			$client_remote = new Raven_Client( $this->conf['remote'] ,[
				'release'=>_XPMSE_REVISION,
				'name'=>$name,
				"site"=>$_SERVER["HTTP_HOST"],
			]);
			$handler_remote = new RavenHandler($client_remote);
			$handler_remote->setFormatter(new LineFormatter("[".$_SERVER["HTTP_HOST"].":$channel]%message%\n"));
			$this->logger->pushHandler($handler_remote);
		}

	}



	function info( $message, $context=[] ) {

		if ( !isset($this->option['levels']['info']) ) return true;

		return $this->logger->addInfo($message, $context);
	}

	function debug( $message, $context=[] ) {
		if ( !isset($this->option['levels']['debug']) ) return true;

		return $this->logger->addDebug($message, $context);
	}

	function notice( $message, $context=[] ) {
		if ( !isset($this->option['levels']['notice']) ) return true;

		return $this->logger->addNotice($message, $context);
	}

	function warning($message, $context=[] ) {
		if ( !isset($this->option['levels']['warning']) ) return true;

		return $this->logger->addWarning($message, $context);
	}

	function error($message, $context=[]) {
		if ( !isset($this->option['levels']['error']) ) return true;

		return $this->logger->error($message, $context);	
	}

	function critical($message, $context=[]) {
		if ( !isset($this->option['levels']['critical']) ) return true;

		return $this->logger->addCritical($message, $context);
	}

	function alert($message, $context=[]) {

		if ( !isset($this->option['levels']['alert']) ) return true;

		return $this->logger->addAlert($message, $context);
	}

	function emergency($message, $context=[]) {
		if ( !isset($this->option['levels']['emergency']) ) return true;

		return $this->logger->addEmergency($message, $context);
    }
    
    function getConfig(){
        return $this->conf;   
    }
}




/**
 * PsrLogger 
 */
class PsrLogger extends AbsPsrLogger
{
	public $verbose;
	private $channel = 'PsrLogger';


	public function __construct($verbose = false) {
		$this->verbose = $verbose;
		if ( defined('_XPMSE_LOG_CHANNEL') ) {
			$this->setChannel(_XPMSE_LOG_CHANNEL);
		}
	}

	public function setChannel( $channel ) {
		$this->channel = $channel;
	}


	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed   $level    PSR-3 log level constant, or equivalent string
	 * @param string  $message  Message to log, may contain a { placeholder }
	 * @param array   $context  Variables to replace { placeholder }
	 * @return null
	 */
	public function log($level, $message, array $context = array())
	{
		$method = $level;
		$log = new \Xpmse\Log( $this->channel );

		if ($this->verbose) {
			fwrite(
				STDOUT,
				'[' . $level . '] [' . strftime('%T %Y-%m-%d') . '] ' . $this->interpolate($message, $context) . PHP_EOL
			);

			$log->$method( $this->interpolate($message, $context) , $context );
			return;
		}

		if (!($level === \Psr\Log\LogLevel::INFO || $level === \Psr\Log\LogLevel::DEBUG)) {
			fwrite(
				STDOUT,
				'[' . $level . '] ' . $this->interpolate($message, $context) . PHP_EOL
			);
			$log->$method( $this->interpolate($message, $context) , $context );
		}
	}

	/**
	 * Fill placeholders with the provided context
	 * @author Jordi Boggiano j.boggiano@seld.be
	 * 
	 * @param  string  $message  Message to be logged
	 * @param  array   $context  Array of variables to use in message
	 * @return string
	 */
	public function interpolate($message, array $context = array())
	{
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}
	
		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}
}

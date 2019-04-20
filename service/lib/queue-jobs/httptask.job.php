<?php
namespace Xpmse;
require_once( __DIR__ . '/../Log.php');
require_once( __DIR__ . '/../Excp.php');
require_once( __DIR__ . '/../Utils.php');

use \Xpmse\Log as Log;
use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;


class JobHttpTask {
	
	private $log = null;	

	function __construct() {
		
	}

	public function perform() {
		
		set_time_limit(0);

	 	$id = $this->job->payload['id'];
	 	$class = $this->job->payload['class'];
	 	$queue_time = $this->job->payload['queue_time'];
	 	$short = substr($id, 0, 8);
	 	
	 	$ut = new Utils;
	 	$this->log = new Log("Queue::{$this->job->queue}");

	 	try {
	 		$resp = $ut->Request($this->args['method'], $this->args['url'], $this->args['opt']);
	 		$this->log->info("HttpTask任务运行完毕 #{$short}", ['id'=>$id, 'params'=>$this->args, 'resp'=>$resp, 'class'=>$class,  'queue_time'=>$queue_time]);
	 	} catch ( Excp $e ) {
	 		$e->log();
	 		$this->log->error("HttpTask任务运行失败 #{$short}", ['id'=>$id, 'params'=>$this->args, 'error'=>$e->error->toArray(), 'class'=>$class, 'queue_time'=>$queue_time]);
	 	}
	}
}

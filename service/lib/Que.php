<?php

namespace Xpmse;

require_once( __DIR__ . '/Mem.php');
require_once( __DIR__ . '/Log.php');

use \Exception as Exception;
use \Xpmse\Mem as Mem;
use \Xpmse\Log as Log;
use \Resque as Resque;
use \Resque_Job_Status as Resque_Job_Status;
use \Resque_Event as Resque_Event;
use \Resque_Worker as Resque_Worker;
use \SuperClosure\Serializer as Serializer; #USE https://packagist.org/packages/jeremeamia/SuperClosure


/**
 * XpmSE队列服务
 * @author WangJiaYi
 */
class Que {
	
	private $name = 'default';
	private $prefix = null;

	const STATUS_WAITING = Resque_Job_Status::STATUS_WAITING;
	const STATUS_RUNNING = Resque_Job_Status::STATUS_RUNNING;
	const STATUS_FAILED = Resque_Job_Status::STATUS_FAILED;
	const STATUS_COMPLETE = Resque_Job_Status::STATUS_COMPLETE;


	function __construct( $name = 'default' ) {


		$prefix = 'core';
		$this->name = $name;
		$this->prefix = $prefix;
		$this->quename = "{$this->prefix}_{$this->name}";
		$this->log = new Log("QUEUE::{$this->quename}");


		// 设定后端
		$m = new Mem;
		$redis = $m->redis();
		if ( $redis != null ) {
			$DB = getenv('REDIS_DB');
			if ( empty( $DB )) {
			    $DB  = 2;
			}
			$host = $redis->getHost();
			$port = $redis->getPort();
			$auth = $redis->getAuth();
			$dsn = "redis://{$host}:{$port}";
			if ( is_string($auth) ){
			    $dsn = "redis://user:{$auth}@{$host}:{$port}";
			}
			
			/**
			 * - host:port
			 * - redis://user:pass@host:port/db?option1=val1&option2=val2 (user not useed)
			 * - tcp://user:pass@host:port/db?option1=val1&option2=val2
			 * - unix:///path/to/redis.sock
			 */
			Resque::setBackend($dsn, $DB);
			$this->log->info( "Backend set to redis {$dsn} database {$db}", ['dsn'=>$dsn, 'db'=>$DB]);

			$PREFIX = __VHOST_NAME ? __VHOST_NAME . ':' . getenv('PREFIX') : 'local:'. getenv('PREFIX');
		    \Resque_Redis::prefix($PREFIX);
		    $this->log->info( 'Prefix set to {prefix}', ['prefix' => $PREFIX] );
		}
		
	}



	/**
	 * 添加一个 HTTP 任务
	 * @param string $method GET|PUT|DELETE|POST
	 * @param string $url   URL
	 * @param array  $opt    [description]
	 * @return string jobid
	 */
	

	/**
	 * 添加一个 HTTP 任务
	 * @param string $method GET/POST/PUT/DELETE
	 * @param string $url    请求地址
	 * @param array  $opt    请求参数
	 * @param mix    $fn     回调函数
	 * @return string jobid 或 回调函数返回值
	 */
	function addHttpTask(  $method,  $url, $opt=[], $fn = null ) {
		
		$task_id = Resque::enqueue(
			$this->quename, 
			'\Xpmse\JobHttpTask', 
			['method'=>$method, 'url'=>$url, 'opt'=>$opt], true );

		if ( $task_id ) {
			$short = substr($task_id, 0, 8);
			$this->log->info("HttpTask任务添加成功  #{$short}", ['task'=>$task_id, 'method'=>$method, 'url'=>$url, 'opt'=>$opt]);
		} else {
			$this->log->error("HttpTask任务添加失败", ['method'=>$method, 'url'=>$url, 'opt'=>$opt]);
		}

		if ( is_callable($fn) ) {
			return $fn( $task_id );
		}

		return $task_id;
	}


	/**
	 * 从队列中删除 HTTP 任务
	 * @param  [type] $task_id [description]
	 * @return [type]          [description]
	 */
	function delHttpTask( $task_id=null, $fn=null ) {
		
		$ret = false;
		$short = null;
		if ( $task_id == null) {
			$ret = Resque::dequeue($this->quename, ['\Xpmse\JobHttpTask']);
		} else {
			$ret = Resque::dequeue($this->quename, ['\Xpmse\JobHttpTask'=>$task_id]);
			$short = "#". substr($task_id, 0, 8);
		}

		if ( $ret ) {			
			$this->log->info("HttpTask任务删除成功 {$short}", ['task'=>$task_id]);
		} else {
			$this->log->error("HttpTask任务删除失败 {$short}",  ['task'=>$task_id]);
		}

		if ( is_callable($fn) ) {
			return $fn( $ret );
		}

		return $ret;
	}



	/**
	 * 添加 APP 任务
	 * @param string $app_name   [description]
	 * @param string $controller [description]
	 * @param string $action     [description]
	 * @param array  $query      [description]
	 * @param array  $data       [description]
	 * @param array  $files      [description]
	 */
	function addAppTask( $app_name, $controller, $action, $option=[], $fn=null, $bf=null ) {

		$before = null;
		if ( is_callable($bf) ) {
			$se = new Serializer();
			$before = $se->serialize($bf);
		}

		$callback = null;
		if ( is_callable($fn) ) {
			$se = new Serializer();
			$callback = $se->serialize($fn);
		}

		$task_id = Resque::enqueue(
			$this->quename, 
			'\Xpmse\JobAppTask', [
				'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 'option'=>$option,
			 	'callback'=>$callback,
			 	'before' => $before
			 ], true );

		if ( $task_id ) {
			$short = substr($task_id, 0, 8);
			$this->log->info("AppTask任务添加成功  #{$short}", [
				'task'=>$task_id, 
				'app_name'=>$app_name, 
				'controller'=>$controller, 'action'=>$action
			]);
		} else {
			$this->log->error("AppTask任务添加失败", [
				'app_name'=>$app_name, 
				'controller'=>$controller, 'action'=>$action
			]);
		}

		return $task_id;
	}



	/**
	 * 从队列中删除 APP 任务
	 * @param  [type] $task_id [description]
	 * @return [type]          [description]
	 */
	function delAppTask( $task_id=null, $fn=null ) {
		
		$ret = false;
		$short = null;
		if ( $task_id == null) {
			$ret = Resque::dequeue($this->quename, ['\Xpmse\JobAppTask']);
		} else {
			$ret = Resque::dequeue($this->quename, ['\Xpmse\JobAppTask'=>$task_id]);
			$short = "#". substr($task_id, 0, 8);
		}

		if ( $ret ) {			
			$this->log->info("HttpTask任务删除成功 {$short}", ['task'=>$task_id]);
		} else {
			$this->log->error("HttpTask任务删除失败 {$short}",  ['task'=>$task_id]);
		}

		if ( is_callable($fn) ) {
			return $fn( $ret );
		}

		return $ret;
	}




	/**
	 * 查询任务状态
	 * @param  [type] $task_id [description]
	 * @return [type]          [description]
	 */
	function status( $task_id ) {
		$st = new Resque_Job_Status($task_id);
		$status =  $st->get();
		if ( $status === false ) {
			$short = substr($task_id, 0, 8);
			$this->log->error("读取任务状态信息失败  #{$short}", ['task'=>$task_id]);
		}
		return $status;
	}


	function processed() {
		
		$workers = Resque_Worker::all();
		$resp = [];
		foreach ($workers as $worker ) {

			$job = $worker->job();

			if ( isset($job['payload'])) {
				$resp[]  = [
					"queue" => $job['queue'],
					"run_at" => strtotime($job['run_at']),
					"id" => $job['payload']["id"],
					"class" => $job['payload']["class"],
					"queue_time" => $job['payload']["queue_time"]
				];
			}
		}
		return $resp;
	}


	// function jobs(){
	// 	$queues = Resque::queues();
	// 	$resp = [];
	// 	foreach ($queues as $quename ) {
	// 		$jobs = Resque::redis()->get("queue:{$quename}");
	// 		$resp[$quename] = $jobs;

	// 	}
	// 	return $resp;
	// }


	function queues() {
		return Resque::queues();
	}

}

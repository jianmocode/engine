<?php
namespace Xpmse;

require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/Que.php');
require_once( __DIR__ . '/Model.php');


/**
 * *** 即将废弃 ***
 * *** 请使用 \XpmSE\Job 替代 **
 * 后台执行任务库 ( 用来查看后台正在运行进程 )
 * XpmSE 1.4.7 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Task
 *
 * USEAGE: 
 *
 *
 * 	// 创建推送任务
 *	$task_id = $t->run($task_name, [
 *			'app_name'=> "xpmsns/homepage",
 *			'c' => 'appstore',
 *			'a' => 'pushcode',
 *			'data' => array_merge([
 *				"host" => $host,
 *				"id" => $id
 *			],$params)
 *		], 
 *
 *		// 运行成功后挺回调 ( 通报进度 )
 *		function( $status, $task, $job_id, $queue_time, $resp ) use( $host, $id ) {
 *			$t = new \Xpmse\Task;
 *			if ( $status == 'failure') {
 *				$t->progress($task['task_id'], 100,  "推送应用: {$id} 到 $host 失败");
 *			} else {
 *				$t->progress($task['task_id'], 100,  "推送应用: {$id} 到 $host 成功" );
 *			}
 *		}
 *	);
 *	
 * // 控制器 > Action
 * function pushcode() {
 *	
 *		Utils::cliOnly();
 *		set_time_limit(0);
 *
 *		$t = new \Xpmse\Task;
 *		for( $i=0; $i<10; $i++) {
 *			$t->progressByJob($_GET['job_id'], $i*10 );
 *			sleep(1);
 *		}
 *		// $_POST: {"host":"dev.xpmsns.com","id":"5a8d749a6ecbf","cmd":"download","appid":"151809788364067","nonce":"SwCC7S","timestamp":"1519987667","signature":"ae0222823d96490d65c7c438287f00e9caf825d9"}
 *		// $_GET:  {"job_id":"f8ea6f81ee01cd4d132bb5ef1c5d814b"}
 *		echo json_encode([$_GET,$_POST]);
 *	}
 *
 */

use \Xpmse\Model as Model;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Que  as Que;


use \SuperClosure\Serializer as Serializer; #USE https://packagist.org/packages/jeremeamia/SuperClosure
use \Cron\CronExpression as CronExpression; #USE http://mtdowling.com/blog/2012/06/03/cron-expressions-in-php/

define('TASK_WAITING', 'waiting');  // 任务等待运行
define('TASK_PENDING', 'pending');  // 任务正在运行
define('TASK_ERROR', 'error');  // 任务运行失败
define('TASK_DONE', 'done');  // 任务运行完毕
define('THREAD_SLEEP', 60);   // 每分钟运行一次
define('THREAD_FLUSH', 10);   // 每10分钟刷新一次数据

class Task extends Model {

	private $cache = null;
	private $que = null;

	/**
	 * 任务数据表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'] , $driver );
		$this->table('task');
		$this->que = new Que;
	}

	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// 任务ID
			$this->putColumn( 'task_id', $this->type('string', [ "length"=>128,   'unique'=>1] ) )

			// 队列任务 ID
			->putColumn( 'job_id', $this->type('string', [ 'length'=>128, 'unique'=>1] ) )

			// 任务名称
			->putColumn( 'name', $this->type('string', [ "null"=>false, 'length'=>128] ) )

			// 任务SLUG 
			->putColumn( 'slug', $this->type('string', ['unique'=>1, 'length'=>128] ) )

			// 调用应用参数
			->putColumn( 'app', $this->type('longText', ['json'=>true] ) )

			// 任务开始执行前运行
			->putColumn( 'before', $this->type('longText', [] ) )

			// 任务执行完毕后回调
			->putColumn( 'callback', $this->type('longText', [] ) )

			// 计划任务表, 精确到1分钟 ( Crontab )
			->putColumn( 'schedule', $this->type('string', [ 'length'=>128] ) )

			// 进度提示
			->putColumn( 'progress_message', $this->type('string', [ 'length'=>128] ) )

			// 进度条
			->putColumn( 'progress', $this->type('integer', [ 'default'=>0 ] ) )

			// 任务开始执行前运行(before)结果
			->putColumn( 'response_before', $this->type('longText', ['json'=>true] ) )

			// 任务执行完毕后回调(callback)结果
			->putColumn( 'response', $this->type('longText', ['json'=>true] ) )

			// 任务执行完毕后回调结果返回代码
			->putColumn( 'response_code', $this->type('string', [ 'length'=>128] ) )

			// 状态信息
			->putColumn( 'status', $this->type('string', [ 'length'=>128, 'default'=>TASK_WAITING, 'index'=>true] ) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
	}


	/**
	 * 生成任务ID
	 * @param  string $param_string [description]
	 * @return [type]               [description]
	 */
	function task_id( $param_string="" ) {
		
		return uniqid();

		// $nextid = $this->nextid();
		// $param_string = "[{$nextid}]{$param_string}";
		// return hash('md4',  $param_string);   
	}


	/**
	 * 查询正在运行中的任务
	 * @return [type] [description]
	 */
	function getRunning() {
		$jobs = $this->que->processed();
		$job_ids = array_column($jobs, 'id');
		$qb = $this->query()->whereIn('job_id', $job_ids);
		$rows = $qb->select('*')->get()->toArray();
		$map = [];
		foreach ($rows as $rs ) {
			$map[ $rs['job_id'] ]  = $rs;
		}
		foreach ($jobs as & $job ) {
			$job['task'] = $map[$job['id']];
		}
		return $jobs;
	}



	function run(  $task_name,  $app=[],  $fn=null, $bf=null ) {

		$callback = null;
		if ( is_callable($fn) ) {
			$se = new Serializer();
			$callback = $se->serialize($fn);
		} else if ( is_string($fn) ) {
			$callback = $fn;
		}


		$before = null;
		if ( is_callable($bf) ) {
			$se = new Serializer();
			$before = $se->serialize($bf);
		} else if ( is_string($bf) ) {
			$before = $bf;
		}

		$task_id = $this->isExists($task_name, $app['app_name']);

		if ( $task_id !== false ) {

			$task = $this->updateBy('task_id',[
				"task_id" => $task_id,
				"job_id" => null,
				"name" => $task_name,
				"app" => $app,
				"callback" => $callback,
				"before" => $before,
				"progress" => 0,
				"progress_message" => null,
				"status" => TASK_WAITING
			]);

		} else {

			$task = $this->create([
				"task_id" => $this->task_id( $app['app_name'] ),
				"slug" => "{$app['app_name']}{$task_name}",
				"name" => $task_name,
				"app" => $app,
				"before" => $before,
				"callback" => $callback,
				"status" => TASK_WAITING
			]);
		}


		// string $app_name, string $controller, string $action, $option=[], $fn=null
		$job_id = $this->que->addAppTask( $app['app_name'], $app['c'], $app['a'], $app, 
			function( $response_code, $job_id, $queue_time, $resp, $resp_before ) use( $callback,  $task ) {
				
				$t = M('Task');
				$t->updateBy('task_id', [
					'status'=>TASK_DONE, 
					'slug' => $task['slug'] . time(),
					'task_id'=> $task['task_id'],
					'response_code'=>$response_code, 
					'response_before' => $resp_before,
					'response'=>$resp
				]);
				
				$fn = null;
				if ( !empty($callback) ) {
					$se = new \SuperClosure\Serializer();
					$fn = $se->unserialize($callback);
				}

				if ( is_callable($fn) ) {
					$fn( $response_code, $task, $job_id, $queue_time, $resp, $resp_before  );
				}
			},
			
			function( $job_id ) use( $before, $task ) {

				$t = M('Task');
				$t->updateBy('task_id', [
					'status'=>TASK_PENDING, 
					'task_id'=>$task['task_id'],
					'response_code'=>$response_code, 
					'response'=>$resp
				]);

				$bf = null;
				if ( !empty($before) ) {
					$se = new \SuperClosure\Serializer();
					$bf = $se->unserialize($before);
				}

				if ( is_callable($bf) ) {
					return $bf( $job_id, $task );
				}
			}
		);

		$task = $this->updateBy('task_id', ['job_id'=>$job_id, 'task_id'=>$task['task_id']]);
		return $task['task_id'];
	}



	function register(  $task_name,  $schedule,  $app=[],  $fn=null) {

		$callback = null;
		if ( is_callable($fn) ) {
			$se = new Serializer();
			$callback = $se->serialize($fn);
		}

		$task = $this->create([
			"task_id" => $this->task_id(  $app['app_name'] ),
			"slug" => "{$app['app_name']}{$task_name}",
			"name" => $task_name,
			"app" => $app,
			"callback" => $callback,
			"schedule" => $schedule,
			"status" => TASK_WAITING
		]);
		return $task['task_id'];
	}


	function rm( $task_name,  $app_name ) {
		$slug = $app_name . $task_name;
		return $this->remove($slug, 'slug', false);
	}


	/**
	 * 任务是否存在
	 * @param  [type]  $task_name [description]
	 * @param  [type]  $app_name  [description]
	 * @return boolean            [description]
	 */
	function isExists(  $task_name,  $app_name ) {
		$slug = $app_name . $task_name;
		$task_id = $this->getVar('task_id', "WHERE slug=? AND status <> '". TASK_DONE . "' LIMIT 1", [$slug]);
		if ( $task_id === null ) {
			return false;
		}
		return $task_id;
	}


	/**
	 * 任务是否正在运行
	 * @param  [type]  $task_name [description]
	 * @param  [type]  $app_name  [description]
	 * @return boolean            [description]
	 */
	function isRunning(  $task_name,  $app_name ) {
		$slug = $app_name . $task_name;
		$status = $this->getVar('status','WHERE slug=?', [$slug]);

		if ( $status == TASK_PENDING ) {
			return true;
		}

		return false;
	}


	/**
	 * 设定任务状态
	 */
	function progress(  $task_id,  $progress, $message='' ) {

		$data = [
			'progress'=>$progress, 
			'task_id'=>$task_id
		];

		if ( !empty($message) ) {
			$data['progress_message'] = $message;
		}

		return $this->updateBy('task_id', $data);
	}


	/**
	 * 使用 job_id 更新任务
	 * @param  [type] $job_id   [description]
	 * @param  [type] $progress [description]
	 * @param  string $message  [description]
	 * @return [type]           [description]
	 */
	function progressByJob( $job_id,  $progress, $message='' ) {

		$data = [
			'progress'=>$progress, 
			'job_id'=>$job_id
		];

		if ( !empty($message) ) {
			$data['progress_message'] = $message;
		}

		return $this->updateBy('job_id', $data);
	}


	/**
	 * 查询任务状态
	 * @param  [type] $task_id [description]
	 * @param  [type] $status  [description]
	 * @return [type]          [description]
	 */
	function status( $task_id,  $status= null ) {

		if ( $status != null ) {
			$this->updateBy('task_id', ['status'=>$status, 'task_id'=>$task_id]);
			return $status;
		}

		return $this->getVar('status','WHERE task_id=? LIMIT 1', [$task_id]);
	}


	/**
	 * 使用任务ID查询任务状态
	 * @param  [type] $job_id [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	function statusByJob( $job_id,  $status= null ) {

		if ( $status != null ) {
			$this->updateBy('job_id', ['status'=>$status, 'job_id'=>$job_id]);
			return $status;
		}
		return $this->getVar('status','WHERE job_id=?  LIMIT 1', [$job_id]);
	}


	

	function schedule() {

		Utils::cliOnly();
		$count = 0; 
		$tasks = $this->query()->whereNotNUll("schedule")->get()->toArray();


		$sleep = false;
		while(true) {
			if($sleep){
				sleep(THREAD_SLEEP);
				$count++;

				if ( $count == THREAD_FLUSH ) {
					$count = 0;
					$tasks = $this->query()->whereNotNUll("schedule")->get()->toArray();
				}
			}
			$sleep = true;
			foreach ($tasks as $t ) {
				$cron =  \Cron\CronExpression::factory($t['schedule']);
				if ($cron->isDue()) {
					$this->run( $t['name'], $t['app'], $t['callback'] );
				} 
			}
		}
	}

	function spider(){
		Utils::cliOnly();
		$count = 0; 
		$sleep = false;
		while(true) {
			if($sleep){
				sleep(THREAD_SLEEP);
				$count++;

				if ( $count == THREAD_FLUSH ) {
					$count = 0;

				}
			}
			$sleep = true;

				$cron =  \Cron\CronExpression::factory('*/1 * * * *');
				if ($cron->isDue()) {

					exec('/xhost/code/ts.xpmsns.com/xpmse/bin/xpm.phar app run /xhost/code/ts.xpmsns.com/apps/jianmo/spider/controller Run.php run');
				} 
			}
	}

}


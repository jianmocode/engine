<?php
namespace Xpmse\Model;

/**
 * 
 * 后台执行任务库 ( 用来查看后台正在运行进程 )
 * XpmSE 1.4.7 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Model\Task
 *
 * USEAGE: 
 *
 */

use \Xpmse\Task  as TaskModel;
class Task extends TaskModel {
 	function __construct( $param=[] ) {
 		parent::__construct($param);
 	}
 }


// use \Xpmse\Model as Model;
// use \Xpmse\Mem as Mem;
// use \Xpmse\Excp as Excp;
// use \Xpmse\Err as Err;
// use \Xpmse\Conf as Conf;
// use \Xpmse\Stor as Stor;
// use \Xpmse\Utils as Utils;
// use \Xpmse\Que  as Que;

// use Mina\Cache\Redis as Cache;


// use \SuperClosure\Serializer as Serializer; #USE https://packagist.org/packages/jeremeamia/SuperClosure
// use \Cron\CronExpression as CronExpression; #USE http://mtdowling.com/blog/2012/06/03/cron-expressions-in-php/

// define('TASK_WAITING', 'waiting');  // 任务等待运行
// define('TASK_PENDING', 'pending');  // 任务正在运行
// define('TASK_ERROR', 'error');  // 任务运行失败
// define('TASK_DONE', 'done');  // 任务运行完毕
// define('THREAD_SLEEP', 60);   // 每分钟运行一次
// define('THREAD_FLUSH', 10);   // 每10分钟刷新一次数据

// class Task extends Model {

// 	private $cache = null;
// 	private $que = null;

// 	/**
// 	 * 任务数据表
// 	 * @param integer $company_id [description]
// 	 */
// 	function __construct( $param=[] ) {

// 		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
// 		parent::__construct($param , $driver );
// 		$this->table('task');

// 		$cacheOptions = [
// 			"prefix" => '_task:',
// 			"host" => Conf::G("mem/redis/host"),
// 			"port" => Conf::G("mem/redis/port"),
// 			"passwd" => Conf::G("mem/redis/password")
// 		];

// 		$this->cache = new Cache( $cacheOptions );
// 		$this->que = new Que;
// 	}

// 	/**
// 	 * 数据表结构
// 	 * @return [type] [description]
// 	 */
// 	function __schema() {
// 		// 数据结构
// 		try {
			
// 			// 任务ID
// 			$this->putColumn( 'task_id', $this->type('string', [ "length"=>128,   'unique'=>1] ) )

// 			// 队列ID
// 			->putColumn( 'job_id', $this->type('string', [ 'length'=>128] ) )

// 			// 任务名称
// 			->putColumn( 'name', $this->type('string', [ "null"=>false, 'length'=>128] ) )

// 			// 任务SLUG 
// 			->putColumn( 'slug', $this->type('string', ['unique'=>1, 'length'=>128] ) )

// 			// 调用应用参数
// 			->putColumn( 'app', $this->type('longText', ['json'=>true] ) )

// 			// 回调函数
// 			->putColumn( 'callback', $this->type('longText', [] ) )

// 			// 计划任务表, 精确到1分钟 ( Crontab )
// 			->putColumn( 'schedule', $this->type('string', [ 'length'=>128] ) )

// 			// 进度提示
// 			->putColumn( 'progress_message', $this->type('string', [ 'length'=>128] ) )

// 			// 进度条
// 			->putColumn( 'progress', $this->type('integer', [ 'default'=>0 ] ) )

// 			// 结果信息
// 			->putColumn( 'response', $this->type('longText', ['json'=>true] ) )

// 			// 返回代码
// 			->putColumn( 'response_code', $this->type('string', [ 'length'=>128] ) )

// 			// 状态信息
// 			->putColumn( 'status', $this->type('string', [ 'length'=>128, 'default'=>TASK_WAITING, 'index'=>true] ) )

// 			;

// 		} catch( Exception $e ) {
// 			Excp::elog($e);
// 			throw $e;
// 		}
// 	}


// 	function task_id( $param_string="" ) {
// 		$nextid = $this->nextid();
// 		$param_string = "[{$nextid}]{$param_string}";
// 		return hash('md4',  $param_string);   
// 	}



// 	function run( string $task_name, array $app=[],  $fn=null ) {

// 		$callback = null;
// 		if ( is_callable($fn) ) {
// 			$se = new Serializer();
// 			$callback = $se->serialize($fn);
// 		} else if ( is_string($fn) ) {
// 			$callback = $fn;
// 		}

// 		if ( $task_id = $this->isExists($task_name, $app['app_name']) ) {

// 			$task = $this->updateBy('task_id',[
// 				"task_id" => $task_id,
// 				"name" => $task_name,
// 				"app" => $app,
// 				"callback" => $callback,
// 				"progress" => 0,
// 				"progress_message" => null,
// 				"status" => TASK_WAITING
// 			]);

// 		} else {

// 			$task = $this->create([
// 				"task_id" => $this->task_id( $app['app_name'] ),
// 				"slug" => "{$app['app_name']}{$task_name}",
// 				"name" => $task_name,
// 				"app" => $app,
// 				"callback" => $callback,
// 				"status" => TASK_WAITING
// 			]);

// 		}

// 		// string $app_name, string $controller, string $action, $option=[], $fn=null
// 		$job_id = $this->que->addAppTask( $app['app_name'], $app['c'], $app['a'], $app, 
// 			function( $status, $job_id, $queue_time, $resp ) use( $fn, $task ) {
				
// 				$t = M('Task');
// 				$t->updateBy('task_id', [
// 					'status'=>TASK_DONE, 
// 					'task_id'=>$task['task_id'],
// 					'response_code'=>$status, 
// 					'response'=>$resp
// 				]);
				
// 				if ( is_callable($fn) ) {
// 					$fn( $status, $task, $job_id, $queue_time, $resp  );
// 				}
// 			}
// 		);

// 		$task = $this->updateBy('task_id', ['job_id'=>$job_id, 'task_id'=>$task['task_id']]);
// 		return $task['task_id'];
// 	}



// 	function register( string $task_name, string $schedule, array $app=[],  $fn=null) {

// 		$callback = null;
// 		if ( is_callable($fn) ) {
// 			$se = new Serializer();
// 			$callback = $se->serialize($fn);
// 		}

// 		$task = $this->create([
// 			"task_id" => $this->task_id(  $app['app_name'] ),
// 			"slug" => "{$app['app_name']}{$task_name}",
// 			"name" => $task_name,
// 			"app" => $app,
// 			"callback" => $callback,
// 			"schedule" => $schedule,
// 			"status" => TASK_WAITING
// 		]);
// 		return $task['task_id'];
// 	}


// 	function rm( string $task_name, string $app_name ) {
// 		$slug = $app_name . $task_name;
// 		return $this->remove($slug, 'slug', false);
// 	}


// 	function isExists( string $task_name, string $app_name ) {
// 		$slug = $app_name . $task_name;
// 		$task_id = $this->getVar('task_id', "WHERE slug=? LIMIT 1", [$slug]);
// 		if ( $task_id === null ) {
// 			return false;
// 		}
// 		return $task_id;
// 	}


// 	function isRunning( string $task_name, string $app_name ) {
// 		$slug = $app_name . $task_name;
// 		$status = $this->getVar('status','WHERE slug=?', [$slug]);

// 		if ( $status == TASK_PENDING) {
// 			return true;
// 		}

// 		return false;
// 	}



// 	function progress( string $task_id, int $progress, string $message='' ) {

// 		$data = [
// 			'progress'=>$progress, 
// 			'task_id'=>$task_id
// 		];

// 		if ( !empty($message) ) {
// 			$data['progress_message'] = $message;
// 		}

// 		return $this->updateBy('task_id', $data);
// 	}



// 	function status( $task_id,  $status= null ) {

// 		if ( $status != null ) {
// 			$this->updateBy('task_id', ['status'=>$status, 'task_id'=>$task_id]);
// 			return $status;
// 		}

// 		return $this->getVar('status','WHERE task_id=?', [$task_id]);
// 	}


// 	function schedule() {

// 		Utils::cliOnly();
// 		$count = 0; 
// 		$tasks = $this->query()->whereNotNUll("schedule")->get()->toArray();
// 		$sleep = false;
// 		while(true) {
// 			if($sleep){
// 				sleep(THREAD_SLEEP);
// 				$count++;

// 				if ( $count == THREAD_FLUSH ) {
// 					$count = 0;
// 					$tasks = $this->query()->whereNotNUll("schedule")->get()->toArray();
// 				}
// 			}
// 			$sleep = true;
// 			foreach ($tasks as $t ) {
// 				$cron =  \Cron\CronExpression::factory($t['schedule']);
// 				if ($cron->isDue()) {
// 					$this->run( $t['name'], $t['app'], $t['callback'] );
// 				} 
// 			}
// 		}
// 	}

// }








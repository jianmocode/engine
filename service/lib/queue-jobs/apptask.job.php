<?php
namespace Xpmse;
require_once( __DIR__ . '/../Log.php');
require_once( __DIR__ . '/../Excp.php');
require_once( __DIR__ . '/../Utils.php');

use \Exception as Exception;
use \Xpmse\Log as Log;
use \Xpmse\Excp as Excp;
use \Resque_Event as Resque_Event;
use \SuperClosure\Serializer as Serializer; #USE https://packagist.org/packages/jeremeamia/SuperClosure

class JobAppTask {
	
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
	 	// $this->log->error("AppTask---debug perform #{$short}");

	 	$option  = is_array( $this->args['option']) ?  $this->args['option'] : [];
	 	$app_org = 'xpmse';
	 	$app_name = $this->args['app_name'];
	 	$controller = $this->args['controller'];
	 	$action  = $this->args['action'];
	 	$callback = $this->args['callback'];
	 	$before = $this->args['before'];


	 	$fn = null;
	 	if ( $callback !== null ) {
	 		$se = new Serializer();
	 		try {
	 			$fn = $se->unserialize($callback);
	 		}catch( Exception $e){}
	 	}

	 	$bf = null;
	 	if ( $before !== null ) {
	 		$se = new Serializer();
	 		try {
	 			$bf = $se->unserialize($before);
	 		}catch( Exception $e){}
	 	}

	 	$userslug = isset( $option['user'] ) ?  $option['user'] : null;
	 	$query =  is_array($option['query']) ? $option['query'] : [];
	 	$data =   is_array($option['data']) ? $option['data'] : [];
	 	$files =  is_array($option['files']) ? $option['files'] : [];
	 	$query['job_id'] = $id;
	 	
	 	$app = explode('/', $app_name);
	 	if ( count($app) == 2) {
	 		$app_org = $app[0];
	 		$app_name = $app[1];
	 	}

	 	$u = M('User');
		if ( $user != null ) {
			
			$user = $u->getLine("WHERE userid=? OR mobile=? LIMIT 1", [], [$userslug, $userslug] );
			if ( count( $user) == 0 ) {
				$this->log->error("AppTask任务运行失败 #{$short}", [
		 			'id'=>$id, 
		 			'app_org'=>$app_org, 'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 'message'=>"指定用户不存在",   'queue_time'=>$queue_time
		 		]);

		 		return;
			}
		} else {	

			$user = $u->getLine("WHERE isRobot=1 LIMIT 1");	
			if ( count( $user) == 0 ) {
				$avatar = $u->genAvatar('任务');
				$user = $u->create([
					"mobile" => "00000000000",
					'userid'=> $u->genUserid(),
					"name" => "任务机器人",
					"position" => "用来执行后台任务",
					'avatar'=>$avatar['avatar'],
					'department'=>[1],
					"isAdmin" => true,
					'isBoss'=>true,
					"isRobot" => true
				]);
			}
		}
		$u->setSession( $user['_id'] );

	 	try {

	 		$before_response = null;
	 		if ( is_callable($bf) ) {
	 			$before_response = $bf( $id );
	 			if ( $before_response === false ) { //返回
	 				if ( is_callable($fn) ) {
			 			$fn('complete', $id, $queue_time, null, $before_response );

			 			$this->log->info("AppTask任务运行完毕 #{$short}", [
				 			'id'=>$id, 
				 			'app_org'=>$app_org, 'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 'queue_time'=>$queue_time
				 		]);
			 		}
	 				return;
	 			}
	 		}

	 		$content = $this->app_run([
	 			"app_org" => $app_org,
	 			"app_name" => $app_name,
	 			"c" =>$controller,
	 			"a" => $action
	 		], $query, $data, $files );

	 		if ( is_callable($fn) ) {

	 			if ( is_array($content) &&  isset($content['code']) && isset($content['message']) && $content['code'] !== 0 ) {
	 				$fn('failure', $id, $queue_time, $content, $before_response );
	 			} else {
	 				$fn('complete', $id, $queue_time, $content, $before_response );
	 			}
	 		}

	 		$this->log->info("AppTask任务运行完毕 #{$short}", [
	 			'id'=>$id, 
	 			'app_org'=>$app_org, 'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 'queue_time'=>$queue_time
	 		]);

	 	} catch ( Excp $e ) {

	 		$this->log->info("AppTask任务运行失败 1 #{$short}", [ 'content'=>$e->getMessage() ]);

	 		if ( is_callable($fn) ) {
	 			$fn('failure', $id, $queue_time, $e, $before_response );
	 		}

	 		$this->log->error("AppTask任务运行失败 #{$short}", [
	 			'id'=>$id, 
	 			'app_org'=>$app_org, 'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 
	 			'message'=>$e->getMessage(),  'queue_time'=>$queue_time
	 		]);

	 	} catch( Exception $e ) {
	 		if ( is_callable($fn) ) {
	 			$fn('failure', $id, $queue_time, $e, $before_response );
	 		}

	 		$this->log->error("AppTask任务运行失败 #{$short}", [
	 			'id'=>$id, 
	 			'app_org'=>$app_org, 'app_name'=>$app_name, 'controller'=>$controller, 'action'=>$action, 
	 			'message'=>$e->getMessage(),   'queue_time'=>$queue_time
	 		]);
	 	}
	}


	private function app_run( $q, $query, $data, $files ) {

		require_once( TROOT .'/controller/core/app/route.class.php' );

		$_GET['n'] = 'core-app';
		$_GET['c'] = 'route';
		$_GET['a'] = 'n';
		$_GET['app_name'] = $q['app_name'];
		$_GET['app_org'] = $q['app_org'];
		$_GET['app_c'] = $q['c'];
		$_GET['app_a'] = $q['a'];
		$_GET = array_merge($query, $_GET);
		$_POST = $data;
		$_REQUEST = array_merge( $_REQUEST , $_GET, $_POST );
		$_FILES = $files;

		$route = new \coreAppRouteController();
		ob_start();
		$route->noframe();
		$content = ob_get_contents();
	    ob_clean();

	    $this->log->info("AppTask app_run 2", ['content'=>$content]);

	    if ( $content != null  ) {
	    	$resp = json_decode( $content, true );
	    	if ( isset($resp['result']) &&  
	    		 isset( $resp['content']) && 
	    		$resp['result'] === false ) {
	    		throw new Excp($resp['content'], 500);
	    	}

	    	return $resp;
	    }

	    return $content;
	}
}

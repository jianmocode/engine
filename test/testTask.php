<?php
require_once( "env.inc.php");
require_once( SEROOT  . DS . 'lib'. DS .'Utils.php');

use \Xpmse\Utils as Utils;

// 	$t = M("Task");
// 		$t->schedule();
// exit;

echo "\n \Xpmse\Model\Task 单元测试... \n\n\t";

class testTask extends PHPUnit_Framework_TestCase {

	function testRun() {

		$t = M('Task');
		$task_id = $t->run( "测试任务",
			["app_name" => "mina/pages", 'c'=>'article', 'a'=>'testrun', 'data'=>["id"=>10] ],
			function( $status, $task, $job_id, $queue_time, $resp  ) {
				$t = M("Task");
				$t->progress($task['task_id'], 100, "运行完成");
				Utils::out( $task['task_id'], "  ", $status );
			}
		);



		// $task_id = $que->addAppTask('mina/pages', 'article', 'uptowechat', ["data"=>["id"=>10]], 

		// 	function( $status, $task_id, $queue_time, $resp ) use( $t ){
		// 		echo "callback $task_id\n";
		// 		if ( $status == 'failure') {
		// 			Utils::out( $resp->toArray() );

		// 		} else if( $status == 'success' )  {
		// 			var_dump( $resp['message'] , $t);

		// 			// Utils::out("success\n", $resp );
		// 		}
		// 	}
		// );


	}


	function testRm() {
		$t = M('Task');
		$t->rm("测试计划任务", "mina/pages");
	}


	function testRegister(){
		$t = M('Task');
		$task_id = $t->register("测试计划任务", "*/1 * * * * *",
			["app_name" => "mina/pages", 'c'=>'article', 'a'=>'testregister', 'data'=>["id"=>110] ],
			function( $status, $task, $job_id, $queue_time, $resp  ) {
				$t = M("Task");
				$t->progress($task['task_id'], 100, "运行完成");
				Utils::out( $task['task_id'], "  ", $status );
			}
		);
	}


	function testIsRunning(){

		$t = M("Task");
		var_dump( $t->isRunning("测试计划任务", "mina/pages") );
	}




}


<?php
require_once(__DIR__ . '/env.php');
use \Xpmse\Que as Que;
use \Xpmse\Utils as Utils;

echo "\nXpmse\Que 测试... \n\n\t";

class testQue extends PHPUnit_Framework_TestCase {


	public function testAddAppTask() {
		$que = new Que;
		$t = time();
		$task_id = $que->addAppTask('mina/pages', 'article', 'uptowechat', ["data"=>["id"=>10]], 

			function( $status, $task_id, $queue_time, $resp ) use( $t ){
				echo "callback $task_id\n";
				if ( $status == 'failure') {
					Utils::out( $resp->toArray() );

				} else if( $status == 'success' )  {
					var_dump( $resp['message'] , $t);

					// Utils::out("success\n", $resp );
				}
			}
		);

		echo "$task_id start\n";
	}



	// /**
	//  * 测试添加任务
	//  * @return [type] [description]
	//  */
	// public function testAddHttpTaskOk() {

	// 	$que = new Que;
	// 	$task_id = $que->addHttpTask('GET', 'http://www.baidu.com/', ['datatype'=>'html']);
	// 	$status = $que->status($task_id);
	// 	$this->assertEquals( is_string($task_id), true );
	// }

	// public function testAddHttpTaskFail() {
		
	// 	$que = new Que('another');
	// 	$task_id = $que->addHttpTask('GET', 'http://www.baidu.com/');
	// 	$status = $que->status($task_id);
	// 	$this->assertEquals( is_string($task_id), true );
	// }


	/**
	 * 删除任务
	 */
	// public function testDelHttpTaskALL() {
		
	// 	$que = new Que;
	// 	$result = $que->delHttpTask();
	// 	$this->assertEquals( $result,  true );

	// 	$ret = $que->queues();
	// 	print_r($ret); 
	// }

	


}
<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Excp as Excp;
use \Xpmse\Utils as Utils;
use \Xpmse\Que as Que;
use \Xpmse\Task as Task;

class CoreSystemTaskController extends privateController {
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-settings', 'icontype'=>'si', 'cname'=>'系统选项']);
	}

	function running(){

		$que = new Que();
		$t = new Task();
		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;

		$live = $t->query()
				   ->where("status", '<>', TASK_DONE )
				   ->whereNull('schedule')
				   ->orderBy('status')
				   ->select(
				   		'task_id', 'job_id', 'name', 'app', 'schedule', 
				   		'progress_message as message', 'progress', 
				   		'response', 'response_code', 'created_at', 'status' )
				   ->pgArray(10, ['*'], '', $page);
		$data = [
			"query" =>["page"=>$page],
			"live" => $live,
			"workers" => $que->processed()
		];
		render( $data, 'core/system/web/task', 'running');
	}

	function clean(){
		Utils::cliOnly();
		$tab = Utils::getTab('task', 'core_');
		$tab->runsql('Truncate table core_task');
		$tab->runsql('Truncate table mina_pages_article_category');
		$tab->runsql('Truncate table mina_pages_article_draft');
		$tab->runsql('Truncate table mina_pages_article');
	}

	function schedule(){

		$que = new Que();
		$t = new Task();
		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;

		$live = $t->query()
				   ->whereNotNull("schedule")
				   ->orderBy('status')
				   ->select(
				   		'task_id', 'job_id', 'name', 'app', 'schedule', 
				   		'progress_message as message', 'progress', 
				   		'response', 'response_code', 'created_at', 'status' )
				   ->pgArray(10, ['*'], '', $page);
		$data = [
			"query" =>["page"=>$page],
			"schedule" => $live,
			"workers" => $que->processed()
		];
		render( $data, 'core/system/web/task', 'schedule');
	}

	function index() {
		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$this->_crumb('系统选项', R('baas-admin','data','index') );
	    $this->_crumb('任务管理');

	    $data = $this->_data([
	    	"query" =>[
	    		"page"=>$page
	    	]
	    ],'系统选项','任务管理');
		render( $data, 'core/system/web', 'task');
	}

}
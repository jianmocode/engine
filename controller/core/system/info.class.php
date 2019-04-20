<?php
include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Conf;
class CoreSystemInfoController extends privateController {
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'si-settings', 'icontype'=>'si', 'cname'=>'系统选项']);
	}

	function index() {

		$page  = (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$this->_crumb('系统选项', R('baas-admin','data','index') );
	    $this->_crumb('系统信息');

	    $sys = GetSysinfo();

	    $conf = [
	    	"cache"=>Conf::G('mem/redis'),
	    	"database"=>Conf::G('supertable/storage'),
	    	"storage"=>Conf::G('storage/local/bucket'),
	    	'log' => Conf::G('log/server/file'),
	    	"roots" => $sys['roots']
	    ];

	    $data = $this->_data([
	    	"sys" => $sys,
	    	"conf" => $conf,
	    	"_page" => '/info/index',
	    	"query" =>[
	    		"page"=>$page
	    	]
	    ],'系统选项','系统信息');

		render( $data, 'core/system/web', 'main');
    }
    

    function log(){
        $file = Conf::G('log/server/file');
        $max = 100;
        exec("tail -n {$max} {$file}", $response );
        $data["path"] = $file;
        $data["log"] = implode("\n",$response);
        render( $data, 'core/system/web/info', 'log');
    }
}
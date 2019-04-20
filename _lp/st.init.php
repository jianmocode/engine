<?php

if( !defined('AROOT') ) die('NO AROOT!');
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );


// define constant
define( 'IN' , true );

define( 'ROOT' , dirname( __FILE__ ) . DS );
define( 'CROOT' , ROOT . 'core' . DS  );
define( 'TROOT' , ROOT . 'simpletest' . DS  );


// define 
//error_reporting(E_ALL^E_NOTICE);
error_reporting(E_ALL&~E_NOTICE);
ini_set( 'display_errors' , true );

include_once( CROOT . 'lib' . DS . 'core.function.php' );
@include_once( AROOT . 'lib' . DS . 'app.function.php' );

include_once( CROOT . 'config' .  DS . 'core.config.php' );
// include_once( AROOT . 'config' . DS . 'app.config.php' );

require_once( TROOT . 'autorun.php');
require_once( TROOT . 'web_tester.php' );


$test = new TestSuite('点云网站单元测试');
$name = $_GET['f'];
if ($name == "" ) {

	foreach( glob( AROOT . 'test'. DS .'phptest' . DS . '*.test.php' ) as $f ) {
		echo "<pre>";
		print_r( $f );
	}

	echo "\n\n";
	print_r('请输入需要测试的模块 EG: ?f=pay');
	die();	
}


$test->addFile( AROOT . 'test'. DS .'phptest' . DS . "$name.test.php" );



//$test->run(new HtmlReporter('UTF-8'));
//$test->run(new HtmlReporter('UTF-8'));
unset( $test ); 




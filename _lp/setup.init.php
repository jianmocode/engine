<?php

if( !defined('AROOT') ) die('NO AROOT!');
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );

// define constant
define( 'IN' , true );

define( 'ROOT' , dirname( __FILE__ ) . DS );
define( 'CROOT' , ROOT . 'core' . DS  );

// define 
//error_reporting(E_ALL^E_NOTICE);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
ini_set( 'display_errors' , true );
ini_set('date.timezone','Asia/Shanghai');
include_once(__DIR__ . '/autoload.php' );

include_once( CROOT . 'lib' . DS . 'core.function.php' );
include_once( AROOT . 'lib' . DS . 'app.function.php' );
include_once( CROOT . 'config' .  DS . 'core.config.php' );
# include_once( AROOT . 'config' . DS . 'app.config.php' );


$n = $GLOBALS['n'] = v('n') ? v('n') : null;
$c = $GLOBALS['c'] = v('c') ? v('c') : 'setup';
$a = $GLOBALS['a'] = v('a') ? v('a') : 'install';

$n =  strtolower( z($n) );
$c =  strtolower( z($c) );
$a =  basename(strtolower( z($a) ));

$class_prefix = $filepath_prefix = '';
if ( $n !== "" ) {
	$namespace = explode('-', $n);
	$class_prefix = implode('', $namespace);
	$filepath_prefix = implode('/', $namespace) . DS;
}


$post_fix = '.class.php';
$c = urldecode($c);
$cont_file = AROOT . 'controller'  . DS . $filepath_prefix . $c . $post_fix;

$class_name = $class_prefix . basename($c) .'Controller' ; 
if( !file_exists( $cont_file ) )
{
	$cont_file = CROOT . 'controller' . DS . $c . $post_fix;
	if( !file_exists( $cont_file ) ) die('Can\'t find controller file - ' . $filepath_prefix . $c . $post_fix );
}

require_once( $cont_file );
if( !class_exists( $class_name ) ) die('Can\'t find class - '  .  $class_name );
if( !method_exists( $class_name , $a ) ) die('Can\'t find method - '   . $a . ' ');
if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE && @ini_get("zlib.output_compression")) ob_start("ob_gzhandler");

try {
	$o = new $class_name;
	call_user_func( array( $o , $a ) );

} catch ( Exception $e ) {
	echo "系统错误 \n";
	print_r( $e );
	die();
} 



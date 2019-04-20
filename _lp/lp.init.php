<?php
// 指定 SESSION 4 app & wxapp
if ( array_key_exists('_sid', $_REQUEST) && array_key_exists('_appid', $_REQUEST) ) {
	$session_id = !empty($_REQUEST['_sid']) ? trim($_REQUEST['_sid']) : null;
	if ( $session_id !== null ) {
		@session_id( $session_id );
	}
}

// Active Session
$_SESSION['__timing'] = time();

// ========================================================================


if( !defined('AROOT') ) die('NO AROOT!');
if( !defined('SEROOT') ) die('NO SEROOT!');
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );

// define constant
define( 'IN' , true );
define( 'ROOT' , __DIR__ . DS );
define( 'CROOT' , ROOT . 'core' . DS  );
define( 'LIB_ROOT' , realpath(__DIR__ . DS . '..') . DS . 'service' . DS . 'lib' . DS );

// PHP INIT SETTING
// error_reporting(E_ALL^E_NOTICE);
// error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set( 'display_errors' , true );

// error_reporting(0); // 自定义错误处理函数
// ini_set( 'display_errors' , false );

ini_set('date.timezone','Asia/Shanghai');
ini_set('memory_limit','128M');

include_once(__DIR__ . '/autoload.php' );
include_once( CROOT . 'lib' . DS . 'core.function.php' );
include_once( AROOT . 'lib' . DS . 'app.function.php' );
include_once( CROOT . 'config' .  DS . 'core.config.php' );


use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;

/**
 * 过滤 POST 数据 
 * 处理 nested 字段
 */

foreach ($_POST as $key => & $val ) {
	if ( strpos($key, '+') === 0 ) {

		if ( is_array($val) ) {
			foreach ($val as & $v ) {
				$v = is_string($v) ? json_decode( $v, true ) : $v;
			}
		} else {
			$val = is_string($val) ? json_decode( $val, true ) : $val;
		}

		unset($_POST[$key]);
		$_POST[substr($key, 1, strlen($key))] = $val;
	}
}



// /**
//  * PHP 错误处理 ??????
//  * @return [type] [description]
//  */
// function __php_error() { 
//     $error = error_get_last();
//     if ( !empty($error) ) {
// 	    $error['extra'] = $error;
// 	    $error['code'] = $error['type'] + 500;
// 	    echo json_encode($error);
//     }
// }

// register_shutdown_function("__php_error");

$n = $GLOBALS['n'] = v('n') ? v('n') : null;
$c = $GLOBALS['c'] = v('c') ? v('c') : c('default_controller');
$a = $GLOBALS['a'] = v('a') ? v('a') : c('default_action');

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
if( !class_exists( $class_name ) ) die(json_encode(['code'=>404,'message'=>'Can\'t find class - '  .  $class_name ]));



if( !method_exists( $class_name , $a ) ) die(json_encode(['code'=>404,'message'=>'Can\'t find method - '   . $a . ' ']));


if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE && @ini_get("zlib.output_compression")) ob_start("ob_gzhandler");

// 读取默认值
$GLOBALS['_defaults'] = Conf::G('defaults');

// 异常处理
$error_reporting_templete = tpl('common/web/error');
$error_reporting_svg = tpl('common/web/error.svg');


try {
	$o = new $class_name;
	call_user_func( array( $o , $a ) );
	
} catch( Excp $e ) {
    
    $ut = new Utils;
    $e->log();
    $type = $ut->getRespType();
    if( in_array($type, ['application/json', "application/api","application/noframe", "application/portal"] ) ) {
        Utils::out( $e->toArray());
        return ;
    } else if ( $type == 'application/image') { 
        header('Content-type:image/svg+xml');
        $e->render( $error_reporting_svg );
    } else {
        $e->render( $error_reporting_templete );
    }

} catch ( Exception $e ) {

	$ut = new Utils;
	$type = $ut->responseType();
	Excp::elog($e);

	if( in_array($type,['application/json', "application/api","application/noframe", "application/portal"]  ) ) {
		Utils::out( Excp::etoArray($e) );
	} else if ( $type == 'application/image') { 
		header('Content-type:image/svg+xml');
		Excp::erender( $e, $error_reporting_svg );
	} else {
		Excp::erender($e, $error_reporting_templete);
	}
	
}



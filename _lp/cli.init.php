<?php

/* lp app root */
// ↑____ for aoi . Do Not Delete it.
/****  load lp framework  ***/


if( !defined('AROOT') ) die('NO AROOT!');
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );


define( 'ROOT', AROOT . "_lp" . DS );

if( !defined('AROOT') ) die('NO AROOT!');
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );


//error_reporting(E_ALL^E_NOTICE);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
ini_set( 'display_errors' , true );


// define constant
define( 'IN' , true );
define( 'ROOT' , '.' . DS );
define( 'CROOT' , ROOT . 'core' . DS  );

include_once(__DIR__ . '/autoload.php' );



include_once( CROOT . 'lib' . DS . 'core.function.php' );
include_once( AROOT . 'lib' . DS . 'app.function.php' );
include_once( CROOT . 'config' .  DS . 'core.config.php' );


/****  load lp framework  END  ***/






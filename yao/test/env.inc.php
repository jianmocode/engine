<?php
$ROOT =  realpath(dirname( __FILE__ ) . '/../../');
define( 'AROOT',  $ROOT);
define( 'SEROOT', "{$ROOT}/service");
define( 'LPROOT', "{$ROOT}/_lp/" );
define( 'CROOT', "{$ROOT}/_lp/core/");
define( 'DS' , DIRECTORY_SEPARATOR );
define( 'IN' , true );
include_once(LPROOT . '/autoload.php' );
include_once ( "{$ROOT}/lib/app.function.php" );
<?php
define( 'AROOT',  dirname( __FILE__ ) . '/../../../');
define( 'SEROOT',  dirname( __FILE__ ) . '/../../../service');
define( 'LPROOT',  dirname( __FILE__ ) . '/../../../_lp' );
define( 'CROOT',  dirname( __FILE__ ) . '/../../../_lp/core');
define( 'DS' , DIRECTORY_SEPARATOR );
define( 'IN' , true );
define( '_XPMAPP_ROOT', '/apps');

include_once(LPROOT . '/autoload.php' );
include_once ( __DIR__ . '/../../../lib/app.function.php' );
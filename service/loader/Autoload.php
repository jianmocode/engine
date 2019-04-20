<?php
if( !defined('DS') ) define( 'DS' , DIRECTORY_SEPARATOR );



spl_autoload_register(function ($class_name ) {

	$class_arr = explode( '\\', $class_name );
	$namespace = current($class_arr);

	if ( $namespace == 'Xpmse' ) {
		if ( isset( $class_arr[1]) &&  $class_arr[1] == "Loader" ) {
			$LIB_ROOT = __DIR__;
		} else {
			$LIB_ROOT = __DIR__ . DS . '..' . DS . 'lib';
		}
		
		$class = end($class_arr);
		$class_file = ucfirst(strtolower($class));
		$class_path_file = $LIB_ROOT . DS . $class_file . '.php';
		include_once( $LIB_ROOT . DS. $class_file . '.php' );

	} else if ( $namespace == 'Mina'&& is_string($class_arr[1]) 
		        && in_array($class_arr[1], ['Storage', 'Template', 'Cache', 'Router', 'Delta']) ) {

		$MINA_ROOT = realpath(__DIR__ . DS . '..'. DS . '..' . DS . 'mina');
		$CLASS_ROOT = $MINA_ROOT . DS . strtolower($class_arr[1]);
		$autoload = $CLASS_ROOT . DS . "vendor" . DS . 'autoload.php';
		
		$class = end($class_arr);
		$class_file = ucfirst(strtolower($class)) . '.php';
		$class_path_file = $CLASS_ROOT . DS . 'src' . DS. $class_file;

		if ( file_exists($autoload) ) {
			include_once($autoload);
		}
		include_once($class_path_file);
		
	}  else  {

		$APP_ROOT = _XPMAPP_ROOT;
		$class = end($class_arr);
		array_pop($class_arr);
		
		$class_file = ucfirst(strtolower($class));
		$class_path = strtolower(implode(DS, $class_arr));
		$class_path_file = $APP_ROOT . DS . $class_path . DS . $class_file . '.php';

		if ( file_exists($class_path_file) ) {
			include_once($class_path_file);
		}
	}

});

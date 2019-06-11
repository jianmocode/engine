<?php
if ( !defined("_XPMAPP_ROOT")){
    define("_XPMAPP_ROOT", "/apps");
}

// 载入YaoJS Backend 配置
$GLOBALS["YAO"] = require_once(__DIR__ . "/../yao/config.inc.php");

spl_autoload_register(function ($class_name ) {

	$class_arr = explode( '\\', $class_name );
		
	$namespace  = current($class_arr);

	// MINA SDK 
	if ( strtolower($namespace) == 'mina' && is_string($class_arr[1]) 
		        && in_array($class_arr[1], ['Storage', 'Template', 'Cache', 'Router', 'Delta', 'Gateway']) ) {

		$MINA_ROOT = __DIR__ . DS . '..' . DS . 'mina';
		$CLASS_ROOT = $MINA_ROOT . DS . strtolower($class_arr[1]);
		$autoload = $CLASS_ROOT . DS . "vendor" . DS . 'autoload.php';
		
		$class = end($class_arr);
		$class_file = ucfirst(strtolower($class)) . '.php';
		$class_path_file = $CLASS_ROOT . DS . 'src' . DS. $class_file;

		if ( file_exists($autoload) ) {
			include_once($autoload);
		}
		include_once($class_path_file);
        return;
        
    // Yao SDK
    } else if ( strtolower($namespace) == 'yao') {
        
        $YAO_ROOT = __DIR__ . DS . '..' . DS . strtolower($class_arr[0]);
        
        // Vendor autoload
        $autoload = $YAO_ROOT . DS . "vendor" . DS . 'autoload.php';
        if ( file_exists($autoload) ) {
			include_once($autoload);
        }
        
        // Class Name
        $class = array_pop($class_arr);
        array_shift( $class_arr);

        // Source Path
        $path = strtolower(implode(DS, $class_arr));
        $src_path = !empty($path) ? "src" . DS . $path : "src";
        $class_file = ucfirst(strtolower($class)) . '.php';
        $class_path_file = $YAO_ROOT . DS . $src_path . DS . $class_file;
        
        include_once($class_path_file);
        return;
    
    // Xpmse SDK
	} else if (  strtolower($namespace) == 'xpmse') {
        $class = end($class_arr);

		if ( isset( $class_arr[1]) &&  ucfirst(strtolower($class_arr[1])) == "Loader" ) {
			$LIB_ROOT = realpath(__DIR__ . DS . '..' . DS . 'service'. DS .'loader');
            // echo $LIB_ROOT . "\n";
        } else if ( isset( $class_arr[2]) &&  ucfirst(strtolower($class_arr[1])) == "Datadriver" ) {
            $LIB_ROOT = realpath(__DIR__ . DS . '..' . DS . 'service/lib/data-driver');
		} else if ( isset( $class_arr[2]) &&  ucfirst(strtolower($class_arr[1])) == "Model" ) {
			$LIB_ROOT = realpath(__DIR__ . DS . '..' . DS . 'model');
            // echo $LIB_ROOT;
        } else if (  ucfirst(strtolower($class_arr[1])) == "Xpmse"  &&  ucfirst(strtolower($class_arr[2])) == "Api" ) {
            $LIB_ROOT = realpath(__DIR__ . DS . '..' . DS . 'api');
            
		}else {
			$LIB_ROOT = realpath(__DIR__ . DS . '..' . DS . 'service'. DS .'lib');
			// echo $LIB_ROOT . "\n";
		}
        
		$class_file = ucfirst(strtolower($class));
		$class_path_file = $LIB_ROOT . DS . $class_file . '.php';
		include_once( $class_path_file );

    // 载入其他文件
	} else  {

		$APP_ROOT = _XPMAPP_ROOT;
		$class = end($class_arr);
        array_pop($class_arr);
        
        // 添加 model 目录
        if ( count($class_arr) == 2 ) {
            array_push( $class_arr, "model");
        }
        
		$class_file = ucfirst(strtolower($class));
		$class_path = strtolower(implode(DS, $class_arr));
		$class_path_file = $APP_ROOT . DS . $class_path . DS . $class_file . '.php';
      
		if ( file_exists($class_path_file) ) {
			include_once($class_path_file);
		}
	}

	return ;

});
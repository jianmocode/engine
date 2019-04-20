<?php

//修改为MySQL Li
//兼容主从查询 
//带参数连接可能会有BUG


// db functions
function db( $host = null , $port = null , $user = null , $password = null , $db_name = null )
{
	// $db_key = MD5( $host .'-'. $port .'-'. $user .'-'. $password .'-'. $db_name  );
	$db_key = "DB";

	if( !isset( $GLOBALS['LP_'.$db_key] ) )
	{
		// include_once( AROOT .  'config/db.config.php' );
		//include_once( CROOT .  'lib/db.function.php' );
		
		$db_config = $GLOBALS['config']['db'];
		
		if( $host == null ) $host = $db_config['db_host'];
		if( $port == null ) $port = $db_config['db_port'];
		if( $user == null ) $user = $db_config['db_user'];
		if( $password == null ) $password = $db_config['db_password'];
		if( $db_name == null ) $db_name = $db_config['db_name'];
		
		if( !$GLOBALS['LP_'.$db_key] = mysqli_connect( $host, $user , $password , $db_name, $port ) )
		{
			//
			echo 'can\'t connect to database';
			return false;
		}
		
		mysqli_query(  $GLOBALS['LP_'.$db_key] , "SET NAMES 'UTF8'" );
	}
	
	return $GLOBALS['LP_'.$db_key];
}


function db_read( $host = null , $port = null , $user = null , $password = null , $db_name = null )
{
	// $db_key = MD5( $host .'-'. $port .'-'. $user .'-'. $password .'-'. $db_name  );
	$db_key = "DB";

	if( !isset( $GLOBALS['LP_READ_'.$db_key] ) )
	{
		include_once( AROOT .  'config/db.config.php' );
		//include_once( CROOT .  'lib/db.function.php' );
		
		$db_config = $GLOBALS['config']['db'];

		// 如果未设置读库，则连接主库
		if( !isset($db_config['db_host_read']) ) $db_config['db_host_read'] = $db_config['db_host'];
		if( !isset($db_config['db_port_read']) ) $db_config['db_port_read'] = $db_config['db_port'];
		if( !isset($db_config['db_user_read']) ) $db_config['db_user_read'] = $db_config['db_user'];
		if( !isset($db_config['db_password_read']))  $db_config['db_password_read'] = $db_config['db_password'];


		if( $host == null ) $host = $db_config['db_host_read'];
		if( $port == null ) $port = $db_config['db_port_read'];
		if( $user == null ) $user = $db_config['db_user_read'];
		if( $password == null ) $password = $db_config['db_password_read'];

		if( $db_name == null ) $db_name = $db_config['db_name'];
		
		if( !$GLOBALS['LP_READ_'.$db_key] = mysqli_connect( $host, $user , $password , $db_name, $port ) )
		{
			//
			echo 'can\'t connect to Read Database';
			return false;
		}
		
		mysqli_query(  $GLOBALS['LP_READ_'.$db_key] , "SET NAMES 'UTF8'" );
	}
	
	return $GLOBALS['LP_READ_'.$db_key];
}





function s( $str , $db = NULL )
{
	if( $db == NULL ) $db = db();
	return   mysqli_real_escape_string(  $db, $str )  ;
	
}

// $sql = "SELECT * FROM `user` WHERE `name` = ?s AND `id` = ?i LIMIT 1 "
function prepare( $sql , $array )
{
	if(!is_array($array)) $array = array($array);
	foreach( $array as $k=>$v )
		$array[$k] = s($v );
	
	$reg = '/\?([is])/i';
	$sql = preg_replace_callback( $reg , 'prepair_string' , $sql  );
	$count = count( $array );
	for( $i = 0 ; $i < $count; $i++ )
	{
		$str[] = '$array[' .$i . ']';	
	}
	
	$statement = '$sql = sprintf( $sql , ' . join( ',' , $str ) . ' );';
	eval( $statement );
	return $sql;
	
}

function prepair_string( $matches )
{
	if( $matches[1] == 's' ) return "'%s'";
	if( $matches[1] == 'i' ) return "'%d'";	
}


function get_data( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db_read();

	$GLOBALS['LP_LAST_SQL'] = $sql;
	$GLOBALS['LP_LAST_SQL_IS_READ'] = true;

	$data = Array();
	$i = 0;
	$result = mysqli_query( $db, $sql );
	
	//if( mysqli_errno($db) != 0 ) echo mysqli_error($db) .' ' . $sql;
	
	while( $Array = @mysqli_fetch_array($result, MYSQL_ASSOC ) )
	{
		$data[$i++] = $Array;
	}
	
	//if( mysqli_errno($db) != 0 ) echo mysqli_error($db) .' ' . $sql;
	
	@mysqli_free_result($result); 

	return $data;
}

function get_line( $sql , $db = NULL )
{
	$data = get_data( $sql , $db  );
	return @reset($data);
}

function get_var( $sql , $db = NULL )
{
	$data = get_line( $sql , $db );
	return $data[ @reset(@array_keys( $data )) ];
}

function last_id( $db = NULL )
{
	if( $db == NULL ) $db = db();
	return get_var( "SELECT LAST_INSERT_ID() " , $db );
}

function run_sql( $sql , $db = NULL )
{
	if( $db == NULL ) $db = db();
	$GLOBALS['LP_LAST_SQL'] = $sql;
	$GLOBALS['LP_LAST_SQL_IS_READ'] = false;

	return mysqli_query(  $db, $sql );
}

function db_errno( $db = NULL )
{
	if( $db == NULL ) {
		if ( $GLOBALS['LP_LAST_SQL_IS_READ'] ) {
			$db = db_read();	
		} else {
			$db = db();
		}
	}

	return mysqli_errno( $db );
}


function db_error( $db = NULL )
{
	if( $db == NULL ) {
		if ( $GLOBALS['LP_LAST_SQL_IS_READ'] ) {
			$db = db_read();	
		} else {
			$db = db();
		}
	}
	return mysqli_error( $db );
}

function last_error()
{
	if( isset( $GLOBALS['LP_DB_LAST_ERROR'] ) )
	return $GLOBALS['LP_DB_LAST_ERROR'];
}

function close_db( $db = NULL )
{
	if( $db == NULL ) {

		if ( isset( $GLOBALS['LP_DB'] )) {
			unset( $GLOBALS['LP_DB'] );
			mysqli_close( $GLOBALS['LP_DB'] );
		}

		if ( isset( $GLOBALS['LP_READ_DB'] )) {
			unset( $GLOBALS['LP_READ_DB'] );
			mysqli_close( $GLOBALS['LP_READ_DB'] );
		}
	}

	mysqli_close($db);
}

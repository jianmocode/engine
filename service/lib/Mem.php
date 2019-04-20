<?php

namespace Xpmse;
require_once( __DIR__ . '/Inc.php');

use \Exception as Exception;
use \Redis as Redis;

/**
 * XpmSE内存缓存 ( Base On Redis )
 */
class Mem {

	private $redis = null;
    private $prefix = null;
    private static $redis_instance = null;
	
	/**
	 * 构造函数
	 * @param boolen $namespace  是否启用NameSpace
	 * @param string $prefix [description]
	 * @param array  $server [description]
	 */
	function __construct( $namespace=false, $prefix='XPMSE:', $server=[] ) {

		$name = "core";
		$path_info = dirname($_SERVER['SCRIPT_FILENAME']);
		if ( strpos($path_info, _XPMAPP_ROOT) !== false  && $namespace === true ) {
			$path =  str_replace(_XPMAPP_ROOT . '/', '',  $path_info);	
			$info = explode('/', $path);
			$name = $info[0];  // APP NAME
		}

        // static redis_instance
        if ( self::$redis_instance === null ) {
            self::$redis_instance = new Redis();
        }

		$this->redis = self::$redis_instance;
		// $this->prefix = "_{$name}:$prefix";
		$this->prefix = (defined("__VHOST_NAME") &&  !empty(__VHOST_NAME) ) ? __VHOST_NAME . ":_{$name}:$prefix" : "_{$name}:$prefix" ;

        // (defined("__VHOST_NAME") &&  !empty(__VHOST_NAME) ) ?  __VHOST_NAME . ":{$this->options['prefix']}" : "{$this->options['prefix']}";

		$server = (is_array($server))? $server : [];
			$host = (isset($server['host']))? $server['host'] : _XPMSE_REDIS_HOST;
			$port = (isset($server['port']))? $server['port'] : _XPMSE_REDIS_PORT;
			$timeout = (isset($server['timeout']))? $server['timeout'] : 1.0;
			$retry = (isset($server['retry']))? $server['retry'] : 500;
			$socket = (isset($server['socket']))? $server['socket'] : _XPMSE_REDIS_SOCKET;
			$passwd = (isset($server['passwd']))? $server['passwd'] : _XPMSE_REDIS_PASSWD;
			$db = intval(isset($server['db']) ? $server['db'] : _XPMSE_REDIS_DB);
			if ( empty($db) ) { $db = 1; }
			
		$ret = true;
		if ( !empty($socket) ) {
			$ret = $this->redis->connect($socket);
		} else {
            try {
                $ret = $this->redis->connect($host, $port, $timeout, NULL, $retry);
            } catch( \RedisException $e ) {
                // $excp = new Excp( $e->getMessage(), 500 );
                // $excp->log();
                $ret = false;
            }
		}
		if ( !empty($passwd) ) {
			try { $ret = $this->redis->auth($passwd); } catch( \RedisException $e ) {
                // $excp = new Excp( $e->getMessage(), 500 );
                // $excp->log();
                $ret = false;
            }
		}

		if ( !$ret ) {
			$this->redis = null;
			$this->prefix = null;
		} else {
			$this->redis->select( $db );
			// if ($_GET['debug'] == "1" ) {
			// 	echo "mem:: prefix={$this->prefix} db={$db} \n";
			// }
		}
    }
    
    public function __destruct() {
        if ( $this->redis ) {
            // $excp = new Excp( "Close Redis connect", 500 );
            // $excp->log();
            $this->redis->close();
            unset( $this->redis );
        }
    }

	/**
	 * 读取Redis链接信息
	 * @return [type] [description]
	 */
	public function redis() {
		return $this->redis;
	}


	/**
	 * 检查服务器是否正常
	 * @return [type] [description]
	 */
	public function ping(){

		if ( empty($this->redis) ) return false;
		return $this->redis->ping();
	}


	/**
	 * 从内存中读取数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function get( $key ) {
		if ( empty($this->redis) ) return false;
		return $this->redis->get("{$this->prefix}{$key}");
	}

	/**
	 * 从集合中读取数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function sMembers( $key ) {
		if ( empty($this->redis) ) return false;
		return $this->redis->sMembers("{$this->prefix}{$key}");
	}


	/**
	 * 从内存中读取数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function keys( $key='*' ) {
		if ( empty($this->redis) ) return false;
		return $this->redis->keys("{$this->prefix}{$key}");
	}



	/**
	 * 添加一个值，如果已经存在，则覆写
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function set( $key, $value, $expires=0, $flag=false ){
		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$key}", $expires, $value );
		return $this->redis->set("{$this->prefix}{$key}", $value);
	}

	/**
	 * 集合sMembers
	 * 添加一个唯一值，如果已经存在，false
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function sAdd( $key, $value, $expires=0, $flag=false ){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$key}", $expires, $value );
		return $this->redis->sAdd("{$this->prefix}{$key}", $value);
	}

	/**
	 * hash字典
	 * 设置hash key=>value，如果已经存在 替换原有值，false 
	 * @param [type]  $name    hash名
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function hset( $name, $key, $value, $expires=0, $flag=false ){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		return $this->redis->hset("{$this->prefix}{$name}", $key, $value);
	}
	/**
	 * hash字典
	 * 删除hash key=>value，如果已经存在 替换原有值，false 
	 * @param [type]  $name    hash名
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function hdel( $name, $key, $expires=0, $flag=false ){

		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		
		return $this->redis->hdel("{$this->prefix}{$name}", $key);
	}
	/**
	 * list
	 * list key=>value，入站，false 
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function lpush($key, $value, $expires=0, $flag=false ){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		return $this->redis->lpush("{$this->prefix}{$key}", $value);
	}
	/**
	 * list 删除队列中指定值
	 * list key=>value，，false 
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $count   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function lrem($key, $value, $expires=0, $flag=false ,$count=0){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		return $this->redis->lrem("{$this->prefix}{$key}",  $value , $count);
	}
	/**
	 * list 查看队列中所有值
	 * list key=>value，，false 
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $count   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function lrange($key, $expires=0, $flag=false){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		return $this->redis->lrange("{$this->prefix}{$key}",  "0" , "-1");
	}
	/**
	 * 获取hash中某个key的值
	 * 设置hash key=>value，如果已经存在 替换原有值，false 
	 * @param [type]  $name    hash名
	 * @param [type]  $key     键值
	 * @param [type]  $value   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function hget( $name, $key, $expires=0, $flag=false ){


		if ( empty($this->redis) ) return false;

		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$name}", $expires, $value );
		return $this->redis->hget("{$this->prefix}{$name}", $key);
	}
	/**
	 * 从内存中读取JSON格式数据
	 * @param  [type] $key 键值
	 * @return [mix] 成功返回数值， 失败返回false
	 */
	public function getJSON( $key ) {
		$json_text = $this->get($key);
		if ( $json_text === false ) return false;
		if ( json_last_error()  != JSON_ERROR_NONE ) return false;

		$json_data = json_decode($json_text,true);
		return $json_data;
	}
	


	/**
	 * JSON编码后，添加一个数值，如果已经存在，则覆写
	 * @param [type]  $key     键值
	 * @param [type]  $json_data   数值
	 * @param [type]  $expires  存储值的过期时间，如果为0表示不会过期。 默认为0。 你可以用unix时间戳或者描述来表示从现在开始的时间，但是你在使用秒数表示的时候，不要超过2592000秒 (表示30天)
	 * @param  boolean $flag    是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。默认为false ( 废弃 )
	 * @return [boolean] 如果成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function setJSON( $key, $json_data, $expires=0, $flag=false ){
		$json_text = json_encode($json_data);
		return $this->set($key, $json_text, $expires, $flag );
	}





	/**
	 * 删除一个键值 
	 * @param  [type]  $key     键值
	 * @param  integer $timeout 设置的秒数以后过期 默认为0
	 * @return [bool]   成功则返回 TRUE，失败则返回 FALSE。
	 */
	public function del( $key, $timeout=0 ) {
		if ( empty($this->redis) ) return false;

		if ( $timeout > 0 ) {
			return $this->redis->setTimeout("{$this->prefix}{$key}", $timeout);
		} 

		$ret = $this->redis->delete("{$this->prefix}{$key}");
		return ( $ret === 1 )? true  : false;
	}


	/**
	 * 删除一组键值
	 */
	public function delete( $keys ){
		if ( empty($this->redis) ) return false;
		$keylist = $this->redis->keys("{$this->prefix}{$keys}*");
		// echo "{$this->prefix}|{$keys}*\n";
		// print_r( $keylist );
		return $this->redis->delete($keylist);
	}


	/**
	 * 清空一组键值
	 * @param  array  $cacheList [description]
	 * @return [type]            [description]
	 */
	public function clean( $cacheList = [] ) {
		$cacheList = (is_array($cacheList)) ? $cacheList : [$cacheList];
		$result = true;
		$error = [];
		foreach ($cacheList as $cache ) {
			if ( $this->delete($cache) === false) {
				if ( $this->del($cache) === false) {
					$result = false;
					$error[] = $cache;
				}
			}
		}
		return ['ret'=>$result, 'error'=>$error];
	}

}

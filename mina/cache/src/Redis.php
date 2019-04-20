<?php
/**
 * MINA Pages Redis 缓存
 * 
 * @package      \Mina\Cache
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Cache;
use Mina\Cache\Base;
use \Exception;

class Redis extends Base {

    private static $redis_instance = null;
	private $redis = null;


	/**
	 * Redis 缓存
	 * @param array $options 配置选项
	 *      	string  ["prefix"] 缓存前缀，默认为空
	 *      	string  ["socket"] Redis 服务器 Socket 文件,  默认为空
	 *      	string  ["host"] Redis 服务器地址  默认 "127.0.0.1"
	 *      	   int  ["port"] Redis 端口 默认 6379
	 *      	string	["passwd"] Redis 鉴权密码 默认为 null
	 *      	   int  ["db"] Redis 数据库 默认为 1
	 *      	   int  ["timeout"] Redis 超时时间, 单位秒默认 10
	 *      	   int	["retry"] Redis 链接重试次数, 默认 3
	 * 
	 */
	function __construct( $options = [] ) {

        parent::__construct( $options );
        
        // static redis_instance
        if ( self::$redis_instance === null ) {
            self::$redis_instance = new \Redis();
        }

		$this->redis = self::$redis_instance;

		$isConnected = true;
		if ( isset( $this->options['socket']) ) {
			$isConnected = $this->redis->connect($this->options['socket']);
		} else {

			$this->options['host'] = !empty($this->options['host']) ? $this->options['host']: "127.0.0.1";
			$this->options['port'] = !empty($this->options['port']) ? $this->options['port']: 6379;
			$this->options['timeout'] = !empty($this->options['timeout']) ? $this->options['timeout']: 10;
			$this->options['retry'] = !empty($this->options['retry']) ? $this->options['retry']: 3;

            try {
                
            
                $isConnected = $this->redis->connect(
                    $this->options['host'], 
                    $this->options['port'], 
                    $this->options['timeout'], NULL, 
                    $this->options['retry'] );
            } catch( \RedisException $e ) {
                $isConnected = false;
            }
		}

		// passwd set
		if ( !empty($this->options['passwd'] ) ) {
            
            try { $isConnected = $this->redis->auth($this->options['passwd']); } catch( \RedisException $e ) {
                // $excp = new \Xpmse\Excp( $e->getMessage(), 500 );
                // $excp->log();
                $isConnected = false;
            }
		}

		// password set
		if ( !empty($this->options['password'] ) ) {
            
            try { $isConnected = $this->redis->auth($this->options['password']); } catch( \RedisException $e ) {
                // $excp = new \Xpmse\Excp( $e->getMessage(), 500 );
                // $excp->log();
                $isConnected = false;
            }
		}

		// auth set
		if ( !empty($this->options['auth'] ) ) {
            try { $isConnected = $this->redis->auth($this->options['auth']); } catch( \RedisException $e ) {
                // $excp = new \Xpmse\Excp( $e->getMessage(), 500 );
                // $excp->log();
                $isConnected = false;
            }
		}

		if ( !$isConnected ) {
			$this->redis = null;
			$this->prefix = null;
		} else {
			$this->options['db'] = !empty($this->options['db']) ? intval($this->options['db'])  : 1;
			$this->redis->select( $this->options['db'] );
        }
        

        // echo "\n{$this->prefix}\n";
	}

	public function ping(){
		if ( empty($this->redis) ) return false;
		return $this->redis->ping();
	}

	public function get( $key ) {
		if ( empty($this->redis) ) return false;
		return $this->redis->get("{$this->prefix}{$key}");
	}

	public function set( $key, $value, $expires=0  ){
		if ( empty($this->redis) ) return false;
		if ( $expires > 0 ) return $this->redis->setEx("{$this->prefix}{$key}", $expires, $value );
		return $this->redis->set("{$this->prefix}{$key}", $value);
	}

	public function del( $key, $timeout=0 ) {
		if ( empty($this->redis) ) return false;

		if ( $timeout > 0 ) {
			return $this->redis->setTimeout("{$this->prefix}{$key}", $timeout);
		} 

		$ret = $this->redis->delete("{$this->prefix}{$key}");
		return ( $ret === 1 )? true  : false;
	}

	public function delete( $keys ){
		if ( empty($this->redis) ) return false;
		$keylist = $this->redis->keys("{$this->prefix}{$keys}*");
		return $this->redis->delete($keylist);
    }
    
    public function keys( $keys="" ) {
        if ( empty($this->redis) ) return false;
        $keys = $this->redis->keys("{$this->prefix}{$keys}*");
        if (empty($keys)){
            return [];
        }
        array_walk( $keys, function(&$key){
            $key = str_replace("{$this->prefix}", "", $key);
        });

        return $keys;
    }

}


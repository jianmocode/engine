<?php
/**
 * MINA Pages 缓存基类
 * 
 * @package      \Mina\Cache
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Cache;
use Mina\Cache\Obj as MinaObject;

class Base implements MinaObject {

	protected $options;
	protected $prefix;

	function __construct( $options = [] ) {
		$this->options = $options;
		$this->options['prefix'] = !empty($options['prefix']) ? $options['prefix'] : "";
        $this->prefix = (defined("__VHOST_NAME") && !empty(__VHOST_NAME) ) ?  __VHOST_NAME . ":{$this->options['prefix']}" : "{$this->options['prefix']}";
        
	}

	/**
	 * 如采用缓存服务，返回服务API对象
	 * @return mix 
	 */
	function inst(){}


	/**
	 * 检查服务器是否正常工作
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	function ping() { return false ;}


	/**
	 * 从缓存中读取数据
	 * @param  string $key 键值
	 * @return 成功返回数值, 失败返回false
	 */
	public function get( $key ) {  return false ;}


	/**
	 * 添加一个值，如果已经存在，则覆写
	 * @param string  $key     键值
	 * @param string  $value   数值
	 * @param int  $ttl  缓存过期时间，如果为0表示不会过期。 默认为0。 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function set( $key, $value, $ttl=0 ){  return false ; }

	/**
	 * 删除一个键值 
	 * @param  string $key     键值
	 * @param  string $timeout 延时多久后删除 默认为0
	 * @return boolean   成功返回 TRUE，失败返回 FALSE。
	 */
	public function del( $key, $timeout=0 ){  return false ;}


	/**
	 * 从缓存中读取数据，并进行 JSON 解码操作
	 * @param  string $key 键值
	 * @return 成功返回数值, 失败返回false
	 */
	public function getJSON( $key ) {
		
        $json_text = $this->get($key);
        if ( $json_text === false ) {
            return false;
        }

        $json_data = json_decode($json_text,true);
		if ( json_last_error()  != JSON_ERROR_NONE ) {
            return false;
        }
		return $json_data;
	}


	/**
	 * 添加一个值，并对其JSON编码，如果已经存在，则覆写
	 * @param string  $key     键值
	 * @param string  $value   数值
	 * @param int  $ttl  缓存过期时间，如果为0表示不会过期。 默认为0。 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function setJSON( $key, $json_data, $ttl=0 ) {
		$json_text = json_encode($json_data);
		return $this->set($key, $json_text, $ttl );
	}


	/**
	 * 使用通配符 删除键值
	 * @param  string $key 键值 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function delete( $key ){  return false ; }


	/**
	 * 删除一组键值
	 * @param  array $keys 键值列表
	 * @return array ['code'=>成功返回0，失败返回错误数量, 'error'=>成功返回空数组,失败返回失败的键列表]
	 */
	public function clean( $keys = [] ){
		$keys = (is_array($keys)) ? $keys : [$keys];
		$result = 0;
		$error = [];
		foreach ($keys as $key ) {
			if ( $this->delete($key) === false) {
				if ( $this->del($key) === false) {
					$result++;
					$error[] = $key;
				}
			}
		}
		return ['code'=>$result, 'error'=>$error];
	}

}
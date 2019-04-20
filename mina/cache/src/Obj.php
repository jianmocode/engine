<?php
/**
 * MINA Pages 缓存接口
 * 
 * @package      \Mina\Cache
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Cache;

interface Obj {

	/**
	 * 如采用缓存服务，返回服务API对象
	 * @return mix
	 */
	function inst(); 


	/**
	 * 检查服务器是否正常工作
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	function ping();

	/**
	 * 从缓存中读取数据
	 * @param  string $key 键值
	 * @return 成功返回数值, 失败返回false
	 */
	public function get( $key );


	/**
	 * 添加一个值，如果已经存在，则覆写
	 * @param string  $key     键值
	 * @param string  $value   数值
	 * @param int  $ttl  缓存过期时间，如果为0表示不会过期。 默认为0。 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function set( $key, $value, $ttl=0 );

	/**
	 * 删除一个键值 
	 * @param  string $key     键值
	 * @param  string $timeout 延时多久后删除 默认为0
	 * @return boolean   成功返回 TRUE，失败返回 FALSE。
	 */
	public function del( $key, $timeout=0 );


	/**
	 * 从缓存中读取数据，并进行 JSON 解码操作
	 * @param  string $key 键值
	 * @return 成功返回数值, 失败返回false
	 */
	public function getJSON( $key );


	/**
	 * 添加一个值，并对其JSON编码，如果已经存在，则覆写
	 * @param string  $key     键值
	 * @param string  $value   数值
	 * @param int  $ttl  缓存过期时间，如果为0表示不会过期。 默认为0。 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function setJSON( $key, $json_data, $ttl=0 );


	/**
	 * 使用通配符 删除键值
	 * @param  string $key 键值 
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	public function delete( $key );


	/**
	 * 删除一组键值
	 * @param  array $keys 键值列表
	 * @return boolean | array 成功返回 TRUE，失败返回未成功删除的数据列表
	 */
	public function clean( $keys = [] );

}
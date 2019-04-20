<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Mina\Pages\Api\Article;
use \Xpmse\T; 


/**
 * 本程序应该只允许后端用户访问
 */
class minaHelperController extends minaBaseController {


	private $option = [];

	function __construct() {
	}


	/**
	 * Select2 Option 选择器 ( 每页显示30个 )
	 * @param $_GET['page']  当前页码 
	 * @param $_GET['q'] 查询关键词
	 * @param $_GET['model'] 数据模型
	 * @param $_GET['table'] 数据表名称
	 * @param $_GET['fields'] 呈现字段 (多个用','分割)
	 * @param $_GET['style'] 字段呈现样式
	 * @param $_GET['option'] 数值字段
	 * @param $_GET['default'] 默认值
	 * @param $_GET['preload'] 预载入数值 ( 已填写数据 )
	 * 
	 * @return JSON String  
	 * @see https://select2.org/data-sources/formats
	 *         {
	 *         		"items": [
	 *         			{"id":"<option value>", "text": "<option name>"}
	 *         			...
	 *         		],
	 *         		"total":100
	 *         }
	 */
	function select2() {

		$page = empty($_GET['page']) ? 1: intval($_GET['page']); // 页码
		$keyword = empty($_GET['q']) ? "" : trim($_GET['q']); // 关键词
		$model = empty($_GET['model']) ? "" : trim($_GET['model']); // 数据模型
		$option = empty($_GET['option']) ? "" : trim($_GET['option']); // 数值字段
		$fields = empty($_GET['fields']) ? $option : trim($_GET['fields']); // 呈现字段
		$style = empty($_GET['style']) ? "{{{$option}}}" : trim($_GET['style']); // 字段呈现样式
		$preload = trim($_GET['preload']);
		$default = empty($_GET['default']) ? "" : trim($_GET['default']); // 默认值

		$cancelable = empty($_GET['cancelable']) ? 0: intval($_GET['cancelable']); // 是否可取消选择 (默认不可以) 
		$cancel =  empty($_GET['cancel']) ? "取消选择" : trim($_GET['cancel']); // 取消选择名称，默认取消选择

		// 缺少必填参数
		if( empty($option) || empty($model) ) {
			throw new Excp("缺少必填参数(model/option)", 404, ['get'=>$_GET]);
		}

		// 类型不存在
		if ( !class_exists($model) ) {
			throw new Excp("数据模型不存在($model)", 404, ['get'=>$_GET]);
		}

		// 处理参数
		$select = is_string($fields) ? explode(',', $fields) : $fields;
		$select = !is_array($select) ? ['*'] : $select;
		$select = array_unique(array_merge( $select, [$option] ));

		// 预载入数值
		if( array_key_exists('preload', $_GET) ) {

			$ids = is_string($_GET['preload']) ? explode(',', $_GET['preload']) : [];
			if ( empty($ids) ) {
				throw new Excp("未指定预备载入数据", 404, ['get'=>$_GET]);
			}
			$method = T::s("getInBy{{option | camelCase }}", $_GET);
			
			if ( !method_exists($model, $method) ) {
				throw new Excp("查询方法不存在({$model}::{$method})", 404, ['get'=>$_GET, 'selected'=>$select]);
			}

			$data = ["items"=>[]];

			$rows = (new $model)->$method( $ids, $select );
			foreach ($rows as $id => $rs ) {
				$item  = [
					"id" => $id,
					"text" => T::s( $style, $rs ), 
					"selected" => true
				];
				array_push($data["items"], $item );
			}
			Utils::out($data);
			return;
		}

		// 查询选项列表
		if( $_GET['method'] = 'get' ) {
					
			if ( !method_exists($model, "search") &&  !method_exists($model, "select2") ) {
				throw new Excp("查询方法不存在({$model}::search)", 404, ['get'=>$_GET, 'selected'=>$select]);
			}

			$query = [
				"page" => $page,
				"perpage" => 30,
				"keyword" => $keyword,
				"select"  => $select
			];


			$data = [ "total" => 0, "items"=>[] ];
			
			// 取消选择
			if ( $cancelable != 0 ) {
				array_push($data["items"], [
					"id" => "__cancel",
					"text" => $cancel
				]);
			}

			if ( method_exists($model, "select2") ) {
				$response = (new $model)->select2( $query );	
			}  else {
				$response = (new $model)->search( $query );
			}

			
			$data["total"] = $response['total'];
			foreach ($response['data'] as $rs ) {
				$item  = [
					"id" => $rs[$option],
					"text" => T::s( $style, $rs )
				];
				array_push($data["items"], $item );
			}

			Utils::out( $data  );
			return;
		}

		Utils::out([]);
	}
}
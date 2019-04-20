<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'api.class.php' );





use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;


class apiv1DeptController extends apiController {

	function __construct() {
		parent::__construct([]);
	}

	/**
	 * 读取一组/一个部门信息
	 * @return [type] [description]
	 */
	function get() {

		$id = ( isset($this->query['id']) && !empty($this->query['id']) ) ? $this->query['id'] : null;
		$ids = (isset($this->data['ids']) && count($this->data['ids']) > 0 ) ? $this->data['ids'] : null;

		if ( $id == null &&  $ids == null ) {
			throw new Excp("缺少请求信息", 403, [ 'data'=>$this->data,'query'=>$this->query, 'appid'=>$this->appid, 'token'=>$this->token]);
			exit;
		}

		if (  $ids == null ) $ids = [$id];
		
		$dept = M('Department');	
		$resp = $dept->getListByIds( $ids );
		
		echo json_encode( ['code'=>0, 'data'=>$resp] );
	}
}

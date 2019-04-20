<?php
// 要求用户登录的控制器基类 （ 请不要直接使用 ）
// 
//      coreController 
//            |
//     publicController
//            |
//     privateController
// 
// 
include_once( AROOT . 'controller' . DS . 'public.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;
use \Xpmse\Acl as Acl;
use \Xpmse\Mem as Mem;

class privateController extends publicController {	

	
	/**
	 * 左侧菜单对象
	 * @var [type]
	 */
	protected $menu = null;


	/**
	 * 当前应用信息
	 */
	protected $app = [];


	/**
	 * 面包屑导航
	 * @var [type]
	 */
	protected $crumb = [];


	/**
	 * 当前用户信息
	 * @var array | null
	 */
	protected $user = null;


	/**
	 * 无需验证登录的 Action 清单
	 */
	protected $extra = [];



	function __construct(  $extra = [], $app=null ) {
		
		
		parent::__construct();

		// 无需登录的Action清单
		$this->extra = $extra;
		if ( in_array($this->route['action'], $this->extra)  ) {
			return;
		}

		// 验证登录 & 读取用户信息
		$user = M('User');
		$this->user = $user->getLoginInfo();
		$user->format($this->user);

		if ( $this->user ===  null || $this->user === false ) { 
			// 转向登录界面
			$ut = new Utils;
			$type = $ut->getRespType();

			if( in_array($type, ['application/json', "application/api","application/noframe", "application/portal"] ) ) {
				$this->datatype = 'json';
				$this->isAjax = true;
			}

			TO(R('core-dept', 'account', 'login'), $this->isAjax, $this->datatype );
			return null;
			exit;
		}
		

		// 核心功能菜单激活逻辑
		$menu_slug = strtolower("{$this->route['namespace']}/{$this->route['controller']}/{$this->route['action']}");

		// 应用激活逻辑
		if ( !empty($_GET['app_name']) && !empty($_GET['app_org']) ) {
			$menu_slug = strtolower("{$_GET['app_org']}/{$_GET['app_name']}/{$_GET['app_c']}/{$_GET['app_a']}");
        } 
        

		$this->menu = M('Menu', $this->user)->active($menu_slug);
		

		// 应用信息存储 (icon icontype cname )
		$this->app = is_array($app) ? $app : [
			'icon' => 'icon-xpmse',
			'icontype' => 'iconfont',
			'cname' => ''
		];
		
	}


	/**
	 * 生成基本信息数据 （重载）
	 * @param  [type]  $title  [description]
	 * @param  boolean $isfull [description]
	 * @return [type]          [description]
	 */
	public function _data( $_data=[], $title=null, $isfull=false ) {

		return array_merge(
			parent::_data( $_data, $title, $isfull ), [
				'_USER' => $this->user,  // 用户信息
				'_MENU' => (isset($this->menu)) ?$this->menu->get() : [],  // 菜单项
				'_CRUMB' => $this->crumb,  // 面包屑导航
				'_SIDEBAR_MINI'=> ($_COOKIE['sidebar_mini'] == 'hide') ? '' : 'sidebar-mini',
				'_APP' => $this->app  // 当前应用信息
		]);



	}



	/**
	 * 绑定权限
	 * @param  [type] $fullname [description]
	 * @return [type]           [description]
	 */
	protected function _acl( $name, $app=null ) {


		$group = ( $app === null ) ?  $this->app['group'] : "";
		$app = ( $app === null ) ? $this->app['name'] : $app;
		$app = ( $group == "" ) ? $app : "{$group}::$app";

		$acl = new Acl;
		$fullname = "{$app}::{$name}";

		if ( $acl->isRegister($fullname) === false ) {
			throw new  Excp('绑定的权限未被注册', '404', ['app'=>$app, 'name'=>$name]);
		}

		if ( $acl->has($fullname, $this->user) === false ) {
			throw new Excp('您没有此项功能使用权限', '503', ['app'=>$app, 'name'=>$name]);
		}

		return true;
	}


	/**
	 * 权限校验
	 * @param  [type]  $app  [description]
	 * @param  [type]  $name [description]
	 * @return boolean       [description]
	 */
	public function _has( $name, $app=null ) {

		$group = ( $app === null ) ?  $this->app['group'] : "";
		$app = ( $app === null ) ? $this->app['name'] : $app;
		$app = ( $group == "" ) ? $app : "{$group}::$app";

		$acl = new Acl;
		$fullname = "{$app}::{$name}";

		if ( $acl->isRegister($fullname) === false ) {
			throw new  Excp('绑定的权限未被注册', '404', ['app'=>$app, 'name'=>$name]);
		}

		return $acl->has($fullname, $this->user);

	}


	/**
	 * 校验当前用户是否为指定部门主管
	 * @param  [type] $dept [description]
	 * @return [type]       [description]
	 */
	public function _manager( $dept ) {

		$userid = $this->user['userid'];

		if ( isset( $dept['deptManagerUseridList'] ) && is_array($dept['deptManagerUseridList']) ) {
			$this->user['isManager'] =  in_array($userid, $dept['deptManagerUseridList'] );

		} else if ( is_array($dept) ) {
			$this->user['isManager'] =   in_array($userid, $dept );

		} else if ( is_numeric($dept) || is_int($dept) ) {

			if ( isset($this->user['dept_detail']['_id'][$dept]) ) {
				$this->user['isManager'] = $this->user['dept_detail']['_id'][$dept]['isManager'];
			} else {
				$this->user['isManager']  = false;
			}
			
		} else {
			$this->user['isManager'] =  false;
		}

		return $this->user['isManager'];
	}



	/**
	 * 面包屑导航
	 * @param  [type] $name [description]
	 * @param  [type] $link [description]
	 * @return [type]       [description]
	 */
	public function _crumb( $name, $link=null ) {
		array_push($this->crumb, ['name'=>$name, 'link'=>$link]);
	}


	/**
	 * 设定当前应用信息
	 */
	public function _app( $app ) {
		$this->app = $app;
	}


	/**
	 * 设定激活导航项
	 * @param  [type] $menu_slug 菜单 Slug 
	 * @return Menu 
	 */
	protected function _active( $menu_slug  ) {

        if ( empty($this->menu) ) {
            return $this->menu;
        }

        $this->menu->active($menu_slug);
		return  $this->menu;
	}


	

} 
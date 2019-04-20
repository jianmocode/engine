<?php
namespace Xpmse\Model;

/**
 * 菜单模型
 *
 * CLASS 
 * 		\Xpmse\Menu
 *
 * USEAGE:
 *
 */

define('MENU_CONFIG', AROOT . '/config/menu.json' );


use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Stor as Stor;
use \Xpmse\Utils as Utils;
use \Xpmse\Log as Log;

class Menu {

	private $data = [];  // 菜单数据
	private $user = [];  // 当前用户身份
    private $curr = [];  // 当前菜单

	function __construct( $user = [] ) {
		$this->load();
        $this->user = $user;
	}


	/**
	 * 读取菜单信息
	 * @param  [type] $options [description]
	 * @return [type]          [description]
	 */
	function load( $nocache = false ) {

        // 读取自定义菜单
        $opt = new \Xpmse\Option;
        $custom = $opt->get("custmenu");

        // 系统默认菜单
        if ( empty($custom) || empty($custom["menu"]) || $custom["active"] === false ) {
            if ( !file_exists(MENU_CONFIG) ) {
                throw new Excp('菜单配置信息不存在', 404, ['menu_file'=>MENU_CONFIG]);
            }

            $json_text = file_get_contents(MENU_CONFIG);
            $this->data = json_decode($json_text, true );
            if ( json_last_error() ) {
                throw new Excp('解析菜单配置文件失败', 500, ['json_error'=>json_last_error_msg(), 'menu_file'=>MENU_CONFIG]);
            }

		    // 读取应用菜单
		    $this->insert( $this->getAppMenu($nocache) );
        
        // 用户自定义菜单项
        } else {
            $this->data = $custom["menu"];
        }

		return $this;

	}

	function get() {
		return $this->curr;
	}

	
	/**
	 * 根据用户身份和激活功能，读取菜单
	 * @param  string  $active  激活的菜单项目
	 * @param  boolean $nocache 不从缓存中读取
	 * @return array $menu
	 */
	function active( $active, $nocache=false ) {
        $nocache = true;

		$resp = [];
		foreach ($this->data as $item ) {
            

			$allow = (empty($item['permission'])) ? ['boss','admin','manager','user'] : explode(',',$item['permission']);
			if ( !$this->hasPermission($item['permission']) )  { continue; }

			$item['open'] = 0; // 菜单关闭不激活状态
			if ( $item['slug'] == $active) {
				 $item['open'] = 1;
			}

			$item['submenu'] = !is_array($item['submenu']) ? [] : $item['submenu'];
			$submenu = [];
			foreach ($item['submenu'] as $idx => $sm ) {
				if ( !$this->hasPermission($sm['permission']) )  { continue; }
				$sm['active'] = 0; // 菜单为选中
				if ( $sm['slug'] == $active ) {
					$sm['active'] = 1;
					$item['open'] = 1;
				}

				if ( !empty($sm['link']) ) { // 解析菜单
					$link = (new Utils)->parseNSLink( $sm['link'] );


					if ( is_array($link) ) {
						$sm['link'] = R($link['n'], $link['c'], $link['a'], $link['q']);		
					}
				}
				array_push( $submenu, $sm );
			}

			$item['submenu'] = $submenu;

			if ( !empty($item['link']) ) { // 解析菜单
				$link = (new Utils)->parseNSLink( $item['link'] );

				if (is_array($link) ) {
					$item['link'] = R($link['n'], $link['c'], $link['a'], $link['q']);		
				}
			}

			if ( count($item['submenu']) > 0 || !empty($item['link']) || !empty($item['group']) ) { // 有效菜单
				array_push($resp, $item);
			}

		}

		// Utils::out( $active, "  ", $resp );
		$this->curr = $resp;
		return $this;
	}


	function hasPermission( $pmt="" ) {
		
		$display = false;
		$allow = (empty($pmt)) ? ['boss','admin','manager','user'] : explode(',',$pmt);

		if( $this->user['isBoss'] == true && in_array('boss', $allow) ) {
			$display = true;
		}

		if( $this->user['isAdmin'] == true && in_array('admin', $allow) ) {
			$display = true;
		}

		if( $this->user['isManager'] == true && in_array('manager', $allow) ) {
			$display = true;
		}

		if( in_array('user', $allow) ) {
			$display = true;
		}

		return $display;

	}



	/**
	 * 读取已安装应用菜单信息
	 * @return 
	 */
	function getAppMenu ( $nocache = false ) {

		$items = [];
		$apps = M('App')->getInstalled( $nocache );
		foreach ($apps['data'] as $a ) {


			if ( isset($a['menu']) && is_array($a['menu'])) {
				$it = end($a['menu']);
				

				if (isset($it['slug']) ) {  // 新菜单系统

					$items = array_merge($items, $a['menu']);

				} else {  // 旧菜单系统格式转换

					$menu = [
						"slug" => "apps/{$a['slug']}",
						"name" => "{$a['cname']}",
						"icon" => "{$a['icon']}",
						"icontype" => "{$a['icontype']}",
						"permission" => "boss,admin,manager,user",
						"submenu" => []
					];

					foreach ($a['menu'] as $sm ) {

						array_push($menu['submenu'], [
							"slug" => "apps/{$a['slug']}/{$sm['controller']}",
							"name" => "{$sm['name']}",
							"permission"=> "{$sm['permission']}",
							"link" => "{$sm['link']}"
						]);
					}

					array_push($items, $menu );
				}
			}
		}

		return $items;

		
	}

	/**
	 * 向指定位置插入菜单项
	 * @param  array  $item  菜单项
	 * @param  integer $offset 插入位置
	 * @return [type]          [description]
	 */
	function insert( $items, $offset=1 ){
		array_splice( $this->data, $offset, 0, $items );
		return $this->data;
	}



	/**
	 * 清空缓存
	 * @return [type] [description]
	 */
	function cleanCache(){
		return true;
	}
}
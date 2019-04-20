<?php
/**
 * 应用路由: 将请求转发到应用
 */

include_once( AROOT . 'controller' . DS . 'private.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Excp as Excp;

class coreAppRouteController extends privateController {

	/**
	 * 当前应用信息
	 * @var array
	 */
	private $curr = [];

	function __construct() {
		
		$app_name = $_GET['app_name'];
		$app_org = $_GET['app_org'];

		if ( empty($app_org) ) {
			throw new Excp("未指定应用厂商", 402, ['GET'=>$_GET]);
		}
		if ( empty($app_name) ) {
			throw new Excp("未指定应用名称", 402,  ['GET'=>$_GET]);
		}

		$this->curr = M('App')->getBySlug("{$app_org}/{$app_name}");
		if ( empty($this->curr) ) {
			throw new Excp("应用不存在", 404, ["app"=>"{$app_org}/{$app_name}"]);
		}

		// 载入父类构造函数
		parent::__construct(['staticurl'],'apps',  $app_name);


	}

	// Quick Index 
	function i() { $this->index(); }

	// Quick Noframe 
	function n() { $this->noframe(); }

	// Quick Portal (废弃)
	function p() { $this->portal(); }

	// Quick StaticUrl
	function s() { $this->staticurl(); }


	// 透明代理方式
	function noframe() {

		if ( $this->curr['status'] !== 'installed' ) {
			throw new Excp("{$this->curr['cname']} v{$this->curr['version']} 尚未安装", 404, [
				"app"=>"{$this->curr['org']}/{$this->curr['name']}",
				"status" => $this->curr['status']
			]);
		}

		// 选定当前应用网关
		$gateway = empty($this->curr['gateway']) ? 'http' : $this->curr['gateway'];
		$gateway = "\\Mina\\Gateway\\{$gateway}";

		$gw = new $gateway([
			"seroot" => Utils::seroot(),
			"user" => $this->user
		]);
        
        
		$curr = $this->curr;
		$gw->load("{$this->curr['org']}/{$this->curr['name']}", function( $app ) use ($curr) {
			// 禁止访问 setup 下的控制器
			foreach ($curr['setup'] as $script => $c ) {
				$curr['block'][$c['controller']][$c['action']] = true;
			}
			return $curr;
		})

        ->init()

		->transparent($_GET['app_c'], $_GET['app_a']);
	}



	// 转发静态页面
	function staticurl() {

		$gateway = empty($this->curr['gateway']) ? 'http' : $this->curr['gateway'];
		$gateway = "\\Mina\\Gateway\\{$gateway}";
		$gw = new $gateway([
			"seroot" => Utils::seroot()		
		]);
		$curr = $this->curr;
		$gw->load("{$this->curr['org']}/{$this->curr['name']}", function( $app ) use ($curr) {
			return $curr;
		})->file($_GET['path']);
	}


	// 转发不带用户信息的页面 (废弃)
	function portal() {
		throw new Excp("不支持应用。1.5+ 废弃 Portal, 请使用 MINA 框架重构", 500 );
	}

	
	// 转发带控制台的页面
	function index() {

		if ( $this->curr['status'] !== 'installed' ) {
			throw new Excp("{$this->curr['cname']} v{$this->curr['version']} 尚未安装", 404, [
				"app"=>"{$this->curr['org']}/{$this->curr['name']}",
				"status" => $this->curr['status']
			]);
		}

		// 选定当前应用网关
		$gateway = empty($this->curr['gateway']) ? 'http' : $this->curr['gateway'];
		$gateway = "\\Mina\\Gateway\\{$gateway}";

		$gw = new $gateway([
			"seroot" => Utils::seroot(),
			"user" => $this->user
		]);

		$curr = $this->curr;
		$resp = $gw->load("{$this->curr['org']}/{$this->curr['name']}", function( $app ) use ($curr) {
			// 禁止访问 setup 下的控制器
			foreach ($curr['setup'] as $script => $c ) {
				$curr['block'][$c['controller']][$c['action']] = true;
			}
			return $curr;
		})
		->init()
		->fetch($_GET['app_c'], $_GET['app_a'])
		->get();


		// 处理应用返回结果
		if ( $resp['code'] == 0 ) {
			$info = $resp['data'];
			$main_content = $resp['content'];
			$left_sidebar = $right_sidebar = array('active'=>false, 'content'=>'');
			$jsfile = array();
			if ( is_array($info['left_sidebar']) ) {
				$left_sidebar = $info['left_sidebar'];
			}

			if ( is_array($info['right_sidebar']) ) {
				$right_sidebar = $info['right_sidebar'];
			}

			if ( is_array($info['js'])) {
				$jsfile = $info['js']; $jsfile_important = [];
				foreach ($jsfile as $i=>$js ) {
					$uinfo = parse_url($js);
					if ( !isset($uinfo['scheme']) && $js[0] != '/' ){
						$jsfile[$i] = "/static/assets/" . $js;
					}

					if ( $uinfo['query'] == 'important') {
						array_push( $jsfile_important,  $jsfile[$i]);
						unset($jsfile[$i] );
					}
				}
			}

			if ( is_array($info['css'])) {
				$cssfile = $info['css']; $cssfile_important = [];
				foreach ($cssfile as $i=>$css ) {

					$uinfo = parse_url($css);
					if ( !isset($uinfo['scheme'])  && $css[0] != '/'  ){
						$cssfile[$i] = "/static/assets/" . $css;
					}
					
					if ( $uinfo['query'] == 'important') {
						array_push( $cssfile_important,  $cssfile[$i]);
						unset($cssfile[$i] );
					}
				}

			}

			//设置面包屑导航
			$title = "";
			if ( is_array( $info['crumb']) ) {
				foreach ($info['crumb'] as $name => $link) {
					$this->_crumb($name, $link);
					$title = $name . "/$title";
				}
			}

			// 设置激活导航
			if ( is_array( $info['active']) ) {
				if ( isset($info['active']['slug']) ) {
					$menu_slug = $info['active']['slug'];
				} else {
					$info['active']['query'] = !is_array($info['active']['query']) ?  [] : $info['active']['query'];
					$info['active']['c'] = empty($info['active']['c']) ?  $this->curr['controller'] : $info['active']['c'];
					$info['active']['a'] = empty($info['active']['a']) ?  $this->curr['action'] : $info['active']['a'];
					$menu_slug = strtolower("{$this->curr['org']}/{$this->curr['name']}/{$info['active']['c']}/{$info['active']['a']}");
				}
				
				$this->_active($menu_slug);
			}

			// 设置应用信息
			if ( is_array($info['app']) ) {
				$this->_app([
					'icon'=>($info['app']['icontype']== 'img')? ASR("{$this->curr['org']}/{$this->curr['name']}",$info['app']['image']['color']) : $info['app']['icon'], 
					'icontype'=>$info['app']['icontype'], 
					'cname'=>$info['app']['cname']
				]);
			} else {

				$this->_app([
					'icon'=>($this->curr['icontype'] == 'img') ? ASR("{$this->curr['org']}/{$this->curr['name']}",$this->curr['image']['color']) : $this->curr['icon'], 
					'icontype'=>$this->curr['icontype'], 
					'cname'=>$this->curr['cname']
				]);
			}

		} else {
			$message = (isset($resp['message'])) ? $resp['message'] : '应用返回结果异常';
			$trace = is_array($resp['trace']) ? $resp['trace'] : null;
			$extra  = is_array($resp['extra']) ? $resp['extra'] : [];
			
			// $extra = array_merge([ '__app_extra__'=>[]], $extra);

			$extra['__trace__'] = $trace;
			// if ( $trace != null ) {
			// unset($extra['extra']['__trace__']);
			// 	unset($resp['extra']['__extra__']);
			// 	$extra['__trace__'] = $trace;
			// }

			throw new Excp($message, $resp['code'], $extra );

		}

		
		$data = $this->_data([
			"_TITLE"=>!empty($info['title']) ? $info['title'] : "$title{$this->curr['cname']}",
			'main_content' => $main_content,
			'left_sidebar' => $left_sidebar,
			'right_sidebar' => $right_sidebar,
			'js' =>$jsfile,
			'_js' => $jsfile_important,
			'css' => $cssfile,
			'_css' => $cssfile_important
		]);
        
		// 根据不同浏览类型转向不同模板
        render( $data, 'core/app/web', 'app.frame');

	}
} 

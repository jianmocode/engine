<?php 
/**
 * MINA Pages 模板渲染器
 * 
 * @package	  \Mina\Template
 * @author	   天人合一 <https://github.com/trheyi>
 * @copyright	Xpmse.com
 * 
 * @example
 * 
 * <?php
 *  use \Mina\Template\Render;
 *  use \Mina\Template\DataCompiler;
 *
 * 	class MyDataCompiler extends DataCompiler {
 *  	
 * 	 	function __construct() {
 * 		 	parent::__construct(["home"=>"http://apps.minapages.com/1"]);
 * 	   	}
 * 
 *	  	function compile( $json_data ) {
 * 		 	return '
 * 		 			$data = ["bar"=>"foo"];
 * 		   		';
 *	  	}
 * 	}
 *  
 * 	$cp = new Render();
 * 	
 * 	...
 *  
 */

namespace Mina\Template;

use Mina\Template\DataCompiler;
use Mina\Template\Compiler;
use Mina\Cache; 
use \Exception;


class Render  {

    public $cache = null; 

    /**
     * 翻译类
     */
    public $lang = null;

    /**
     * 当前语言
     */
    public $local = null;
	private $options;

	/**
	 * 模板渲染类
	 * @param array $options 配置选项
     *               string   [:lang] 语言包根路径
	 *         	     boolean  ["marker"]  是否添加注记广告,  默认添加 (对所有页面有效)
	 *         	     array    ["assets"]  在Head中引入的 CSS 或 JS (对所有页面有效)
	 *      string | array    ["script"]  在body结尾处插入的 JavaScript 脚本代码 (对所有页面有效)
	 *      		 array    ["cache"]   缓存配置选项
	 *      		     string  ["cache"]["engine"] 引擎名称 有效值 Redis/Apcu, 默认为 null, 不启用缓存。
	 *      		     string  ["cache"]["prefix"] 缓存前缀，默认为空
	 *      		     string  ["cache"]["host"] Redis 服务器地址  默认 "127.0.0.1"
	 *      		        int  ["cache"]["port"] Redis 端口 默认 6379
	 *      		     string	 ["cache"]["passwd"] Redis 鉴权密码 默认为 null
	 *      		        int  ["cache"]["db"] Redis 数据库 默认为 1
	 *      		        int  ["cache"]["timeout"] Redis 超时时间, 单位秒默认 10
	 *      		        int	 ["cache"]["retry"] Redis 链接重试次数, 默认 3
	 */
	function __construct($options = []) {
		
		$this->options = $options;
		$cacheOptions = is_array($this->options['cache']) ? $this->options['cache'] : [];
		if (!empty($cacheOptions['engine'])) {
			$cacheClassName = "\\Mina\\Cache\\{$cacheOptions['engine']}";

			if ( class_exists($cacheClassName) ) {
				$this->cache = new $cacheClassName( $cacheOptions );
			}
		}
	}


    /**
     * 从缓存中读取渲染结果
     * @param  string $name   页面唯一名称
     * @return string 成功返回页面, 失败返回 false
     */
    function getFromCache( $name ) {

        if( !empty( $this->cache )  ) {
            $cacheName = $this->cacheName( $name, $_REQUEST );
            return $this->cache->get( $cacheName );
        }
        return false;
    }


	/**
	 * 渲染成 HTML 页面
	 * @param  string $name   页面唯一名称
	 * @param  string|function( $name, $exec_options )   $phpcode   PHP Code 或使用 $name 换取代码函数
	 * @param  array  $exec_options 配置选项
	 *           boolean ["nocache"] 是否关闭缓存，默认开启 (废弃)
	 *           boolean ["refresh"] 是否刷新缓存，默认关闭 (废弃)
	 *           boolean ["return"]  若返回结果，设定为 true，默认不返回
	 *               int ["ttl"]	 缓存有效期，单位秒, 默认为0，代表长期有效
	 *                
	 * @return null | string  如果配置项中 'return' 为 TRUE ，则返回解析后的页面
	 */
	function exec( $name,  $phpcode, $exec_options=[] ) {

        // 网页名称
        $____render_page_name = $name;
        
        // 多语言支持/翻译器
        if ( !empty($this->cache) ) {
            $this->local = $local = $exec_options["lang"];
            $this->lang = new Lang( ["cache" => $this->cache]);
        }

		if ( is_string($phpcode) ) {
			$code = $phpcode;
		} elseif ( is_callable($phpcode)  ) {			
			$code = $phpcode( $name, $exec_options );
		} else {
			throw new Exception("无法获取 PHP Code (应该为 string / function )", 500 );
		}

        ob_start();
        eval("?>$code");
        $content = ob_get_contents();
		ob_clean();

		$exec_options['ttl']  = isset($exec_options['ttl']) ? intval($exec_options['ttl']) : 0;

		if (!empty( $this->cache)  && $exec_options['ttl'] > 0 ) {
            $cacheName = $this->cacheName( $name, $_REQUEST );
			$this->cache->set( $cacheName, $content, $exec_options['ttl'] );
        }

        // 多语言支持/翻译器
        if ( !empty($this->lang) && !empty($this->local) ) {
            $this->lang->translate( $content, $____render_page_name, $this->local);
        }
        
		if ( $exec_options['return'] === true ) {
			return $content;
        }
        
		echo $content;
    }
    
    /**
     * 读取字典/输出页面
     * @param string $content 页面内容
     * @param string $page 页面唯一名称: root:tars/desktop/index/index 
     * @param string $lang 语言包
     */
    function translate( & $content, $page, $lang ) {

        if ( empty($this->cache) ) {
            return;
        }

        // print_r( $this->options );
        // $this->options["cache"]["prefix"] = "Language:";
        $local = new Lang(  [
            "cache" => $this->cache
        ]);
        $local->translate( $content, $page, $lang );

        // exit;
    }


	/**
	 * 运行PHP代码
	 * @param  string $phpcode PHP 代码
	 * @param  boolean $return 是否返回结果
	 * @return null | string  如果配置项中 $return 为 TRUE ，则返回解析后的页面
	 */
	function execRaw( $phpcode, $return=true ) {
		if ( $return ) {
		 	ob_start();
		}
		eval("?>$code<?php");
		if ( $return ) {
			$content = ob_get_contents();
			ob_clean();
			return $content;
		}
	}


	/**
	 * 将 Page 编译 PHP代码
	 * @param  array $page 页面信息
	 *          DataCompiler   ["compiler"]   数据编译器, 默认为 \Mina\Template\DataCompiler         
	 *         	     boolean   ["marker"]     是否添加注记广告,  默认添加
	 *         	       array   ["assets"]     在Head中引入的 CSS 或 JS 
	 *        string | array   ["script"]     在body结尾处插入的 JavaScript 脚本代码
	 *      		  string   ["page"]       页面模板代码
	 *      		  string   ["json"]       页面配置JSON字符串
	 *         	     
	 * @return string 编译完成的PHP代码
	 */
	function compile( $page ) {


		$cp = new Compiler( $this->options );
		$compiler = isset( $page['compiler'] ) ? $page['compiler'] : null;

		if ( empty($page['json']) || empty($page['page'])) {
			return '<?php echo "empty page"; ?>'; // 忽略未完成页面
		}

		return $cp->loadPage( $page )->toPHP( $compiler );
	}



	/**
	 * 将 Page 编译 PHP代码并运行
	 * @param  array $page 页面信息
	 *          DataCompiler   ["compiler"]   数据编译器, 默认为 \Mina\Template\DataCompiler
	 *         	     boolean   ["marker"]     是否添加注记广告,  默认添加
	 *         	       array   ["assets"]     在Head中引入的 CSS 或 JS 
	 *        string | array   ["script"]     在body结尾处插入的 JavaScript 脚本代码
	 *      		  string   ["page"]       页面模板代码
	 *      		  string   ["json"]       页面配置JSON字符串
	 *
	 * @param array $query $_GET 参数
	 * @param array $query $_POST 参数
	 *         	     
	 * @return string 编译完成的PHP代码
	 */
	function run( $page, $query = [], $data=[] ) {



		$cp = new Compiler( $this->options );
		$compiler = isset( $page['compiler'] ) ? $page['compiler'] : null;
		$cp->loadPage( $page )->toPHP( $compiler );
		return $cp->toHTML( $query, $data );
	}



	/**
	 * 返回缓存名称
	 * @param  string $name 页面唯一名称
	 * @param  array $param 请求参数( $_REQUEST )
	 * @return string 缓存唯一名称
	 */
	function cacheName( $name, $param ) {
		sort( $param );
		$param_string = serialize($param) ;   // serialize 比 json 效率高
		$string = hash('md4',  $param_string);   // MD4 最快 http://www.cnblogs.com/AloneSword/p/3464330.html
		return $name . ":" . $string;
	}


	/**
	 * 清除页面缓存
	 * @param  string $name  页面唯一名称
	 * @param  string $param  请求参数( $_REQUEST )
	 * @return 成功返回true , 失败返回 false
	 */
	function rmCache( $name, $param=null ) {
		
		if( empty( $this->cache ) ) {
			return false;
		}

		if ( $param === null ) {
			return $this->cache->delete( $name );	
		}

		return $this->cache->del( $this->cacheName($name, $param) );
	}

	/**
	 * 返回页面缓存实例
	 * @return 返回缓存对象或null 
	 */
	function cacheInst() {
		return 	$this->cache;
	}


}











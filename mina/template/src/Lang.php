<?php
/**
 * 简墨语言包解析器
 * 
 * @author Max<https://github.com/trheyi>
 * @license Apache 2.0 license <https://www.apache.org/licenses/LICENSE-2.0>
 * @copyright 2019 Jianmo.ink
 */


namespace Mina\Template;
use \Exception;
use \PhpZip\ZipFile;
use Mina\Cache; 

class Lang {

    /**
     * 语言包文件路径
     */
    private $root = null;

    /**
     * 数据缓存
     */
    private $cache = null;

    /**
     * 配置项
     */
    private $options = [];


    function __construct($options = []) {

        // 保存配置项
        $this->options = $options;

        if ( empty($this->options) ) {
            throw new Exception("请设置缓存配置", 500 );
        }

        // 数据缓存
        if ( is_array($this->options['cache']) ) {
            $cacheOptions = $this->options['cache'];
            if (!empty($cacheOptions['engine'])) {
                $cacheClassName = "\\Mina\\Cache\\{$cacheOptions['engine']}";

                if ( class_exists($cacheClassName) ) {
                    $this->cache = new $cacheClassName( $cacheOptions );
                }
            }

        // 直接导入缓存
        } else {
            $this->cache = $this->options["cache"];
        }
        
        if ( empty($this->cache) ) {
            throw new Exception("未找到有效数据缓存配置", 500 );
        }

    }


    /**
     * 加载语言包: 将语言包加载到内存
     * @param string $root 语言包根目录, 默认为 /data/lang
     * @param string $lang 语言
     * @param string $project  项目名称
     * @param string $instance 实例名称
     * @return bool 加载成功返回 true, 加载失败返回 false
     */
    function load( $root = null, $lang = null,  $project = null, $instance=null ) {
        
        // 设定语言包路径
        if ( empty($root) || !is_dir($root) ) {
            $root = "/data/lang";
        }

        // 遍历语言包文件
        $this->scan( function($file,$cache, $lang, $is_global,  $project,  $instance, $type ) {
            if ( $type == 'dict' ) {
                $this->loadDict($file,$cache, $lang, $is_global,  $project,  $instance);
            } else if ( $type == 'data' ) {
                $this->loadData($file,$cache, $lang, $is_global,  $project,  $instance);
            }
        }, $project, $instance, $lang, $root);

    }


    /**
     * 加载字典文件
     * @param string $file 字典文件
     * @param string $cache 缓存名称
     * @param string $lang 语言
     * @param bool   $is_global 是否是全局字典
     * @param string $project  项目名称
     * @param string $instance 实例名称
     * @return bool 加载成功返回 true, 失败抛出异常
     */
    function loadDict( $file,  $cache,  $lang, $is_global,  $project,  $instance) {
        
        $dict = yaml_parse_file( $file );
        if ( $dict === false ) {
            throw new Exception("语言包字典文件解析失败($file)", 400 );
        }

        $resp = $this->cache->setJSON( $cache, $dict );
        if ( $resp === false ) {
            throw new Exception("语言包字典文件装载失败($file)", 400 );
        }

        return true;
    }

    /**
     * 加载数据源文件
     * @param string $file 字典文件
     * @param string $cache 缓存名称
     * @param string $lang 语言
     * @param bool   $is_global 是否是全局字典
     * @param string $project  项目名称
     * @param string $instance 实例名称
     * @return bool 加载成功返回 true, 失败抛出异常
     */
    function loadData( $file,  $cache,  $lang, $is_global,  $project,  $instance) {
        
        $json_text = file_get_contents( $file );

        if ( $json_text === false ) {
            throw new Exception("语言包数据源文件读取失败($file)", 400 );
        }

        $data = json_decode( $json_text, true );
        if ( $data === false ) {
            throw new Exception("语言包数据源文件解析失败($file)", 400 );
        }

        $resp = $this->cache->setJSON( $cache, $data );
        if ( $resp === false ) {
            throw new Exception("语言包数据源文件装载失败($file)", 400 );
        }

        return true;
    }



    /**
     * 卸载语言包: 将语言从内存中删除
     * @param string $lang 语言
     * @param string $project  项目名称
     * @param string $instance 实例名称
     * @return bool 卸载成功返回 true, 卸载失败返回 false
     */
    function unload( $lang="", $project = null, $instance=null ) {
        $this->cache->delete("{$lang}");
    }


    /**
     * 安装语言包
     * @param string $instance 实例名称
     * @param string $project  项目名称
     * @param string $zipfile  压缩包文件
     * @param string $root     语言包根目录, 默认为 /data/lang
     * @param bool   $replace  是否替换现有文件
     * @param string $lang     语言包名称
     * @return bool  卸载成功返回 true, 卸载失败抛出异常
     */
    function install( $instance, $project,  $zipfile, $root = null, $replace = true, $lang=null) {

        if ( empty($root) || !is_dir($root) ) {
            $root = "/data/lang";
        }
        
        // 解压文件
        $zip = new ZipFile();
        $path = "{$root}/{$instance}/{$project}";
		@mkdir( $path, 0777, true);
        
        // 解压缩文件
		$zip->openFile($zipfile)
			->extractTo($path);
        
        return true;
    }


    /**
     * 卸载语言包
     */
    function uninstall( $instance, $project, $lang ) {

    }


    /**
     * 遍历语言包文件
     * @param function  $callback(string $file, string $lang, bool $is_global, string $project, string $instance) 回调函数
     * @param string $project  项目名称
     * @param string $instance 实例名称
     * @param string $lang 语言包名称
     * @param string $root     语言包根目录, 默认为 /data/lang
     * @param string $path     当前遍历的目录, 默认为 /data/lang
     * @param string $type     文件类型 lang 语言包, data 数据源
     * @return null
     */
    function scan( $callback,  $project=null, $instance=null, $lang =null, $root = null, $path = null, $type = null ) {
        
        if ( empty($root) || !is_dir($root) ) {
            $root = "/data/lang";
        }

        if ( empty($path) ) {
            $path = $root;
        }

        $dir = $path;

        // 查找指定实例
        if ( !empty($instance) ) {
            $dir = "{$dir}/{$instance}";
        }

        // 查找指定项目
        if ( !empty($project) ) {
            $dir = "{$dir}/{$project}";
        }


        if(!is_dir($dir)) return false;

        foreach (scandir($dir, SCANDIR_SORT_DESCENDING) as $name ) {
            if ( $name == "." || $name == ".." ) {
                continue;
            }

            $path = "{$dir}/$name";
            if ( is_dir($path) ) {
                $this->scan( $callback, $project, $instance, $lang, $root, $path );

            } else {
                
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if ( $ext == "yml" ) {
                    $pi = pathinfo($path);
                    $filelang = $pi["filename"];
                    $is_global = true;
                    $cache = explode("/", str_replace("{$root}", "", $path ));
                 
                    // 页面字典文件                    
                    if ( $filelang == 'lang' ) {
                        $dirs = explode("/",$pi["dirname"]);
                        $filelang = end( $dirs );
                        $is_global = false;
                        $page = explode("/",str_replace("{$root}/{$cache[1]}/{$cache[2]}", "", $pi["dirname"] ));
                        array_splice($page, -1, 1);
                        $page = implode("/", $page );

                        // 缓存名称
                        $cache_name = "{$filelang}:{$cache[1]}:{$cache[2]}" . $page . ":dict";
                        
                    
                    // 全局字典文件
                    } else {
                        $cache_name = "{$filelang}:{$cache[1]}:{$cache[2]}:dict";
                    }

                    // 过滤语言包
                    if ( !empty($lang) && $lang != $filelang ) {
                        continue;
                    }

                    // 解析配置名称
                    $callback( $path, $cache_name, $lang, $is_global, $project, $instance, "dict");
                
                } else if ( $ext == 'json') {
                    
                    $pi = pathinfo($path);
                    $filelang = $pi["filename"];
                    $is_global = true;
                    $cache = explode("/", str_replace("{$root}", "", $path ));
                    
                    // 页面数据源文件                    
                    if ( $filelang == 'data' ) {
                        $dirs = explode("/",$pi["dirname"]);
                        $filelang = end( $dirs );
                        $is_global = false;
                        $page = explode("/",str_replace("{$root}/{$cache[1]}/{$cache[2]}", "", $pi["dirname"] ));
                        array_splice($page, -1, 1);
                        $page = implode("/", $page );

                        // 缓存名称
                        $cache_name = "{$filelang}:{$cache[1]}:{$cache[2]}" . $page . ":data";
                        
                    
                    // 全局字典文件
                    } else {
                        $cache_name = "{$filelang}:{$cache[1]}:{$cache[2]}:data";
                    }

                    // 过滤语言包
                    if ( !empty($lang) && $lang != $filelang ) {
                        continue;
                    }

                    // 解析配置名称
                    $callback( $path, $cache_name, $lang, $is_global, $project, $instance, "data");
                }


            }
        }

        return true;
    }


    /**
     * 读取内容源/替换内容源
     * @param array  $source  原生数据源
     * @param string $page    页面唯一名称: root:tars/desktop/index/index 
     * @param string $lang    语言包
     */
    function data( $source, $page, $lang ) {
        if ( !is_string($page) || empty($page) ) {
            return;
        }

        if ( !is_string($lang) || empty($lang) ) {
            return;
        }

        // 提取instance & project 路径
        $path = explode("/", $page);
        $path = current($path);
        $path = explode(":", $path);
        $instance = $path[0];
        $project = $path[1];

        if ( empty($instance) ) {
            return;
        }
        if ( empty($project) ) {
            return;
        }

        // 读取语言包数据源
        $data = $this->cache->getJSON( "$lang:{$page}:data" );
        if ( !is_array($data) ) {    
            $data = $source;        
        }
    
        // 读取全局语言包数据源
        $global = $this->cache->getJSON( "{$lang}:{$instance}:{$project}:data" );
        if ( is_array($global) ) {
            $data = array_merge( $data, $global );
        }

        return $data;
    }


    /**
     * 读取字典/翻译内容
     * @param string $content 页面内容
     * @param string $page 页面唯一名称: root:tars/desktop/index/index 
     * @param string $lang 语言包
     */
    function translate( & $content,  $page,  $lang ) {
        if ( !is_string($page) || empty($page) ) {
            return;
        }

        if ( !is_string($lang) || empty($lang) ) {
            return;
        }
        
        // 提取instance & project 路径
        $path = explode("/", $page);
        $path = current($path);
        $path = explode(":", $path);
        $instance = $path[0];
        $project = $path[1];

        if ( empty($instance) ) {
            return;
        }
        if ( empty($project) ) {
            return;
        }

       
        // 读取页面语言包
        $dict = $this->cache->getJSON( "$lang:{$page}:dict" );
        if ( !is_array($dict) ) {    
            $dict = [];        
        }
    
        // 读取全局语言包
        $global = $this->cache->getJSON( "{$lang}:{$instance}:{$project}:dict" );
        if ( is_array($global) ) {
            $dict = array_merge( $global["words"], $dict );
        }
 
        if (empty($dict)) {
            return;
        }

        // 排序
        $keys = array_map('strlen', array_keys($dict));
        array_multisort($keys, SORT_DESC, $dict);

        // 替换内容
        $this->word_replace( $content, $dict );
        $this->date_replace( $content, $dict );
    }


    /**
     * 单词批量替换
     * @param string $content 页面内容
     * @param array $dict 字典
     */
    function word_replace( & $content, & $dict ) {
        if( isset($_GET["lang_debug"]) && $_GET["lang_debug"] == 1 ) {
            echo "<!-- \n";
            print_r( $dict );
            echo "--> \n";
        }
        $content = str_replace(array_keys($dict), array_values($dict), $content);
    }


    /**
     * 日期时间批量替换 (下一版支持)
     */
    function date_replace( & $content, & $format ) {

    }

}
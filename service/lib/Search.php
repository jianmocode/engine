<?php
/**
 * 简墨搜索引擎
 * 
 * @author Max<https://github.com/trheyi>
 * @license Apache 2.0 license <https://www.apache.org/licenses/LICENSE-2.0>
 * @copyright 2019 Jianmo.ink
 */

namespace Xpmse;
require_once( __DIR__ . '/Inc.php');

use \Xpmse\Model as Model;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;


class Search extends Model {

    private $driver = null;

    /**
	 * 搜索数据
	 * @param array $option 配置选项
     *              string ":driver"  搜索引擎驱动, 默认为 MySQL
	 */
	function __construct( $option=[] ) {

        // 搜索内容数据表
        $db_driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'], $db_driver );
        $this->table('search');

        /**
         * 配置搜索引擎
         */
        $driver = empty($option["driver"]) ? "Mysql": ucfirst( $option["driver"] );
        $driver_root =  dirname(__FILE__) . '/search-driver' ;
		$class_name =  "\\Xpmse\\Search\\{$driver}";

		if ( !file_exists("$driver_root/{$driver}.php") ) {
			throw new Excp('搜索引擎驱动不存在', 404, ['driver'=>$this->driver, 'class_name'=>$class_name, 'option'=>$option]);
		}

        // 载入驱动
        include_once( "$driver_root/{$driver}.php" );
    
		if ( !class_exists($class_name) ) {
			throw new Excp('搜索引擎驱动不存在', 404, ['driver'=>$this->driver, 'class_name'=>$class_name, 'option'=>$option]);
        }
        
        // 创建搜索引擎对象
        if ( $driver == "Mysql") {
            $option["model"] =  $this;
        }

        $this->driver = new $class_name( $option );
    }


    /**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {

		// 创建搜索数据表
		try {

            $this

                // String 文档ID, 不存在则创建, 存在则更新. 仅支持精确查询.
                ->putColumn( 'doc_id', $this->type('string', ['unique'=>true, "null"=>false,'length'=>128] ) )
            
                // 内容源文档ID. 支持精确查询.
                ->putColumn( 'origin_id', $this->type('string', ['index'=>true, "null"=>true,'length'=>128] ) )
                 
                // String 文档来源, 支持精确查询, 可以按来源过滤结果
                ->putColumn( 'origin', $this->type('string', [ "null"=>false, 'index'=>true, 'length'=>128] ) )

                // String 标题, 支持匹配查询
                ->putColumn( 'title', $this->type('string', [ "null"=>false,'length'=>600] ) )

                // String 摘要, 支持匹配查询
                ->putColumn( 'summary', $this->type('string', [ "null"=>false,'length'=>2000] ) )

                // String 访问地址, 支持匹配查询
                ->putColumn( 'url', $this->type('string', [ "null"=>false,'length'=>2083] ) )

                // TimestamptZ 发布时间, 支持范围/精确查询, 可排序.
                ->putColumn( 'published_at', $this->type('timestampTz', ["index"=>true] ) )
            
                // String 文档正文, 支持匹配查询
                ->putColumn( 'content', $this->type('longText', [ "null"=>false,'length'=>128] ) )

                // String 文档类型, 支持精确查询. 默认为 text. text 文本, markdown Markdown格式文本, json JSON格式文本, html HTML格式文本, yaml YAML格式文本, image 图片, video 视频, audio 语音
                ->putColumn( 'type', $this->type('string', [ "null"=>false,'length'=>32, "index"=>true] ) )

                // Array[string] 文档标签, 支持精确/匹配查询, 可以按标签过滤结果
                ->putColumn( 'tags', $this->type('string', [ "null"=>true, "json"=>true, 'length'=>600, "index"=>true] ) )

                // Array[string] 文档类目, 支持精确/匹配查询, 可以按类目过滤结果
                ->putColumn( 'categories', $this->type('string', [ "null"=>true, "json"=>true, 'length'=>600, "index"=>true] ) )

                // Array[string] 文档所属用户, 支持精确/匹配查询, 可以按用户过滤结果
                ->putColumn( 'users', $this->type('text', [ "null"=>true, "json"=>true] ) )

                // Boolen 是否开启相似搜索, 默认为 0.  0 不开启, 1 开启
                ->putColumn( 'similar', $this->type('boolean', [ "null"=>true] ) )
               
                // String 文档作者, 支持精确查询/匹配查询, 可以按作者过滤结果
                ->putColumn( 'author', $this->type('string', [ "null"=>true, 'index'=>true, 'length'=>128] ) )

                // Integer 用户自定义权重, 支持精确查询, 可排序.
                ->putColumn( 'priority', $this->type('bigInteger', [ "null"=>true, 'index'=>true, 'length'=>128] ) )

                // Object 传入数据, 不支持查询, 搜索后按原值返回.
                ->putColumn( 'data', $this->type('text', [ "null"=>true,"json"=>true] ) )

                // String 唯一来源判定
                ->putColumn( 'origin_id_uni', $this->type('string', ['unique'=>true, "null"=>true, 'length'=>128] ) )
			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
        } catch ( Excp $e ) {
            $e->log();
            throw $e;
        }

        // + FullText 索引
        $fulltextFields = ["title", "summary", "content", "author", "url", "users", "categories", "tags"];
        foreach($fulltextFields as $field) {
            try { 
                $this->runSql("ALTER TABLE {{table}} ADD FULLTEXT search_{$field}_fulltext (`{$field}`) WITH PARSER ngram");
            } catch ( Exception $e ) {
                Excp::elog($e);
            } catch ( Excp $e ) {
                $e->log();
            }
        }
    }
    

    /**
     * 数据初始化
     */
    function __defaults() {

    }


    /**
     * 返回查询构造器
     */
    static public function Engine( $option = [] ) {
        $se = new self( $option );
        return $se->driver->query();
    }

}
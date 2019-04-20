<?php
/**
 * 简墨搜索引擎(MySQL全文检索驱动程序)
 * 
 * 支持MySQL 5.7以上版本, 推荐配置参数:
 * [mysqld]
 *    ...
 *    ngram_token_size = 2
 *    ft_min_word_len  = 2 
 *  
 * @author Max<https://github.com/trheyi>
 * @license Apache 2.0 license <https://www.apache.org/licenses/LICENSE-2.0>
 * @copyright 2019 Jianmo.ink
 */

namespace Xpmse\Search;
require_once( __DIR__ . '/../Inc.php');
require_once( __DIR__ . '/../search-driver/Engine.php');

use \Exception as Exception;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;

class Mysql implements Engine {

    /**
     * 配置信息
     */
    private $option = [];

    /**
     * 数据模型
     */
    private $model = null;


    /**
     * 查询条件
     */
    private $query = [
        "keywords" => [],
        "match" => [],
        "term" => [],
        "range" => [],
        "sort" => [],
        "select" => [
            "doc_id", "origin_id", "title", "summary", "url", "published_at", "type", "tags", "categories", 
            "users", "origin", "author", "priority", "data", "similar"
        ]
    ];


    /**
     * QueryBulider 实例
     */
    private $qb = null;

	/**
	 * 构造函数
	 * @param array $option 配置项
	 */
	function __construct( $option = [] ) {

        if ( empty($option["model"]) ) {
            throw Excp("配置选项错误, 请提供搜索表数据模型",  404, ["option"=>$option]);
        }

        // 上下文关联长度
        $option["contextLength"] = !empty($option["contextLength"]) ? intval($option["contextLength"]) : 60;

        $this->model = $option["model"];  unset($option["model"]);
        $this->option = $option;
    }

    
    /**
	 * 推送数据到搜索引擎
     * 
     * @param array  $data   文档数据结构
     *                  String          :doc_id         [选填]文档ID, 不存在则创建, 存在则更新. 仅支持精确查询.
     *                  String          :origin         [选填]文档来源, 支持精确查询, 可以按来源过滤结果.
     *                  String          :origin_id      [选填]内容源文档ID. 支持精确查询.
     *                  String          :title          [必填]标题, 支持匹配查询
     *                  String          :summary        [必填]摘要, 支持匹配查询
     *                  String          :url            [必填]访问地址, 支持匹配查询
     *                  TimestamptZ     :published_at   [必填]发布时间, 支持范围/精确查询, 可排序.
     *                  String          :content        [必填]文档正文, 支持匹配查询
     *                  String          :type           [必填]文档类型, 支持精确/匹配查询, 默认为 text.  text 文本, markdown Markdown格式文本, json JSON格式文本, html HTML格式文本, yaml YAML格式文本, image 图片, video 视频, audio 语音
     *                  Array[string]   :tags           [选填]文档标签, 支持精确/匹配查询, 可以按标签过滤结果
     *                  Array[string]   :categories     [选填]文档类目, 支持精确/匹配查询, 可以按类目过滤结果
     *                  Array[string]   :users          [选填]文档所属用户, 支持精确/匹配查询, 可以按用户过滤结果
     *                  String          :author         [选填]文档作者, 支持精确/匹配查询, 可以按作者过滤结果
     *                  Integer         :priority       [选填]用户自定义权重, 支持精确查询, 可排序.
     *                  Object          :data           [选填]传入数据, 不支持查询, 搜索后按原值返回.
     *                  Boolen          :similar        [选填]是否开启相似搜索, 默认为 0.  0 不开启, 1 开启
     * 
     * @return bool 成功返回true, 失败返回 false
	 */
    public function push( array $data ) {

        // 默认值
        $data["type"] = empty($data["type"]) ? 'text' : $data["type"];
        $data["similar"] = !array_key_exists("similar", $data) ? 0 : $data["similar"];
        $data["priority"] = !array_key_exists("priority", $data) ? 999999999 : $data["priority"];
        $data["published_at"] = !array_key_exists("published_at", $data) ? time() : $data["published_at"];
        
        // Array[String] 字段
        $array_fields = ["tags", "categories", "users"];
        array_walk( $array_fields, function( $field ) use( & $data ){

            if ( is_string($data["$field"])) {
                $data["$field"] = explode(",", $data["$field"]);
            }

            if ( !is_array($data["$field"])) {
                $data[$field] = [];
            }
            
            $data[$field] = array_map( "trim", $data[$field] );
        });

        // String 字段
        $string_fields = ["doc_id", "title", "summary", "url", "content", "type", "origin", "author"];
        array_walk( $string_fields, function( $field ) use( & $data ){
            if ( !empty($data["$field"]) ) {
                $data["$field"] = trim($data["$field"]);
            }
        });

        // Integer 字段
        $integer_fields  = ["similar", "priority"];
        array_walk( $integer_fields, function( $field ) use( & $data ){
            $data["$field"] = intval($data["$field"]);
        });

        // Timestamp 字段 
        $timestamp_fields  = ["published_at"];
        array_walk( $timestamp_fields, function( $field ) use( & $data ){
            if ( is_string($field) ) {
                $data["$field"] = strtotime($data["$field"]);
            }
            $data["$field"] = date('Y-m-d H:i:s',$data["$field"]);
        });

        // 唯一主键
        if ( !empty($data["origin_id"]) && !empty($data["origin"]) ){
            $data["origin_id_uni"] = "{$data["origin"]}_{$data["origin_id"]}";
        }

        // 生成 doc_id
        if ( empty($data["doc_id"]) ){
            $data["doc_id"] = $this->model->genId();
        }

        // 数据入库
        return $this->model->createOrUpdate($data);
    }


    /**
     * 清除文档
     * @param string $doc_id 文档ID
     * @return 成功返回 true, 失败返回 false
     */
    public function remove( string $doc_id ) {
        return $this->model->remove($doc_id, "doc_id", false);
    }

    /**
     * 使用来源文档ID清除文档
     * @param string $origin 来源
     * @param string $origin_id 来源文档ID
     * @return 成功返回 true, 失败返回 false
     */
    public function removeOrigin( string $origin, string $origin_id ) {

        // 防止删除所有
        if ( empty($origin) || empty($origin_id) ) {
            return false;
        }
        
        $origin_id_uni = "{$origin}_{$origin_id}";
        return $this->model->remove($origin_id_uni, "origin_id_uni", false);
    }


    /**
     * 查询条件构造器
     * @return Engine $this
     */
    public function query() {
        $this->qb = $this->model->query();
        return $this;
    }


    /**
     * 设定模糊查询条件(AND WHERE)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function match( string $field, string $keyword ){
        $this->query["match"][]  = [
            "field" => $field,
            "operator" => "and",
            "keyword"=>$keyword
        ];
        array_push( $this->query["keywords"], $keyword );
        return $this;
    }

    /**
     * 设定模糊查询条件(OR WHERE)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function orMatch( string $field, string $keyword ){
        $this->query["match"][]  = [
            "field" => $field,
            "operator" => "or",
            "keyword"=>$keyword
        ]; 
        array_push( $this->query["keywords"], $keyword );
        return $this;
    }


    /**
     * 设定精确查询条件(AND WHERE)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function term( string $field, string $keyword ){

        $this->query["term"][]  = [
            "field" => $field,
            "operator" => "and",
            "keyword"=>$keyword
        ];

        if ( in_array($field, ["tags", "categories"])){
            array_push( $this->query["keywords"], $keyword );
        }
        return $this;
    }

    /**
     * 设定精确查询条件(OR WHERE)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function orTerm( string $field, string $keyword ){

        $this->query["term"][]  = [
            "field" => $field,
            "operator" => "or",
            "keyword"=>$keyword
        ];

        if ( in_array($field, ["tags", "categories"])){
            array_push( $this->query["keywords"], $keyword );
        }
        return $this;
    }

    /**
     * 设定范围查询条件(AND WHERE)
     * @param string $field 字段名称
     * @param array $parameters 查询方式
     *              格式: {":method":":value"}. 
     *              :method 数值范围:
     *                   "gt"    大于 >
     *                   "lt"    小于 <
     *                   "gte"   大于等于 >=
     *                   "lte"   小于等于 <=
     *                   
     * @return Engine $this
     */
    public function range( string $field, array $parameters ){

        // 日期时间 字段
        $integer_fields  = ["similar", "priority"];
         
        // Timestamp 字段 
        $timestamp_fields  = ["published_at"];
          
        if ( in_array($field, $integer_fields) ) {
            array_walk( $parameters, function( $value, $method ) use( & $parameters ){
                $parameters["$method"] = intval($parameters["$method"]);
            });
        }

        if ( in_array($field, $timestamp_fields) ) {
            array_walk( $parameters, function( $value, $method ) use( & $parameters ){
                if ( is_string($value) ) {
                    $parameters["$method"] = strtotime($parameters["$method"]);
                }
                $parameters["$method"] = date('Y-m-d H:i:s',$parameters["$method"]);
            });
        }

        $this->query["range"][]  = [
            "field" => $field,
            "operator" => "and",
            "parameters"=>$parameters
        ];

        return $this;
    }


    /**
     * 设定范围查询条件(OR WHERE)
     * @param string $field 字段名称
     * @param array $parameters 查询方式
     *              格式: {":method":":value"}. 
     *              :method 数值范围:
     *                   "gt"    大于 >
     *                   "lt"    小于 <
     *                   "gte"   大于等于 >=
     *                   "lte"   小于等于 <=
     *                   
     * @return Engine $this
     */
    public function orRange( string $field, array $parameters ){

        // 日期时间 字段
        $integer_fields  = ["similar", "priority"];
         
        // Timestamp 字段 
        $timestamp_fields  = ["published_at"];
          
        if ( in_array($field, $integer_fields) ) {
            array_walk( $parameters, function( $value, $method ) use( & $parameters ){
                $parameters["$method"] = intval($parameters["$method"]);
            });
        }

        if ( in_array($field, $timestamp_fields) ) {
            array_walk( $parameters, function( $value, $method ) use( & $parameters ){
                if ( is_string($value) ) {
                    $parameters["$method"] = strtotime($parameters["$method"]);
                }
                $parameters["$method"] = date('Y-m-d H:i:s',$parameters["$method"]);
            });
        }


        $this->query["range"][]  = [
            "field" => $field,
            "operator" => "or",
            "parameters"=>$parameters
        ];

        return $this;
    }


    /**
     * 设定排序条件
     * @param string $field 字段名称
     * @param array $parameters 排序方式
     *              格式: {":method":":value"}. 
     *              :method 数值范围:
     *                   "order" 排序方式 desc/asc
     *                   
     * @return Engine $this
     */
    public function sort( string $field, array $parameters ) {

        $this->query["sort"][]  = [
            "field" => $field,
            "parameters"=>$parameters
        ];

        return $this;

    }



    /**
     * 设定返回字段
     * @param array $fields 返回字段列表
     * @return Engine $this
     */
    public function select( $fields ){
        if (is_string($fields)) {
            $fields = explode(",", $fields);
        }

        if ( !is_array($fields) ) {
            throw Excp( "输入字段列表不正确", 402, ["fields"=>$fields]);
        }

        $this->query["select"] = array_map( "trim", $fields );
        return $this;
    }


    /**
     * 执行查询, 并返回结果
     * @param integer $page     当前页码，默认为 1
     * @param integer $perpage  每页显示数量, 默认为20, 最多100
     * @return array 成功返回结果集, 失败抛出异常
     */
    public function get( $page=1, $perpage=20 ){

        // 分页信息
        $page = intval( $page );
        $perpage = intval( $perpage );
        if ( $perpage > 100 ) {
            $perpage = 20;
        }

        $qb = & $this->qb;

        // 选择字段 
        $qb->select( $this->query["select"] );

        
        // Match 查询
        if ( !empty($this->query["match"]) ) {
            $contextLength = $this->option["contextLength"];

            array_walk($this->query["match"], function($query, $idx) use ( & $qb, $contextLength ) {
                $method = ($query["operator"] == 'and') ? "whereRaw" : "orWhereRaw";
                $qb->$method("MATCH ({$query["field"]}) AGAINST (? IN NATURAL LANGUAGE MODE)", [$query["keyword"]]);

                // 读取上下文
                if ( $query["field"] == "content") {
                    $qb->selectRaw("SUBSTRING(content,
                        CASE WHEN INSTR(content,'{$query["keyword"]}') < {$contextLength}
                            THEN 1
                            ELSE INSTR(content,'{$query["keyword"]}') - {$contextLength} 
                            END,
                        CASE WHEN INSTR(content,'{$query["keyword"]}') < {$contextLength}
                            THEN {$contextLength} - INSTR(content,'{$query["keyword"]}')
                            ELSE {$contextLength}
                        END + {$contextLength} + LENGTH('{$query["keyword"]}')
                    ) as context_{$idx}");
                }

            });
        }

        // Term 查询
        if ( !empty($this->query["term"]) ) {
            array_walk($this->query["term"], function($query, $idx) use ( & $qb ) {
                
                $method = ($query["operator"] == 'and') ? "where" : "orWhere";

                // 下一版使用 5.7 JSON 查询实现
                if ( in_array($query["field"], ["tags", "categories", "users"])){
                    $method = "{$method}Raw";
                    $qb->$method("MATCH ({$query["field"]}) AGAINST (? IN BOOLEAN MODE)", ["+{$query["keyword"]}"]);
                    // MySQL精确匹配
                } else {
                    $qb->$method($query["field"], "=", $query["keyword"]);
                }
            });
        }


        // Range 查询
        if ( !empty($this->query["range"]) ) {
            $opmap =[
                "gt" => ">",
                "lt" => "<",
                "gte" => ">=",
                "lte" => "<="
            ];
            array_walk($this->query["range"], function($query, $idx) use ( & $qb, & $opmap ) {
                $method = ($query["operator"] == 'and') ? "where" : "orWhere";
                $field = $query["field"];
                $parameters = $query["parameters"];
                $ops = array_keys( $parameters );

                if ( count($ops) == 1 ) {
                    $op  = current( $ops );
                    $qb->$method("$field", $opmap[$op], $parameters[$op] );
                } else {
                    $qb->$method(function( & $qb ) use( $field, & $opmap, & $parameters, & $ops) {
                        foreach( $ops as $op ) {
                            $qb->where( "$field", $opmap[$op], $parameters[$op] );
                        }
                    });
                }
            });
        }


        // 数据排序
        if ( !empty($this->query["sort"]) ) {

            array_walk($this->query["sort"], function($query, $idx) use ( & $qb ) {
                $field = $query["field"];
                $parameters = $query["parameters"];
                $order = $parameters["order"];
                if ( !empty($order) ) {
                    $qb->orderBy($field, $order );
                }
            });
        }


        // 返回分页数据
        $response = $qb->pgArray($perpage, ['doc_id'], $link='page', $page);

        // 格式化输出
        $query = $this->query;
        $query["keywords"] = array_unique($query["keywords"]);
        array_walk( $response["data"], function( & $rs, $idx ) use($query){
            Mysql::format( $rs, $query);
        });


        // 返回查询条件
        $response["query"] = $query;

        return $response;
        
    }


    /**
     * 重置查询条件
     */
    public function reset() {

        $this->query = [
            "keywords" => [],
            "match" => [],
            "term" => [],
            "range" => [],
            "sort" => [],
            "select" => [
                "doc_id", "title", "summary", "url", "published_at", "type", "tags", "categories", 
                "users", "origin", "author", "priority", "data", "similar"
            ]
        ];

        $this->qb = $this->model->query();

        return $this;
    }


    /**
     * 格式化返回结果(高亮代码)
     */
    static public function format( & $rs, $query = []) {
        
        $keywords = $query["keywords"];
        $rs["highlight"] = [];
        $rs["context"] = [];

        // 处理上下文 context
        array_walk( $rs, function( $v, $field ) use( & $rs) {
            if ( strpos($field, "context")  === 0 && $field !== "context" ) {
                array_push($rs["context"], strip_tags($rs["$field"]) );
                unset( $rs["$field"]);
            }
        });

        // 高亮显示关键词 highlight
        if ( !empty($keywords) ) {
            $pattern = "/".implode("|", $keywords)."/i";
           
            if (!empty($rs["title"])){
                $rs["highlight"]["title"] =  preg_replace( $pattern, "<span class=\"highlight\">\${0}</span>", $rs["title"] );
            }

            if (!empty($rs["summary"])){
                $rs["highlight"]["summary"] =  preg_replace( $pattern, "<span class=\"highlight\">\${0}</span>", $rs["summary"] );
            }

            if ( !empty($rs["context"]) ) {
                $rs["highlight"]["context"]  = [];
                foreach($rs["context"] as $context ) {
                    array_push( $rs["highlight"]["context"], preg_replace( $pattern, "<span class=\"highlight\">\${0}</span>", $context ) );
                }
            }
        }
        
    }

}
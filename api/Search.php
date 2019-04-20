<?php
/**
 * Class Staff 
 * 后端管理员接口
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-03-30 01:16:05
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmse\Xpmse\Api;
                                                                                                                                                                                                                                                            use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Search extends Api {

	/**
	 * 简墨搜索引擎接口
     * 
     * 数据结构:
     * String          :doc_id         文档ID, 不存在则创建, 存在则更新. 仅支持精确查询.
     * String          :origin         文档来源, 支持精确查询, 可以按来源过滤结果.
     * String          :origin_id      内容源文档ID. 支持精确查询.
     * String          :title          标题, 支持匹配查询
     * String          :summary        摘要, 支持匹配查询
     * String          :url            访问地址, 支持匹配查询
     * TimestamptZ     :published_at   发布时间, 支持范围/精确查询, 可排序.
     * String          :content        文档正文, 支持匹配查询
     * String          :type           文档类型, 支持精确/匹配查询, 默认为 text.  text 文本, markdown Markdown格式文本, json JSON格式文本, html HTML格式文本, yaml YAML格式文本, image 图片, video 视频, audio 语音
     * Array[string]   :tags           文档标签, 支持精确/匹配查询, 可以按标签过滤结果
     * Array[string]   :categories     文档类目, 支持精确/匹配查询, 可以按类目过滤结果
     * Array[string]   :users          文档所属用户, 支持精确/匹配查询, 可以按用户过滤结果
     * String          :author         文档作者, 支持精确/匹配查询, 可以按作者过滤结果
     * Integer         :priority       用户自定义权重, 支持精确查询, 可排序.
     * Object          :data           传入数据, 不支持查询, 搜索后按原值返回.
     * Boolen          :similar        是否开启相似搜索, 默认为 0.  0 不开启, 1 开启
     * Array[string]   :context        关键词匹配上下文
     * Object[string]  :highlight      高亮显示匹配关键词, 支持 title,summary,context
     * 
	 * @param array $param [description]
	 */
	function __construct(  $option = []  ) {
		parent::__construct( $option );
    }


    /**
     * 全文检索
     */
    function fulltext( $query, $data ) {

        if ( empty($query["keywords"]) ) {
            throw new Excp("请提供至少一个关键词", 402, ["query"=>$query]);
        }
        
        if (empty($query["order"])) {
            $query["order"] = "priority asc,published_at desc";
        }


        $keywords = array_map('trim', explode(' ',  $query["keywords"]));
        $origin = trim( $query["origin"] );
        $orders = array_map('trim', explode(',',  $query["order"]));
        $select = trim( $query["select"] );

        $page = !empty($query["page"]) ? intval($query["page"]) : 1;
        $perpage = !empty($query["perpage"]) ? intval($query["perpage"]) : 20;

        

        $se = \Xpmse\Search::Engine();


        // 关键词
        foreach( $keywords as $keyword ) {
            $se->match("content", $keyword );
            $se->orMatch("title", $keyword );
            $se->orMatch("summary", $keyword );
        }

        // 内容范围
        if ( !empty($origin) ) {
            $se->term("origin", $origin );
        }

        // 排序
        foreach( $orders as $order ) {
           
            $order = array_map('trim', explode(' ',  $order));
            
            if ( count($order) == 1 ) {
                $order[1] = "asc";
            }

            if (count($order) == 2) {
                $se->sort($order[0],["order"=>$order[1]]);
            }
        }

        // 读取内容
        if ( !empty($select) ) {
            $se->select($select);
        }
        
        return $se->get( $page, $perpage );
    }
    
}
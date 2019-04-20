<?php
/**
 * 简墨搜索引擎(接口)
 * 
 * @author Max<https://github.com/trheyi>
 * @license Apache 2.0 license <https://www.apache.org/licenses/LICENSE-2.0>
 * @copyright 2019 Jianmo.ink
 */

namespace Xpmse\Search;

interface Engine {

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
     * @return bool 成功返回true, 失败返回 false
	 */
    public function push( array $data );

    /**
     * 清除文档
     * @param string $doc_id 文档ID
     * @return 成功返回 true, 失败返回 false
     */
    public function remove( string $doc_id );


    /**
     * 使用来源文档ID清除文档
     * @param string $origin 来源
     * @param string $origin_id 来源文档ID
     * @return 成功返回 true, 失败返回 false
     */
    public function removeOrigin( string $origin, string $origin_id );


    /**
     * 查询条件构造器
     * @return Engine $this
     */
    public function query();


    /**
     * 设定模糊查询条件(AND)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function match( string $field, string $keyword );

    /**
     * 设定模糊查询条件(OR)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function orMatch( string $field, string $keyword );

    /**
     * 设定精确查询条件(AND)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function term( string $field, string $keyword );

    /**
     * 设定精确查询条件(OR)
     * @param string $field 字段名称
     * @param string $keyword 关键词
     * @return Engine $this
     */
    public function orTerm( string $field, string $keyword );

    /**
     * 设定范围查询条件(AND)
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
    public function range( string $field, array $parameters );

    /**
     * 设定范围查询条件(OR)
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
    public function orRange( string $field, array $parameters );



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
    public function sort( string $field, array $parameters );



    /**
     * 设定返回字段
     * @param array|string $fields 返回字段列表
     * @return Engine $this
     */
    public function select( $fields );


    /**
     * 执行查询, 并返回结果
     * @param integer $page     当前页码，默认为 1
     * @param integer $perpage  每页显示数量, 默认为 20
     * @return array 成功返回结果集, 失败抛出异常
     */
    public function get( $page=1, $perpage=20 );

    /**
     * 重置查询条件
     */
    public function reset();

}
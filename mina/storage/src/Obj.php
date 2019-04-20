<?php
/**
 * MINA Pages 对象存储接口
 * 
 * @package      \Mina\Storage
 * @author       天人合一 <https://github.com/trheyi>
 * @copyright    Xpmse.com
 * 
 */

namespace Mina\Storage;

interface Obj {

	/**
	 * 如使用外部云存储 API，返回服务API对象
	 * @return mix
	 */
	function inst(); 


	/**
	 * 检查云存储配置是否正确
	 * @return boolean 成功返回 TRUE，失败返回 FALSE。
	 */
	function ping();


	/**
	 * 根据对象路径，读取对象完整信息
	 * @param  string $path 对象路径
	 * @param  boolean $nocache TRUE 不启用缓存, FALSE 启用缓存
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function get( $path, $nocache=false );


	/**
	 * 根据对象路径，读取对象数据
	 * @param  string $path 对象路径
	 * @param  boolean $nocache TRUE 不启用缓存, FALSE 启用缓存
	 * @return string 返回对象数据
	 */
	public function getBlob( $path, $nocache=false );



	/**
	 * 上传对象到指定路径
	 * @param  string $path 对象路径
	 * @param  string $raw 对象数据
	 * @param  boolean $replace 如对象已存在是否替换，默认为 TURE 替换
	 * @return boolean | array 返回对象完整信息, 失败返回 FALSE 
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function upload( $path, $raw, $replace = true );



	/**
	 * 追加数据到指定文件
	 * @param  string $path 对象路径
	 * @param  string $blob 当前片段数据
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function append( $path, $blob );

	/**
	 * 插入数据到目标文件指定位置
	 * @param  string $path 对象路径
	 * @param  string $blob 当前片段数据
	 * @param  int $from  起始位置
	 * @param  int $total 总字节数
	 * @return array 返回对象完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 */
	public function insert( $path, $blob, $from=0, $total=null );



	/**
	 * 根据对象路径，删除对象
	 * @param  string $path 对象路径
	 * @param  boolean $recursive  TRUE: 同事删除子目录名下所有文件 FALSE: 如包含子目录则删除所有 false 
	 * @param  boolean $ignore_notexist  TRUE: 如对象不存在函数返回 true FALSE: 如对象不存在函数返回 false 
	 * @return boolean 成功返回 true, 失败返回 false
	 */
	public function remove( $path, $recursive=true, $ignore_notexist = true );


	/**
	 * 检查对象是否存在
	 * @param  string $path 对象路径
	 * @return boolean  成功返回 TRUE, 失败返回 FALSE
	 */
	public function isExist( $path );


	/**
	 * 刷新对象缓存 (CDN) 
	 * @param  string $path 对象路径 
	 * @return boolean  成功返回 TRUE, 失败返回 FALSE 如没有CDN，则一直存在
	 */
	public function refresh( $path );


	/**
	 * 检查对象是否为已知文件类型
	 * @param  string $path 对象路径 
	 * @param  string $type 检查类型，有效值
	 *                "image" 图片
	 *                "video" 视频
	 *                "audio" 音频
	 *                "text"  文本
	 *                "html"  网页
	 *                "css"   样式表
	 *                "js"    JavaScript 脚本
	 *                "word"  Word 文档
	 *                "excel" Excel 文档
	 *                "ppt"   PowerPoint 文档
	 *                "pdf"   PDF 文档
	 *                "zip"   压缩包 ( 包含 rar/zip/tar/gz 等 )
	 *                "page"  MINA Pages 文件
	 *                "json"  JSON 文件
	 *                    
	 * @return boolean 如对象为指定类型返回 TRUE 否则返回 FALSE
	 */
	public function is_a( $path, $type );


	/**
	 * 返回前缀值
	 * @return string prefix
	 */
	public function prefix();


	/**
	 * 返回当前 cache 实例
	 * @return [type] [description]
	 */
	public function cache();

	/**
	 * 返回当前 路径
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public function getOption( $name = null );



	/**
	 * 返回本地文件镜像路径
	 * @param  [type] $path [description]
	 * @return [type]       [description]
	 */
	public function local( $path );


	/**
	 * ==============================================================
	 *   下为图片处理方法
	 * ==============================================================
	 */


	/**
	 * 返回图片处理对象
	 * @return \Intervention\Image\ImageManagerStatic $image 
	 */
	public function image(); 

	/**
	 * 读取图片信息
	 * @param  string $path 图片路径
	 * @return array $data
	 *         	  ["width"] 图片宽度
	 *         	  ["height"] 图片高度
	 *         	  ["exif"] 图片 Exif 信息
	 */
	public function info( $path );


	/**
	 * 裁切图片 
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  裁切参数
	 *                 int      ["width"]    图片宽度
	 *                 int      ["height"]   图片高度
	 *                 int      ["x"]        X 轴坐标, 默认值 0
	 *                 int      ["y"]        Y 轴坐标, 默认值 0
	 *                 float    ["ratio"]    调整图片比例 (如设置此参数，则 "width"、"height" 参数失效 )
	 *                 boolean  ["replace"]  如目标路径图片存在, 则替换原路径
	 *                 
	 * @return  array 返回目标图片完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *         	int ["width"] 图片宽度
	 *         	int ["height"] 图片高度
	 */
	public function crop( $origin, $dest, $options );



	/**
	 * 压缩图片 
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  压缩参数
	 *                 int    ["width"]    图片宽度 ( 如未设置 height，则必填 )
	 *                 int    ["height"]   图片高度 ( 如未设置 width，则必填 )
	 *                 boolean  ["replace"]  如目标路径图片存在, 则替换原路径
	 *                 
	  * @return array 返回目标图片完整信息
	 *         	string ["url"] 对象CDN访问地址
	 *         	string ["origin"] 原始图片访问地址
	 *         	string ["path"] 对象路径
	 *         	string ["mime"] 对象文件 MIME TYPE
	 *         	int ["width"] 图片宽度
	 *         	int ["height"] 图片高度
	 */
	public function resize( $origin, $dest, $options );



	/**
	 * 添加水印
	 * @param  string $origin  原始图片路径
	 * @param  string $dest    处理后的图片存储路径
	 * @param  array  $options  压缩参数
	 *                 float|string    ["x"]           水印所在的 X 轴坐标, 默认值 0， 
	 *                 							       有效值 数值/left/center/right/rand
	 *                 float|string    ["y"]           水印所在的 Y 轴坐标, 默认值 0， 
	 *                 							       有效值 数值/top/middle/bottom/rand
	 *                 string          ["text"]        文本水印
	 *                 string          ["color"]       文本水印颜色代码，默认值 #999999
	 *                 string          ["size"]        文本水印字号大小，默认值 15px
	 *                 int             ["alpha"]       水印透明度，有效值 0~100 默认值 30
	 *                 string          ["image"]       图片水印 ( 如设置此参数，则 "text" 参数失效 )
	 *                 boolean         ["replace"]     如目标路径图片存在, 则替换原路径
	 *                 
	 * @return boolean  成功返回 TRUE, 失败返回 FALSE
	 */
	public function watermark( $origin, $dest, $options );


}
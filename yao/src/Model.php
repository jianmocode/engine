<?php
/**
 * Class Model
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Illuminate\Database\Eloquent\Model as EloquentModel;
use \League\Flysystem\AdapterInterface;
use \Illuminate\Database\Eloquent\Builder;
use \Yao\Schema;
use \Yao\Arr;
use \Yao\Str;
use \Yao\Utils;

defined("YAO_PUBLIC_URL") ?: define("YAO_PUBLIC_URL", Arr::get($GLOBALS, "YAO.storage.public"));
defined("YAO_PRIVATE_URL") ?: define("YAO_PRIVATE_URL",Arr::get($GLOBALS, "YAO.storage.private"));


// 连接数据库
DB::setting();

/**
 * 数据模型 
 * see https://laravel.com/docs/5.8/eloquent
 */
class Model extends EloquentModel {

    /**
     * 公开文件访问路径
     */
    public $publicURL = YAO_PUBLIC_URL;


    /**
     * 私密的访问路径
     */
    public $privateURL = YAO_PRIVATE_URL;


    /**
     * 公开文件字段
     */
    public $publicFiles = [];
    

    /**
     * 私有文件字段
     */
    public $privateFiles = [];


    /**
     * 文件前缀
     */
    public $filePrefix = "";

    /**
     * 创建时生成的数字类型ID
     */
    public $generateId = null;
    

    /**
     * 数据结构JSON描述文件地址
     */
    protected $schemaFile = null;


    /**
     * 转化为数据结构配置
     */
    public function exportSchema() {
        
        // 读取字段详情
        $schema = [
            "fields" => [],
            "indexes" => []
        ];
        $fields = DB::table('information_schema.columns')
                        ->select('*')
                        ->where('table_name', $this->table )
                        ->get()
                        ->toArray();

        $dataTypeMap = [
            "varchar"   => "string",
            "bigint"    => "bigInteger",
            "timestamp" => "timestamp",
            "int"       => "integer",
            "text"      => "text",
            "json"      => "json"
        ];      

        // 导出字段
        foreach( $fields as $field ) {

            // 忽略主键
            if ( Arr::get($field, "COLUMN_KEY") == "PRI") {
                continue;
            }

            // 忽略自动添加字段
            if ( in_array(Arr::get($field, "COLUMN_NAME"), ["updated_at", "created_at", "deleted_at"])) {
                continue;
            }

            
            $struct = [
                "comment" => Arr::get($field, "COLUMN_COMMENT"),
                "name"    => Arr::get($field, "COLUMN_NAME"),
                "type"    => Arr::get($dataTypeMap, $field["DATA_TYPE"], "string"),
            ];

            // 空值
            if (Arr::get($field, "IS_NULLABLE") == "YES" ){
                $struct["nullable"] = true;
            }

            // 创建参数
            if ( $struct["type"] == "string" ) {
                $struct["args"] = Arr::get( $field, "CHARACTER_MAXIMUM_LENGTH", 200);
            }

            // 默认值
            if ( Arr::get($field, "COLUMN_DEFAULT", null) != null ) {
                $struct["default"] = Arr::get($field, "COLUMN_DEFAULT");
                if ( in_array($struct["type"], ["integer", "bigInteger"]) ) {
                    $struct["default"] = \intval( $struct["default"]);
                }
            }

            array_push($schema["fields"], $struct );

        }

        // 导出索引
        $indexesQuery = DB::select("SHOW INDEXES FROM {$this->table}");
        $indexesMap = [];
        foreach( $indexesQuery as $index ){

            if ( Arr::get($index, "Key_name") == "PRIMARY") {
                continue;
            }

            $name = Arr::get($index, "Key_name");
            $type = Arr::get($index, "Non_unique") == 0 ? 'unique' : 'index';
            if ( Arr::get($index, "Index_type") == "FULLTEXT" ) {
                $type = "fulltext";
            }
            $field = Arr::get( $index, "Column_name");

            $indexesMap[$name][] = [
                "name" => preg_replace("/^{$this->table}_([0-9a-zA-Z_]+)_{$type}$/", "\$1", $name),
                "type" => $type,
                "field" => $field
            ];
        }

        foreach($indexesMap as $index ) {
            $idx = current($index);
            $fields = Arr::pluck($index, "field");
            if ( count($fields) == 1 )  {
                $struct = [
                    "name" => $idx["name"]
                ];

                if ( $idx["name"] != $idx["field"] ) {
                    $struct = [
                        "field" => $idx["field"]
                    ];
                }

            } else {
                $struct = [
                    "name" => $idx["name"],
                    "field" => $fields
                ];
            }

            if ( $idx["type"] != "index") {
                $struct["type"] = $idx["type"];
            }

            array_push($schema["indexes"], $struct );
        }

        return $schema;
    }


    /**
     * 更新数据结构
     */
    public function schema() {
        
        if ( is_null( $this->schemaFile ) ) {
            throw Excp::create("未设定结构描述文件", 402);
        }

        if ( !file_exists( $this->schemaFile) || !is_readable( $this->schemaFile ) ) {
            throw Excp::create("结构描述文件不存在或不可读写", 404);
        }
        
        $schemaData = json_decode( file_get_contents($this->schemaFile), true );
        if ( !is_array($schemaData) ) {
            Utils::json_decode($this->schemaFile);
            $error = Utils::json_error();
            throw Excp::create("结构描述文件格式错误($error)", 402);
        }

        $tableName = $this->table;
        $primaryKey = $this->primaryKey;

        if( empty($tableName) ) {
            throw Excp::create("数据表未定义", 402);
        }

        if( empty($primaryKey) ) {
            throw Excp::create("数据表主键未定义", 402);
        }          
      
        $fields = Arr::get($schemaData, "fields", []);
        $indexes = Arr::get($schemaData, "indexes", []);
      
        // 创建数据表
        if ( !Schema::hasTable($tableName) ) {
            Schema::create($tableName, function ($table) use( $primaryKey ) {
                $table->bigIncrements($primaryKey)->comment("数据表主键");
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        // 删除索引
        Schema::table($tableName, function ( $table) use( $tableName,  $indexes ) {
            $indexesQuery = DB::select("SHOW INDEXES FROM {$tableName}");
            $indexesRemoved = []; 
            foreach( $indexesQuery as $index ){
                if ( Arr::get($index, "Key_name") == "PRIMARY") {
                    continue;
                }

                $key = Arr::get($index, "Key_name") ;
                if ( in_array($key, $indexesRemoved) ) {
                    continue;
                }

                array_push($indexesRemoved, $key);
                if ( Arr::get( $index, "Non_unique") ) {
                    $table->dropIndex( $key );
                } else {
                    $table->dropUnique($key);
                }
            }
        });

        // 更新数据表
        Schema::table($tableName, function ( $table) use( $tableName, $fields, $indexes ) {

            // 添加字段
            foreach( $fields as $schema ) {
                $name = Arr::get( $schema, "name", null);
                if ( empty($name) ) {
                    continue;
                }

                // 添加字段
                $method = Arr::get( $schema, "type", "string");
                $args  = Arr::get( $schema, "args", null);
                if ( is_null($args) ) {
                    $args = [$name];
                } else if ( is_array( $args )) {
                    $args = array_merge([$name], $args);
                } else {
                    $args = array_merge([$name], [$args]);
                }

                // 字段存在则更新
                if ( Schema::hasColumn( $tableName, $name) ) {

                    if( $method == "timestamp" ) {
                        continue;
                    }
                    $field = $table->$method(...$args)->change();

                // 字段不存在，则创建
                } else {
                    $field = $table->$method(...$args);
                }

                // 设定是否可为空值
                if( Arr::get( $schema, "nullable", false) ) {
                    $field->nullable();
                }

                // 设定默认值
                if ( Arr::get( $schema, "default", false) !== false ) {
                    $field->default( Arr::get( $schema, "default"));
                }

                // 设定注释
                if( Arr::get( $schema, "comment", false) ) {
                    $field->comment(Arr::get( $schema, "comment"));
                }                
            }
        });

        // 重建索引
        Schema::table($tableName, function ( $table) use( $tableName,  $indexes ) {
            foreach( $indexes as $index ) {
                $name =  Arr::get($index, "name");
                if ( empty($name) ) {
                    continue;
                }
                $method = Arr::get( $index, "type", "index");
                $keys = is_array($name) ? implode("_", $name) : $name;
                $keyName = "{$tableName}_{$keys}_{$method}";
                $args = Arr::get($index, "field", $name);
                if ( $method == "fulltext" ) {
                    DB::statement("ALTER TABLE {$tableName} ADD FULLTEXT INDEX `{$keyName}` (`$name`) WITH PARSER ngram");
                } else {
                    $table->$method( $args );
                }
            }
        });
    }



    /**
     * 处理输入文件类字段
     * @return void
     */
    public function filesInput() {

        // 文件前缀
        $filePrefix = $this->filePrefix;
        if ( strpos( $filePrefix, "@") === 0 ) {
            $prefix = substr($filePrefix, 1, strlen($filePrefix));
            $filePrefix = "{$this->$prefix}/";
        }

        // 读取文件字段
        $files = $this->getFiles();
        foreach( $files as $attr=>$isPrivate ) {

            // 单一文件路径
            if ( is_string($this->$attr) ) {
                $this->$attr = $this->writeFile( $this->$attr, $isPrivate, $filePrefix );
            
            // 二维数组
            } else {

                // 批量处理文件
                $values = $this->$attr;
                if ( !is_array($values) ) {
                    continue;
                }
                foreach( $values as $key=>$path ) {

                    // 单文件
                    if ( is_string($values[$key]) ) {
                        $values[$key] = $this->writeFile( $path, $isPrivate,  $filePrefix );
                    // 三维数组
                    } else if ( is_array( $values[$key] ) ) {
                        foreach( $values[$key] as $k=>$path ) {
                            $values[$key][$k] = $this->writeFile( $path, $isPrivate,  $filePrefix );
                        }
                    }
                }
                $this->$attr = $values;
            }
        }
    }
    
    /**
     * 处理输入文件类字段读取
     * @param array $exclude 排除字段
     * @return void
     */
    public function pathFilterURL( $exclude=[] ) {

        $files = $this->getFiles();
        foreach( $files as $attr=>$isPrivate ) {

            // 忽略未设定字段
            if ( !isset( $this->$attr) ) {
                continue;
            }

            // 设置空字段
            if ( empty($this->$attr)  ){
                $this->$attr = null;
                continue;
            }

            // 解析 JSON 字段
            if ( is_string($this->$attr) ) {
                $v = json_decode($this->$attr, true);
                if ( $v !== false ) {
                    $this->$attr = $v;
                }
            }
            
            if ( in_array($attr, $exclude) || empty($this->$attr)  ) {
                continue;
            }

            // 单一文件路径 ["path"=>"...", "url"=>"..."]
            if ( is_string(Arr::get($this->$attr, "path", false)) ) {

                $this->$attr = $this->$attr["path"];

            // KEY VALUE 数组  logo["dark"] = ["path"=>"...", "url"=>"..."] ||  logo[0] = ["path"=>"...", "url"=>"..."]
            } else if( is_array($this->$attr) &&  is_string(Arr::get(current($this->$attr), "path", false)) ) {

            // // 二维数组 logo[0] = ["path"=>"...", "url"=>"..."]
            // } else if( is_string(Arr::get($this->$attr, "0.path", false)) ) {

                // 批量处理文件
                $values = $this->$attr;
                foreach( $values as $key=>$path ) {
                    if ( empty($values[$key]) ) {
                        continue;
                    }

                    // 单文件
                    if ( is_string(Arr::get($values[$key], "path", false)) ) {
                     
                        $values[$key] =  Arr::get($values[$key], "path");

                    // 三维数组
                    } else if ( is_string( Arr::get($values[$key], "0.path", false) ) ) {
                        foreach( $values[$key] as $k=>$path ) {
                            if (  is_string( Arr::get($values[$key][$k], "path", false)) ) { 
                                $values[$key][$k] =  Arr::get($values[$key][$k], "path");
                            }
                        }
                    }
                }
                $this->$attr = $values;
            }
        }
    }


    /**
     * 处理输入文件类字段读取
     * @param array $exclude 排除字段
     * @return void
     */
    public function pathWithURL( $exclude=[] ) {

        $files = $this->getFiles();
        foreach( $files as $attr=>$isPrivate ) {
            if ( in_array($attr, $exclude) || empty($this->$attr)  ) {
                continue;
            }

            // 单一文件路径
            if ( is_string($this->$attr) && !Str::isURL($this->$attr)) {

                $this->$attr =  [
                    "url"=>$isPrivate ? "{$this->privateURL}/{$this->$attr}" : "{$this->publicURL}/{$this->$attr}",
                    "path" => $this->$attr
                ];
            
            // 二维数组
            } else if( is_array($this->$attr) ) {

                // 批量处理文件
                $values = $this->$attr;
                foreach( $values as $key=>$path ) {
                    if ( empty($values[$key]) ) {
                        continue;
                    }
                    // 单文件
                    if ( is_string($values[$key]) && !Str::isURL($values[$key]) ) {
                        $values[$key] =  [
                            "url" => $isPrivate ? "{$this->privateURL}/{$values[$key]}" : "{$this->publicURL}/{$values[$key]}",
                            "path" => $values[$key],
                        ];

                    // 三维数组
                    } else if ( is_array( $values[$key] ) ) {
                        foreach( $values[$key] as $k=>$path ) {
                            if (  is_string( $values[$key][$k]) && !Str::isURL( $values[$key][$k]) ) { 
                                $values[$key][$k] =  [
                                    "url" => $isPrivate ? "{$this->privateURL}/{$values[$key][$k]}" : "{$this->publicURL}/{$values[$key][$k]}",
                                    "path" => $values[$key][$k]
                                ];
                            }
                        }
                    }
                }
                $this->$attr = $values;
            }
        }
    }

    /**
     * 处理输入文件类字段读取
     * @param array $exclude 排除字段
     * @return void
     */
    public function pathToURL( $exclude=[] ) {

        $files = $this->getFiles();
        foreach( $files as $attr=>$isPrivate ) {
            
            if ( in_array($attr, $exclude) || empty($this->$attr)  ) {
                continue;
            }

            // 单一文件路径
            if ( is_string($this->$attr) && !Str::isURL($this->$attr)) {

                $this->$attr =  $isPrivate ? "{$this->privateURL}/{$this->$attr}" : "{$this->publicURL}/{$this->$attr}";
            
            // 二维数组
            } else if( is_array($this->$attr) ) {

                // 批量处理文件
                $values = $this->$attr;
                foreach( $values as $key=>$path ) {
                    if ( empty($values[$key]) ) {
                        continue;
                    }

                    // 单文件
                    if ( is_string($values[$key]) && !Str::isURL($values[$key]) ) {
                     
                        $values[$key] =  $isPrivate ? "{$this->privateURL}/{$values[$key]}" : "{$this->publicURL}/{$values[$key]}";

                    // 三维数组
                    } else if ( is_array( $values[$key] ) ) {
                        foreach( $values[$key] as $k=>$path ) {
                            if (  is_string( $values[$key][$k]) && !Str::isURL( $values[$key][$k]) ) { 
                                $values[$key][$k] =  $isPrivate ? "{$this->privateURL}/{$values[$key][$k]}" : "{$this->publicURL}/{$values[$key][$k]}";
                            }
                        }
                    }
                }
                $this->$attr = $values;
            }
        }

    }


    /**
     * 分页查询结果集
     * 
     * @param \Illuminate\Database\Eloquent\Builder $qb 查询器实例
     * @param int|string $page 当前页码, 默认为 1
     * @param int|string $perpage 每页显示记录数量, 默认为 20
     * @param array $params 查询参数
     * @param array $pageName 分页参数名称
     * @param array $columns 查询结果集
     * 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator 分页对象
     * 
     */
    public static function paginator(  Builder & $qb, $page=1, $perpage=20, $params=[], $pageName="page", $columns=['*'] ) {

        $params = empty($params) ? $_GET : $params;
        $uri = explode("?", Arr::get($_SERVER, "REQUEST_URI"));
        $path = current($uri);
        if ( array_key_exists($pageName, $params)) {
            unset( $params[$pageName] );
        }
        $paginator = $qb->paginate( $perpage, ["*"], $pageName, $page);
        $paginator->appends($params);
        $paginator->setPath($path);
        return $paginator;
    }


    /**
     * 读取文件字段清单
     * @return array 文件字段清单
     */
    public function getFiles() {

        $files = [];
        if ( !empty($this->publicFiles) ) {
            $files = array_merge(
                $files,
                array_combine( $this->publicFiles, array_fill(0, count($this->publicFiles), false) )
            );
        }

        if ( !empty($this->privateFiles) ) {
            $files = array_merge(
                $files,
                array_combine( $this->privateFiles, array_fill(0, count($this->privateFiles), true) )
            );
        }

        return $files;
    }


    /**
     * 保存文件
     * @param $path 文件路径
     * @param $private 是否为私有文件
     * @param $perfix 文件前缀
     * @return string 文件名称;
     */
    private function writeFile( $path, $private=false, $perfix="" ) {

        if ( !is_string($path)) {
            return $path;
        }

        self::debug("writeFile: {$path}");
        if ( strpos( $path, "@") !== 0 ) {
            return $path;
        }
        
        // 处理有 @ 抓取标志的文件 (抓取到本地)
        $path = substr($path, 1, strlen($path));
        if ( filter_var($path, FILTER_VALIDATE_URL) === FALSE &&  !file_exists($path) ) {
            throw Excp::create("文件不存在({$path})", 404);
        }

        // 根据文件路径/网址设定文件名称
        $ext = pathinfo($path, PATHINFO_EXTENSION);         
        if ( Str::isURL($path) ){
            $name = FS::getPathNameByURL( $path, $perfix );
        } else {
            $name = FS::getPathName( $ext, $perfix );
        }
        
        // 存储文件
        $stream = fopen($path, 'r');
        FS::writeStream($name, $stream, [
            'visibility' => $private ? AdapterInterface::VISIBILITY_PRIVATE : AdapterInterface::VISIBILITY_PUBLIC
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $name;
    }


    /**
     * 打印调试信息
     */
    public static function debug( $message, $context=[] ) {
        if ( Arr::get( $GLOBALS, "YAO.debug", false ) ) {
            Log::write("debug")->debug( $message, $context );
        }
    }

    /**
     * 根据选择字段和许可清单，过滤选择字段
     * 
     * @param array|string $select        字段列表 foo0,foo1.bar1,foo1.bar2 
     * @param array|string $allow_fields 许可数值清单 foo0,foo1.bar1,foo1.bar2  
     * 
     * @return array 许可的字段清单
     */
    public static function fieldsFilter( $select, $allow_fields ) {
        
        $select = Str::explodeAndTrim(",", $select);
        $allow_fields  = Str::explodeAndTrim(",", $select);

        $select = Arr::dot( $select );
        $allow_fields =Arr::dot( $allow_fields);
        $select = array_intersect( $select, $allow_fields );
        if ( empty($select) ) {
            throw Excp::create("未设定选择字段", 402);
        }
        return Arr::explode( $select );
    }

}
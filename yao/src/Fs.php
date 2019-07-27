<?php
/**
 * Class FS
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \League\Flysystem\Filesystem;
use \League\Flysystem\MountManager as Manager;
use \League\Flysystem\Util\MimeType;

/**
 * 文件管理
 * 
 * see https://flysystem.thephpleague.com/docs/usage/filesystem-api
 * 
 * 
 * 使用示例
 * 
 * ```php
 * <?php 
 * use \Yao\FS;
 * 
 * FS::write("hello/world.md", "这是一个测试文件");
 * 
 * $stream = fopen("https://www.baidu.com", 'r');
 * $response = FS::writeStream("hello/world.html", $stream);
 * if (is_resource($stream)) {
 *     fclose($stream);
 * }
 * 
 * ...
 * ```
 * 
 * 
 * 配置信息
 * 
 * ```php
 * ...
 * "storage" => [
 * 
 *      "public" => "https://cdn.vpin.biz", // 默认公共文件访问地址
 *      "private" => "https://private-cdn.vpin.biz", // 默认私密文件访问地址
 * 
 *       // 数据同步选项 [必填]
 *       "options" => [
 *           "sync" => true, // 是否同步到 Remote, 默认为 true
 *           "backup" => true, // 是否同时保存备份, 默认为 false
 *       ],
 *
 *       // 本地存储 [必填]
 *       "local" => [  
 *           "adapter" => "\\League\\Flysystem\\Adapter\\Local",
 *           "setting" => [
 *               "/data/stor/upload",
 *               LOCK_EX,
 *               0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
 *               [
 *                   'file' => [
 *                       'public' => 0744,
 *                       'private' => 0700,
 *                   ],
 *                   'dir' => [
 *                       'public' => 0755,
 *                       'private' => 0700,
 *                   ]
 *               ]
 *           ]
 *       ],
 *
 *       // 远程存储 [选填]
 *       "remote" => [
 *           "adapter" => "\\League\\Flysystem\\Adapter\\Local",
 *           "setting" => [
 *               "/data/stor/remote",
 *               LOCK_EX,
 *               0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
 *               [
 *                   'file' => [
 *                       'public' => 0744,
 *                       'private' => 0700,
 *                   ],
 *                   'dir' => [
 *                       'public' => 0755,
 *                       'private' => 0700,
 *                   ]
 *               ]
 *           ]
 *       ],
 *
 *       // 备份存储 [选填]
 *       "backup" => [
 *           "adapter" => "\\League\\Flysystem\\Adapter\\Local",
 *           "setting" => [
 *               "/data/stor/backup",
 *               LOCK_EX,
 *               0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
 *               [
 *                   'file' => [
 *                       'public' => 0700,
 *                       'private' => 0700,
 *                   ],
 *                   'dir' => [
 *                       'public' => 0700,
 *                       'private' => 0700,
 *                   ]
 *               ]
 *           ]
 *       ],
 *  ],
 * ...
 * 
 * ```
 * 
 * 
 */
class FS {

    /**
     * MountManager 实例
     * @var \League\Flysystem\MountManager 
     */
    public static $manager = null;

    /**
     * 有效配置 
     * @var array
     */
    protected static $lived = [
        "local" => false,
        "remote" => false,
        "backup" => false
    ];

    /**
     * 配置选项
     * 
     * - sync bool 是否同步到远程
     * - backup bool 是否同时备份
     * 
     * @var array 
     */
    protected static $option = [
        "sync" => true,
        "backup" => false,
    ];


    /**
     * 同步到远程的方法
     */
    protected static $autoSyncMethods =[
        "write", 
        "writeStream", 
        "update", 
        "updateStream",
        "put",
        "putStream",
        "delete",
        "readAndDelete",
        "rename",
        "copy",
        "createDir",
        "deleteDir",
        "setVisibility",
    ];

    /**
     * 是否同步数据到远程
     */
    public static function autoSync() {
        return self::$option["sync"] &&  self::$lived["remote"];
    }

    /**
     * 是否同时备份数据
     */
    public static function autoBackup() {
        return self::$option["sync"] &&  self::$lived["remote"];
    }

    /**
     * 服务器配置
     */
    public static function setting() {

        if ( !FS::$manager instanceof Manager ) {

            $adapters = [];
            $defaults = [
                "sync" => true,
                "backup" => false,
            ];

            $config = $GLOBALS["YAO"]["storage"];
            self::$option = !empty($config["option"]) ? $config["option"] : $defaults;

            // Local Adapter
            if ( !array_key_exists( "local", $config) || empty( $config["local"]) ) {
                throw new Excp("存储配置参数错误(local is null)", 402 );
            }

            $local = $config["local"];
            if ( !class_exists( $local["adapter"]) ) {
                throw new Excp("存储配置参数错误({$local["adapter"]} not found)", 404 );
            }

            $localAdapter = new $local["adapter"](...$local["setting"]);
            $adapters["local"] = new Filesystem($localAdapter);
            self::$lived["local"] = true;

            // Remote Adapter
            if ( array_key_exists( "remote", $config) &&  !empty( $config["remote"]) ) {
                $remote = $config["remote"];
                if ( !class_exists( $remote["adapter"]) ) {
                    throw new Excp("存储配置参数错误({$remote["adapter"]} not found)", 404 );
                }
                $remoteAdapter = new $remote["adapter"](...$remote["setting"]);
                $adapters["remote"] = new Filesystem($remoteAdapter);
                self::$lived["remote"] = true;
            }

            // Backup Adapter
            $backupAdapter = null;
            if ( array_key_exists( "backup", $config) && !empty($config["backup"]) ) {
                $backup = $config["backup"];
                if ( !class_exists( $backup["adapter"]) ) {
                    throw new Excp("存储配置参数错误({$backup["adapter"]} not found)", 404 );
                }
                $backupAdapter = new $backup["adapter"](...$backup["setting"]);
                $adapters["backup"] = new Filesystem($backupAdapter);
                self::$lived["backup"] = true;
            }

            // 创建 Manager
            self::$manager = new Manager($adapters);
        }
    }


    /**
     * 自动成文件名称
     * 
     * @param string $ext 文件扩展名
     * @param string $prefix 文件前缀
     * @return string 唯一文件名称
     */
    public static function getPathName( string $ext, string $prefix="" ) {
        
        $name = Str::uniqid();
        $path = date("Y") . "/" .date("m") . "/" . date("d") . "/" . substr($name, 0, 2) . "/" . substr($name, 2, 2);
        
        return "{$prefix}{$path}/{$name}.{$ext}";
    }

    /**
     * 自动成文件名称(网址)
     * 
     * @param string $url 网址
     * @param string $prefix 文件前缀
     * @return string 唯一文件名称
     */
    public static function getPathNameByURL( string $url, string $prefix="" ) {
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $ext = $ext ? ".{$ext}" : "";
        $name = number_format(hexdec(md5($url)), 0, "", "");
        $path = date("Y") . "/" .date("m") . "/" . date("d") . "/" . substr($name, 0, 2) . "/" . substr($name, 2, 2);
        return "{$prefix}{$path}/{$name}{$ext}";
    }


    /**
     * 根据 MIME Type 获取扩展名
     */
    public static function getExt( string $mimeType ) {
        $map = [
            "jpe" => "jpg"
        ];
        $mimeTypeToExtensionMap = array_flip( MimeType::getExtensionToMimeTypeMap() );
        $ext = Arr::get($mimeTypeToExtensionMap, $mimeType, "txt");
        if ( Arr::get($map, $ext, false) ){
            $ext = Arr::get($map, $ext);
        }
        return $ext;
    }


    /**
     * Pass methods onto MountManager.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {

        // 写入类
        if ( in_array( $method, self::$autoSyncMethods )  ) {
            
            $local = $parameters; 
            $local[0] = "local://{$local[0]}";
            $response = self::$manager->{$method}(...$local);
            if ( $response === false ) {
                return $response;
            }

            // 同步到服务器
            if ( self::autoSync() ) {
                $remote = $parameters; 
                $remote[0] = "remote://{$remote[0]}";
                $response = self::$manager->{$method}(...$remote);
                if ( $response === false ) {
                    Excp::create("同步文件失败( {$path} )", 500 )->log();
                }
            }
            
            // 同步到备份存储
            if ( self::autoBackup() ) {
                $backup = $parameters; 
                $backup[0] = "backup://{$backup[0]}";
                $response = self::$manager->{$method}(...$backup);
                if ( $response === false ) {
                    Excp::create("备份文件失败( {$path} )", 500 )->log();
                }
            }

            return $response;
        }

        // 读取类
        $parameters[0] = "local://{$parameters[0]}";
        return self::$manager->{$method}(...$parameters);
    }

}

FS::setting();
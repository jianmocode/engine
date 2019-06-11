Yao\FS
===============

文件管理

see https://flysystem.thephpleague.com/docs/usage/filesystem-api


使用示例

```php
<?php
use \Yao\FS;

FS::write("hello/world.md", "这是一个测试文件");

$stream = fopen("https://www.baidu.com", 'r');
$response = FS::writeStream("hello/world.html", $stream);
if (is_resource($stream)) {
    fclose($stream);
}

...
```


配置信息

```php
...
"storage" => [

     "public" => "https://cdn.vpin.biz", // 默认公共文件访问地址
     "private" => "https://private-cdn.vpin.biz", // 默认私密文件访问地址

      // 数据同步选项 [必填]
      "options" => [
          "sync" => true, // 是否同步到 Remote, 默认为 true
          "backup" => true, // 是否同时保存备份, 默认为 false
      ],

      // 本地存储 [必填]
      "local" => [
          "adapter" => "\\League\\Flysystem\\Adapter\\Local",
          "setting" => [
              "/data/stor/upload",
              LOCK_EX,
              0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
              [
                  'file' => [
                      'public' => 0744,
                      'private' => 0700,
                  ],
                  'dir' => [
                      'public' => 0755,
                      'private' => 0700,
                  ]
              ]
          ]
      ],

      // 远程存储 [选填]
      "remote" => [
          "adapter" => "\\League\\Flysystem\\Adapter\\Local",
          "setting" => [
              "/data/stor/remote",
              LOCK_EX,
              0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
              [
                  'file' => [
                      'public' => 0744,
                      'private' => 0700,
                  ],
                  'dir' => [
                      'public' => 0755,
                      'private' => 0700,
                  ]
              ]
          ]
      ],

      // 备份存储 [选填]
      "backup" => [
          "adapter" => "\\League\\Flysystem\\Adapter\\Local",
          "setting" => [
              "/data/stor/backup",
              LOCK_EX,
              0002, // \League\Flysystem\Adapter\Local::DISALLOW_LINKS,  \League\Flysystem\Adapter\Local:SKIP_LINKS 0001
              [
                  'file' => [
                      'public' => 0700,
                      'private' => 0700,
                  ],
                  'dir' => [
                      'public' => 0700,
                      'private' => 0700,
                  ]
              ]
          ]
      ],
 ],
...

```


* Class name: FS
* Namespace: Yao





Properties
----------


### $manager

    public \League\Flysystem\MountManager $manager = null

MountManager 实例



* Visibility: **public**
* This property is **static**.


### $lived

    protected array $lived = array("local" => false, "remote" => false, "backup" => false)

有效配置



* Visibility: **protected**
* This property is **static**.


### $option

    protected array $option = array("sync" => true, "backup" => false)

配置选项

- sync bool 是否同步到远程
- backup bool 是否同时备份

* Visibility: **protected**
* This property is **static**.


### $autoSyncMethods

    protected mixed $autoSyncMethods = array("write", "writeStream", "update", "updateStream", "put", "putStream", "delete", "readAndDelete", "rename", "copy", "createDir", "deleteDir", "setVisibility")

同步到远程的方法



* Visibility: **protected**
* This property is **static**.


Methods
-------


### autoSync

    mixed Yao\FS::autoSync()

是否同步数据到远程



* Visibility: **public**
* This method is **static**.




### autoBackup

    mixed Yao\FS::autoBackup()

是否同时备份数据



* Visibility: **public**
* This method is **static**.




### setting

    mixed Yao\FS::setting()

服务器配置



* Visibility: **public**
* This method is **static**.




### getPathName

    string Yao\FS::getPathName(string $ext, string $prefix)

自动成文件名称



* Visibility: **public**
* This method is **static**.


#### Arguments
* $ext **string** - &lt;p&gt;文件扩展名&lt;/p&gt;
* $prefix **string** - &lt;p&gt;文件前缀&lt;/p&gt;



### __callStatic

    mixed Yao\FS::__callStatic(string $method, array $parameters)

Pass methods onto MountManager.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**



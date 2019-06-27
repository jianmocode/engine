Yao\Image
===============

图像处理函数

see http://image.intervention.io/use/basics


* Class name: Image
* Namespace: Yao
* Parent class: Intervention\Image\ImageManagerStatic







Methods
-------


### captcha

    \Yao\mix Yao\Image::captcha(\Yao\mix $option)

生成/校验验证码

see https://github.com/Gregwar/Captcha

配置参数 $option :

- :width  验证码宽度, 默认为 150
- :height 验证码高度, 默认为 40
- :font   字体, 默认为 null 随机字库

* Visibility: **public**
* This method is **static**.


#### Arguments
* $option **Yao\mix** - &lt;p&gt;配置参数数组|用户填写的验证码内容字符串.&lt;/p&gt;



### qrcode

    string Yao\Image::qrcode(string $text, array $option, integer $ttl)

生成二维码

Redis cache key: image:qrcode:[text+option]

see https://github.com/endroid/qr-code

配置参数 $option :

- :writer: 'svg'
- :size: 300
- :margin: 10
- :foreground_color
     - r: 0
     - g: 0,
     - b: 0
- :background_color
     - r: 255
     - g: 255,
     - b: 255
- :error_correction_level: low # low, medium, quartile or high
- :encoding: UTF-8
- :label: Scan the code
- :label_font_size: 20
- :label_alignment: left # left, center or right
- :label_margin: { b: 20 }
- :logo_path: '%kernel.root_dir%/../vendor/endroid/qr-code/assets/images/symfony.png'
- :logo_width: 150
- :logo_height: 200
- :validate_result: false # checks if the result is readabl
- :writer_options
  - :exclude_xml_declaration: true

* Visibility: **public**
* This method is **static**.


#### Arguments
* $text **string** - &lt;p&gt;二维码文件&lt;/p&gt;
* $option **array** - &lt;p&gt;配置参数&lt;/p&gt;
* $ttl **integer** - &lt;p&gt;缓存时间单位秒 ( 默认为0 不缓存数据, -1 为永久缓存)&lt;/p&gt;



<?php
/**
 * Class Str
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Yao\Excp;
use \Yao\Arr;
use \Yao\Redis;
use Intervention\Image\ImageManagerStatic;
use Endroid\QrCode\QrCode;

/**
 * 图像处理函数
 * 
 * see http://image.intervention.io/use/basics
 * 
 */
class Image extends ImageManagerStatic {

    /**
     * 生成二维码
     * 
     * Redis cache key: image:qrcode:[text+option]
     * 
     * see https://github.com/endroid/qr-code
     * 
     * 配置参数 $option : 
     * 
     * - :writer: 'svg'
     * - :size: 300
     * - :margin: 10
     * - :foreground_color 
     *      - r: 0
     *      - g: 0, 
     *      - b: 0 
     * - :background_color
     *      - r: 255
     *      - g: 255, 
     *      - b: 255 
     * - :error_correction_level: low # low, medium, quartile or high
     * - :encoding: UTF-8
     * - :label: Scan the code
     * - :label_font_size: 20
     * - :label_alignment: left # left, center or right
     * - :label_margin: { b: 20 }
     * - :logo_path: '%kernel.root_dir%/../vendor/endroid/qr-code/assets/images/symfony.png'
     * - :logo_width: 150
     * - :logo_height: 200
     * - :validate_result: false # checks if the result is readabl
     * - :writer_options
     *   - :exclude_xml_declaration: true
     * 
     * @param string $text 二维码文件
     * @param array  $option  配置参数
     * @param int    $ttl 缓存时间单位秒 ( 默认为0 不缓存数据, -1 为永久缓存)
     * 
     * @return string 二维码文件字符串
     * 
     */
    public static function qrcode( $text, array $option=[], int $ttl=0 ) {

        if ( $ttl !== 0 ) {
            
            $cache = "image:qrcode:" . md5( json_encode([$text, $option]) );

            // 从缓存中读取
            $blob = Redis::get($cache);
            if ( $blob ) {
                return $blob;
            }
        }

        $qrCode = new QrCode( $text );

        if ( Arr::has($option, "writer") ) {
            $option["writer_by_name"] = $option["writer"];
            Arr::forget($option, ["writer"] );
        }

        // Config
        foreach( $option as $method=>$value ) {
            $method = "set" . implode("", array_map("ucfirst", explode("_", strtolower($method))));
            $qrCode->$method( $value );
        }

        $blob = $qrCode->writeString();

        if ( $ttl > 0 ) {
            Redis::set($cache, $blob, $ttl );// 写入缓存
        } else if ( $ttl < 0  ) {
            Redis::set($cache, $blob );// 写入缓存
        }
        
        return $blob;
    }

}
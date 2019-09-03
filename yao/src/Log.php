<?php
/**
 * Class Log
 * 
 * @package Yao
 * @version $Revision$
 * @author Max<https://github.com/trheyi>
 * @copyright 2019 Vpin.biz
 * @link https://www.vpin.biz
 */

namespace Yao;
use \Monolog\Logger;
use \Monolog\Processor\IntrospectionProcessor;
use \Monolog\Processor\WebProcessor;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;


/**
 * 日志
 * 
 * 示例 
 * 
 * ```php
 * <?php
 * use \Yao\Log;
 * 
 * $log = new log('access');
 * 
 * // $log = Log::write('access');
 * 
 * // add records to the log
 * $log->debug('message', ['foo', 'bar']);
 * $log->info('message');
 * $log->notice('message', ['foo', 'bar']);
 * $log->warning('Foo', ['foo', 'bar']);
 * $log->error('Bar', ['foo', 'bar']);
 * $log->critical('message', ['foo', 'bar']);
 * $log->alert('message', ['foo', 'bar']);
 * $log->emergency('message', ['foo', 'bar']);
 * 
 * ```
 * 
 * 配置 `/yao/config.inc.php`
 * 
 * ```php
 * ...
 * "logger" =>[
 *      "access" =>["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-access.log", 'debug']],
 *      "error" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-error.log", 'debug']],
 *      "debug" => ["handler"=>"Monolog\\Handler\\StreamHandler", "args"=>["/logs/yao-debug.log", 'debug']],
 *      ...
 *      ":channel" => ["handler"=>":CLASS", "args"=>[...:arg]]
 * ],
 * ...
 * ```
 * 
 * - see https://github.com/Seldaek/monolog
 * - see https://github.com/php-fig/log/blob/master/Psr/Log/LoggerInterface.php
 * 
 */
class Log extends Logger {

    /**
     * 构造函数
     * @param string $name 日志通道 
     * @return Log 
     */
    public function __construct(string $name) {

        $logger = $GLOBALS["YAO"]["logger"];
        $handlers = [];
        if ( array_key_exists("{$name}", $logger) ) {
            $class = $logger[$name]["handler"];
            $args = $logger[$name]["args"];
            if (class_exists($class) && is_array($args) ) {
                $handler = new $class( ...$args );
                $handler->setFormatter(new ColoredLineFormatter());
                $handlers[] = $handler;
            }
        }
        
        parent::__construct( $name, $handlers, [ 
            new IntrospectionProcessor(),
            new WebProcessor()
        ]);
        
    }

    /**
     * 动态添加/修改日志通道
     * @param string $name 通道名称
     * @param string|array $config 通道配置或文件名称
     * @return void
     */
    public static function channel( string $name, $config ) {

        if( is_string($config) ) {
            $GLOBALS["YAO"]["logger"][$name] = [
                "handler"=>"Monolog\\Handler\\StreamHandler", 
                "args"=>[$config, 'debug']
            ];
            return;
        } else if ( is_array($config) ) {
            $GLOBALS["YAO"]["logger"][$name] = $config;
            return;
        }

        throw Excp::create("日志同道配置错误", 400, ["config"=>$config]);
    }

    /**
     * 创建Log对象
     * 
     * @param string $name 日志通道 
     * @return Log 
     */
    public static function write( string $name) {
        return new Self($name);
    }

}
<?php
 namespace Xpmse\Model;
 
// Vendor autoload
// $autoload = realpath("/code/yao/vendor/autoload.php");
// include_once($autoload);

/**
 * MINA PAGE 页面数据解析模块
 *
 * CLASS P
 *
 * USEAGE:
 *
 */

use \Yao\Excp;
use \Yao\Route;
use \Yao\Log;
use \Yao\Arr;
use \Mina\Cache\Redis as Cache;
use \Mina\Storage\Local as Storage;

function debug() {
    $args = func_get_args();
    foreach ($args as $arg ) {

        if ( is_string($arg) ) {
            echo $arg;
        } else {
            echo  json_encode($arg,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        }
    }
}

class YaoApi {

    private $json_text = null;
    private $json_data = null;
    private $version  = "1.0";

    function __construct( $json_text, $render = null ) {

         // + 多语言替换内容源支持
         if ( $render != null && !empty($render->lang) && !empty($render->local) ) {
            
            $json_data = json_decode( $json_text, true );
            if ( $json_data === false ) {
                Utils::json_decode($json_text);
            }
            $page = $json_data["page"];
            $json_data["data"] = $render->lang->data( $json_data["data"], $page, $render->local );
            $json_text = json_encode( $json_data );
            $GLOBALS["_SYS"]["lang"] = $render->local;
        }

		$this->json_text = $json_text;
        $this->json_data = json_decode( $this->json_text, true );
        
        // + 系统信息
        $GLOBALS["_SYS"]["page"] = $this->json_data["page"];
        $GLOBALS["_SYS"]["build"] = $this->json_data["build"];
        $GLOBALS["_SYS"]["version"] = $this->json_data["version"];

        if ( $this->json_data["version"] ) {
            $this->version =  $this->json_data["version"];
        }

        Arr::binds( $this->json_data, [
            "__var" => $GLOBALS['_VAR'],
            "__sys" => $GLOBALS['_SYS'],
            "__get" => $_GET,
            "__post" => $_POST
        ]);


		// 配置 MINA Helper
		\Mina\Template\Helper::init([
			"cache" => null, // new Cache($cacheOptions),
			"storage"=> null, // new Storage($storageOptions),
			"fonts" => [],  //Utils::fonts(),
			"debug" =>  $_GET['debug']
		]);
    }

    function getData(){

        $this->json_data['data'] = is_array($this->json_data['data']) ? $this->json_data['data'] : [];
        foreach ($this->json_data['data'] as $field => $params ) {
            $data = [];
            if ( Arr::has($params, "api") ) {

                // 临时设定
                $domain_groups = [
                    "vpin.biz" => [
                        "default" => "/apps/vpin/backend/api/public",
                        "kol" => "/apps/vpin/backend/api/kol",
                        "vpin" => "/apps/vpin/backend/api/vpin",
                        "agent" => "/apps/vpin/backend/api/agent",
                    ]
                ];
                $domain_groups["vpin.ink"] = $domain_groups["vpin.biz"];
                Route::setGroups($domain_groups["vpin.ink"]);

                // 设定路由分组
                $api = $params["api"];
                $query = Arr::get($params, "query", []);

                try {
                    $response = Route::exec( $api, $query );

                } catch ( Excp $e  ){
                    $response = [
                        "code" => $e->getCode(),
                        "message"=> $e->getMessage(),
                        "api" => $api,
                        "query" => $query
                    ];
                }
                $this->json_data['data'][$field] = $data[$field] = $response;

            } else {
                $this->json_data['data'][$field] = $data[$field] = $params;
            }
            // 更新数据
            Arr::binds( $this->json_data['data'], $data);

        }

		// 调试信息
		if ( $_GET['debug'] ) {
            debug("<!-- _SYS: \n", $GLOBALS['_SYS'] , "\n -->\n");
			debug("<!-- _VAR: \n", $GLOBALS['_VAR'] , "\n -->\n");
			debug("<!-- _GET: \n", $_GET , "\n -->\n");
			debug("<!-- _POST: \n", $_POST , "\n -->\n");
			debug("<!-- _DATA:\n", $data , "\n -->\n");
		}
        return $this->json_data;
    }

}
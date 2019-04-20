<?php
namespace Xpmse\Model;

/**
 * MINA PAGE 页面数据解析模块
 *
 * CLASS P
 *
 * USEAGE:
 *
 */

use \Xpmse\Conf;
use \Xpmse\Utils;
use \Xpmse\Excp;
use \Mina\Cache\Redis as Cache;
use \Mina\Storage\Local as Storage;
use \Xpmse\Openapi;

@session_start();

$GLOBALS['_SYS'] =  [
	"location" => Utils::getLocation(),
];



class Data {

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

		$this->replaceVars( $json_text );
		$this->json_text = $json_text;
        $this->json_data = json_decode( $this->json_text, true );

        // + 系统信息
        $GLOBALS["_SYS"]["page"] = $this->json_data["page"];
        $GLOBALS["_SYS"]["build"] = $this->json_data["build"];
        $GLOBALS["_SYS"]["version"] = $this->json_data["version"];

        if ( $this->json_data["version"] ) {
            $this->version =  $this->json_data["version"];
        }

		$cacheOptions = [
			"engine" => 'redis',
			"prefix" => '_pagesStorage:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd" => Conf::G("mem/redis/password")
		];

		$storageOptions = [
			"prefix" => Conf::G("storage/local/bucket/public/root") . "/helper",
			"url" => "/static-file/helper",
			"origin" => "/static-file/helper",
			"cache" => array_merge($cacheOptions,[
				"engine" => "redis",
				"raw" =>28800,
				"info" => 28800
			])
		];

		// 配置 MINA Helper
		\Mina\Template\Helper::init([
			"cache" => new Cache($cacheOptions),
			"storage"=> new Storage($storageOptions),
			"fonts" => Utils::fonts(),
			"debug" =>  $_GET['debug']
		]);

    }
    

	function getData() {
		
		$data = []; $static = [];
		$this->json_data['data'] = is_array($this->json_data['data']) ? $this->json_data['data'] : [];


		foreach ($this->json_data['data'] as $var => $query_options ) {

			if ( isset($query_options['api']) ) {

				$query_options['query'] = is_array($query_options['query']) ? $query_options['query'] : [];
				$query_options['query']['instance_name']=$_SERVER['JM_INSTS'];
				$query_options['data'] = is_array($query_options['data']) ?  $query_options['data'] : [];
				$query_options['files'] = is_array($query_options['files']) ?  $query_options['files'] : [];
				
				// 集成数据
				$this->assetValue( $query_options['query'], $data );
				$this->assetValue( $query_options['data'], $data );
                $this->assetValue( $query_options['files'], $data );
                
                // API请求版本
                $version = empty($query_options['version']) ? $this->version : $query_options['version'];

                // 支持2.0版本请求
                if( $version == "2.0") {
                    $data[$var] = $this->queryV2( 
                        $query_options['api'], 
                        $query_options['query'], $query_options['data'], $query_options['files'] 
                    );

                } else {
                    try {
                        $data[$var] = $this->query( 
                            $query_options['api'], 
                            $query_options['query'], $query_options['data'], $query_options['files'] );
                    } catch( Excp $e ) {
                        $data[$var] = $e->toArray();
                    }
                }

			} else {
				$static[$var] = $query_options;
			}

			$this->assetValue( $static, $data );
			$data = array_merge($data, $static);
		}



		// 调试信息
		if ( $_GET['debug'] ) {
            Utils::out("<!-- _SYS: \n", $GLOBALS['_SYS'] , "\n -->\n");
			Utils::out("<!-- _VAR: \n", $GLOBALS['_VAR'] , "\n -->\n");
			Utils::out("<!-- _GET: \n", $_GET , "\n -->\n");
			Utils::out("<!-- _POST: \n", $_POST , "\n -->\n");
			Utils::out("<!-- _DATA:\n", $data , "\n -->\n");
		}

		return $data;
    }
    
    /**
     * 新版API查询方法 (兼容1.0)
     */
    function queryV2( $path, $query=[], $data=[] ) {
        $class = OpenAPI::GetClass($path);
        $instance = new $class["class"];
        if (method_exists($instance, 'run') ) {
            return $instance->run( $class["method"], $query, $data );
        }
        return $this->query( $path, $query, $data );
    }


	function query( $api_slug, $query=[], $data=[], $files=null, $allowForbidden=false ) {

		$arr = explode('/', $api_slug );
		$method = end($arr);
		array_pop($arr);
		$api_name = ucwords(strtolower(end($arr)));
		array_pop( $arr );

		foreach ($arr as $idx => $name ) {
			$arr[$idx] = ucwords(strtolower($name));
		}

		$class_name =  trim(implode("\\", $arr) . "\\Api\\" . $api_name);

		if ( !class_exists($class_name) ) {
            throw new Excp("{$api_slug} 接口不存在", 404, ['api'=>$api_slug, "class"=>$class_name, "method"=>$method]);	
		}
		$option=['query'=>$query,'data'=>$data,'files'=>$files];
		$api = new $class_name($option);

		if ( $allowForbidden === false && $api->isForbidden($method) === true ) {
			throw new Excp("{$api_slug}::{$method} 禁止直接访问", 403, ['api'=>$api_slug, "class"=>$class_name, "method"=>$method]);
		}

		return $api->call($method, $query, $data, $files);
		
	}

	function toPHPVar( $var_name ) {

		$phpvar = '';
		$vars = explode('.', $var_name);


		foreach ($vars as $var ) {
			if ( empty($phpvar) ) {
				$phpvar = '$' . $var;
			} else {
				$phpvar = $phpvar . "['{$var}']";
			}
		}

		return $phpvar;
	}

	function assetValue( & $vars, & $data ) {

					
		foreach ($vars as $name => $value) {

            // var_dump( $name );
            // var_dump( $value );
            // echo "=======\n";


			if ( is_array($value)) {
				$this->assetValue($vars[$name], $data );
			} else {
				$varRE = "/\{\{\s*([a-zA-Z]{1}[0-9a-zA-Z\.\_]+)\s*\}\}/";
				$valRE = "/\{\{\s*([0-9\'\"]{1}[0-9a-zA-Z%\-\.\_\"\']+)\s*\}\}/";
				if ( preg_match_all($varRE, $value, $match ) ) {

					foreach ($match[1] as $idx=>$var ) {
						eval( 'try { $v=' .  $this->toPHPVar("data.". $var) . ';} catch( Exception $e){}' );
						if ( is_array($v) ) {
							$vars[$name] = $v;
						}  else {

                            // var_dump( $v );
                            // var_dump( $vars[$name] );

							$vars[$name] = str_replace("{{".$var."}}", $v, $vars[$name]);
						}
					}

				} else if ( preg_match_all($valRE, $value, $match ) ) {


					foreach ($match[1] as $idx=>$var ) {
                        eval( 'try { $v=' . $var . ';} catch( Exception $e){}' );
                        $v= urldecode($v);
						$vars[$name] = str_replace($match[$idx][0], $v, $vars[$name]);
					}

				} else if ( preg_match_all("/\{\{\s*\}\}/", $value, $match ) ) {
					foreach ($match[0] as $idx=>$var ) {
						// echo "$var \n";
						$vars[$name] = str_replace($var, "", $vars[$name]);
					}
					// $vars[$name] = ""; 
				}

			}
		}
	}


	function replaceVars( & $text ) {


		$vars = [];
		$varRE = "/{{(s*)([0-9a-zA-Z\.\_\[\]]]+)}}/";
		$varRE = "/\{\{\s*([0-9a-zA-Z\_\.\[\]]+)\s*\}\}/";

		$replace = ["__var"=>[], "__get"=>[], "__post"=>[], "__sys"=>[], "__se"=>[] ];

		if ( preg_match_all($varRE, $text, $vars) ) {

			$len = count($vars[0]);

			for( $i=0; $i<$len; $i++ ) {
				$origin = $vars[0][$i];
				if ( preg_match('/(__var|__get|__post|__sys|__se)\.([0-9a-zA-Z\_\.]+)/', $origin, $match) ) {
					$type= $match[1];
					$var = $match[2];

					switch ($type) {
						case '__var':
							$valuestr = $this->toPHPVar("GLOBALS['_VAR'].". $var);
							break;

						case '__se':
							$valuestr = $this->toPHPVar("_SERVER.". $var);
							break;

						case '__get':
							$valuestr = $this->toPHPVar("_GET.". $var);
							break;

						case '__post':
							$valuestr = $this->toPHPVar("_POST.". $var);
							break;

						case '__sys':
							$valuestr = $this->toPHPVar("GLOBALS['_SYS'].". $var);
							break;
						
						default:
							$valuestr = "''";
							break;
					}

					eval( 'try { $v=' .  $valuestr . ';} catch( Exception $e){}' );
					
                    // {{__var.slug}} {{'someting  __var.slug'}}
					$pos = strpos($vars[0][$i], "{{" . $match[0]); 
                    $v  = urlencode( $v );
                    
					if ( $pos === 0 ) {  // {{__var.slug}}
                        // echo "<!-- debug :: {{__var.slug}} = {$match[0]}  pos={$pos} v={$v}  text=$text-->\n";    
                        $text = str_replace($match[0], "'{$v}'" , $text);
                        // echo "<!-- debug :: after text={$text} -->\n";
					} else {  // {{'someting  __var.slug'}}
						$text = str_replace($match[0], $v , $text);
					}
					
					// echo "<!-- {$vars[0][$i]} ... {$match[0]} text = {$text} -->\n";
					// echo "\n=== \n";
				}
			}


		}


		// foreach ($GLOBALS['_VAR'] as $name=>$value ) {
		// 	if ( is_array($value) ) {
		// 		foreach ($value as $k => $v) {
		// 			$text  = str_replace("{{__var.".$name.".".$k."}}", $v, $text );
		// 		}

		// 	} else{
		// 		$text  = str_replace("{{__var.".$name."}}", $value, $text );
		// 	}
		// }

		// foreach ($_GET as $name=>$value ) {
		// 	if ( is_array($value) ) {
		// 		foreach ($value as $k => $v) {
		// 			$text  = str_replace("{{__get.".$name.".".$k."}}", $v, $text );
		// 		}
		// 	} else{
		// 		 $text  = str_replace("{{__get.".$name."}}", $value, $text );
		// 	}
		// }

		// foreach ($_POST as $name=>$value ) {
		// 	if ( is_array($value) ) {
		// 		foreach ($value as $k => $v) {
		// 			$text  = str_replace("{{__post.".$name.".".$k."}}", $v, $text );
		// 		}
		// 	} else{
		// 		 $text  = str_replace("{{__post.".$name."}}", $value, $text );
		// 	}
		// }

		// // PHP_SERVER
		// foreach ($_SERVER as $name=>$value ) {
		// 	if ( is_array($value) ) {
		// 		foreach ($value as $k => $v) {
		// 			$text  = str_replace("{{__se.".$name.".".$k."}}", $v, $text );
		// 		}
		// 	} else{
		// 		$text  = str_replace("{{__se.".$name."}}", $value, $text );
		// 	}
		// }

		// // CONST 
		// foreach ($GLOBALS['_SYS'] as $name=>$value ) {
		// 	if ( is_array($value) ) {
		// 		foreach ($value as $k => $v) {
		// 			$text  = str_replace("{{__sys.".$name.".".$k."}}", $v, $text );
		// 		}
		// 	} else{
		// 		 $text  = str_replace("{{__sys.".$name."}}", $value, $text );
		// 	}
		// }

		
	}

}
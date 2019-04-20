<?php

namespace Xpmse;

require_once( __DIR__ . '/Mem.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/utils-lib/Validatecode.php');

use \Exception as Exception;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Intervention\Image\ImageManagerStatic as Image;
use \Illuminate\Validation\Factory as ValidatorFactory;

use \Twig_Loader_Array;
use \Twig_Environment;
use \Twig_Filter;
use \Twig_Lexer;



/**
 * XpmSE常用工具函数
 */
class Utils {
	

	/**
	 * Request 请求失败，重试次数
	 * @var integer
	 */
	public static $_request_retry = 0;

	public static $twig = null;


	function __construct() {
	}


	public static function __callStatic($method,$arg) {  
		$ut = new Self();
		$ut->$method(...$arg );
    }
    

    /**
     * 处理表单中JSON字段
     */
    public static function JsonFromInput( &$data, $json_field_param="__json_cols" ){

        if ( array_key_exists($json_field_param, $data) ) {
            
            if ( is_string($data["$json_field_param"]) ) {
                $data["$json_field_param"] = [$data["$json_field_param"]];
            }

            foreach( $data["$json_field_param"]  as $field ) {
                if ( is_string($data["$field"]) ) {
                    $data["$field"] = Utils::json_decode($data["$field"]);
                }
            }
        }
    }


    /**
     * 仅保留有 KEEP TAG 标签 DIFF 数据
     * @param string $diff 差异数据字符串  xdiff_string_diff() / xdiff_file_diff() 返回值
     * @param array $tag 保留代码标记标签  EG:
     *         // @KEEP BEGIN 
     *          echo "hello world!"
     *         // @KEEP END
     * 
     * @return string 保留的DIFF数据
     * 
     */
    public static function diffFilter( $diff, $tag=["@KEEP BEGIN","@KEEP END"] ) {

        $newdiff = "";
        $lines = preg_split("/\n/",str_replace("\r\n", "\n", $diff ));
        $cnt = count($lines);
        for ( $i=0; $i<$cnt; $i++) {
            $curr = $lines[$i];

            // @@ -4,7 +4,7 @@
            // @@ -R,r +R,r @@
            // - is the range for the chunk in the original file
            // + the range in the new file
            // The R designates the line number where the diff operation is started.
            // The numbers after the comma are the number of affected lines in each file.
            //      Every time you remove a line, the +r number will be smaller than -r.
            //      Every time you add a line, the +r number will be bigger than -r
            //      Changing a line will add 0 to the +r number. (same scope of lines)
            // ==================  1 ====== 2 ========= 3 ====== 4 ======= 5 =======================
            if ( preg_match("/@@ (-[0-9]+),([0-9]+) \+([0-9]+),([0-9]+) @@/", $curr, $chunk) ){

                $block = [$curr]; $keep=0;
                while ( $i+1 <= $cnt && !preg_match("/@@ (-[0-9]+),([0-9]+) \+([0-9]+),([0-9]+) @@/", $lines[$i+1], $nextChunk) ){
                    if (  strpos($lines[$i+1], $tag[0]) !== false ) {
                        $keep++;
                    } 
                    if (  strpos($lines[$i+1], $tag[1]) !== false ) {
                        $keep++;
                    }
                    $block[] = $lines[$i+1];
                    $i++;
                }

                if ( $keep == 2 ) {
                    $newdiff .= implode("\n", $block) . "\n";
                }
            }
        }
        $newdiff = $newdiff . "\n\ No newline at end of file";
        return $newdiff;
    }


	/**
	 * 读取CNAME信息并，缓存24小时
	 * @param  [type] $domain [description]
	 * @return [type]         [description]
	 */
	public static function getCNAME( $domain, $nocache=false ) {

		$mem = new Mem(false,'CNAME:');
		$cname = $mem->get("$domain");
		if( $nocache == true || $cname === false )  {
			$cname = $domain;
			$records= dns_get_record($domain, DNS_CNAME);		
			if( is_array($records)  && !empty($records) ) {
				$cname = current($records)["target"];
			}
			$expires_at = 86400; // 24小时后过期
			$mem->set("$domain", $cname, $expires_at );
		}

		return $cname;
	}


	/**
	 * 根据名称生成一个用户头像
	 */
	public static function genAvatar( $name, $option=[] ) {
		
		mb_internal_encoding("UTF-8");

		$colors = [
			'dark' => ['#666699', '#0099CC', '#99CC00', '#009933', '#CCCC33','#336699','#003399'],
			'light' => ['#FFFFFF', '#FEFEFE'],
		];

		$dark_idx = rand(0, count($colors['dark']) -1 );
		$light_idx =  rand(0, count($colors['light']) -1 );

		$dark = $colors['dark'][$dark_idx];
		$light = $colors['light'][$light_idx];
		$len = mb_strlen($name);
		$pname = $name;

		if ( $len > 1 ) {
			$pname = mb_substr($name, -1);
		}

		$opt = [
			"text" => $pname,
			"font-style" => "黑体",
			"font-color" => $light,
			"background-color" => $dark,
			"font-size" => 72,
			"width"=>150,
			"height"=>150,
			"text-align" => "center",
			"text-valign" => "middle"
		];

		$image = Utils::ImageText($opt);
		$media = new Media( $option );
		$name = time() . Utils::genStr(6) . ".png";
 		return $media->appendFile( $name, $image, true );
	}


	/**
	 * 判断表是否存在
	 * @param  string $name 表名
	 * @return bool
	 */
	public static function tableExist( $name ) {
		$db=new \Xpmse\Model();
		$resp = $db->runsql("SHOW TABLES LIKE '$name'", true);
		return count($resp);
	}

	/**
	 * 打印好难受 
	 * @param  array $data  [description]
	 * @return [type]	   [description]
	 */
	public static function p( $data=[] ) {

		echo "<pre>";
		print_r($data);
		echo "</pre>";

	}


	/**
	 * 判断数组维度
	 * @param  [type] $array [description]
	 * @return [type]        [description]
	 */
	static function array_depth($array) {
		if(!is_array($array)) return 0;
		$max_depth = 1;
		foreach ($array as $value) {
			if (is_array($value)) {
				$depth = self::array_depth($value) + 1;
				if ($depth > $max_depth) {
					$max_depth = $depth;
				}
			}
		}
		return $max_depth;
	}


	/**
	 * 解析模板 
	 * @param  [type] $tpl  [description]
	 * @param  [type] $data [description]
	 * @param  array  $opts [description]
	 * @return [type]	   [description]
	 */
	public static function toString( $tpl, $data, $opts=[] ) {

		$loader = new Twig_Loader_Array(["code" => $tpl]);
		self::$twig = new Twig_Environment($loader, array_merge([
			'autoescape'=>false,
			"debug" => false  // 是否为 Debug 模式
		], $opts));

		// 加载默认 Filter 
		include(__DIR__ . "/xsfdl/Filter.php");
		$opts['filters'] = !is_array($opts['filters']) ? [] : $opts['filters'];
		$opts['filters'] = array_merge($_Twig_Filters, $opts['filters']);
	
		// 设定 Filter
		if ( !empty($opts['filters']) ) {
			foreach ($opts['filters'] as $name => $filter) {
				self::$twig->addFilter( $filter );
			}
		}

		// 设定标签
		$tag = empty($opts['tag']) ? [] : $opts['tag'];
		if ( !empty($tag) ) {
			$lexer = new Twig_Lexer(self::$twig, $tag);
			self::$twig->setLexer($lexer);
		}

		return self::$twig->render('code', $data );
	}


	public static function  getFontName( $fontfile )  { 
		$font = \FontLib\Font::load( $fontfile );
		$font->parse();
		$resp = [];
		if  (is_a( $font, 'FontLib\\TrueType\\Collection' ) ){
			$cnt = $font->count();
			for( $i=0; $i<$cnt; $i++ ){
				$f = $font->current();
				array_push($resp, trim($f->getFontName()) );
				$font->next();
			}

			return implode(',',  $resp );
		}


		return $font->getFontName(); 
	}

	public static function getFontPath( $fontid, $page = 1, $perpage=20  ) {
		$fonts = self::fonts( $page, $perpage );
		$f = $fonts["ids"][$fontid];
		if (empty($f) ) {
			$f = current($fonts['data'] );
		}

		if ( !file_exists($f['local']) ) {
			return false;
		}
		return $f['local'];

	}


	/**
	 * 读取有效字体列表 ( AROOT 应用调用有问题， 下一版转移到服务目录 )
	 * @return [type] [description]
	 */
	public static function fonts( $page = 1, $perpage=20 ) {

		$defaults = [
			[
				"id" => 1,
				"text" => "黑体",
				"local" => realpath(__DIR__ . '/fonts/hei.ttf') 
			],
			[
				"id" => 2,
				"text" => "宋体",
				"local" => realpath(__DIR__ . '/fonts/simsun.ttf')
			]
		];

		$media = new \Xpmse\media();
		$qb = $media->query()
					  ->whereNull('origin_id')
					  ->where('mimetype' , '=', 'application/x-font-ttf')
					  ->where('storage', '=', 'local');

		$resp = $qb->orderBy('created_at', 'desc')
					->select('media.media_id as id',  'origin_id',  'mimetype', 'path', 'small', 'tiny', 'extra', 'param', 'created_at')
					->pgArray($perpage, ['media.media_id as id'], '__page', $page);

		$items = []; $map = []; 
		foreach ($resp['data'] as $idx => $rs) {
			$media->formatAsFile( $resp['data'][$idx] );
			unset($resp['data'][$idx]['extra']);
			$namer = explode(',', $resp['data'][$idx]['title']);

			foreach ($namer as $fidx=>$name ) {
				$map[$name] = $rs['id'] . ".$fidx";
			}
		}

		foreach ($map as $name => $id) {
			array_push( $items, ["text"=>$name, "id"=>$id, "local"=>$resp['data'][$idx]['local']]);
		}

		$id_map =[]; $name_map = [];
		$items = array_merge($defaults, $items );
		foreach ($items as $it ) {
			$id_map[$it['id']] = $it;
			$name_map[$it['text']] = $it;
		}
		$resp['data'] = $items;
		$resp['ids'] = $id_map;
		$resp['names'] = $name_map;
		return $resp;
	}


	/**
	 * 
	 *  读取CSV 大文件结构 ( 支持分页, 支持文件编码 UTF-8, GBK, GB2312, UTF-16, GB18030 )
	 *  
	 *	$map = [
	 *		"身份证号" => "id",
	 *		"准考证号" => "exam_no",
	 *		"姓名" => "name",
	 *		"考试时间" => "exam_time",
	 *		"证书编号"=> "cert_no",
	 *		"资格" => "cert_name"
	 *	];
	 */
	public static function getCSVStruct( $file, $map, $from = 1 ){
		$cols = []; $cols_map;
		Utils::getCSV($file, function( $row, $line, $total ) use( & $cols ) {
			$cols = $row;
		},[], $from, 1);

		foreach ($cols  as $idx => $name ) {
			$name = trim($name);
			$field = $map[$name];
			if ( !empty($field) ) {
				$cols_map[$field] = $idx;
			}
		}

		return $cols_map;
    }
    

    /**
     * 读取字段清单，生成映射表
     */
    public static function getCSVFields( $file, $from = 1 ){
		$cols = []; $cols_map;
		Utils::getCSV($file, function( $row, $line, $total ) use( & $cols ) {
			$cols = $row;
		},[], $from, 1);

		foreach ($cols  as $idx => $name ) {
			$name = trim($name);
			$field = $name;
			if ( !empty($field) ) {
				$cols_map[$field] = $idx;
			}
        }
        
		return $cols_map;
    }
    


	/**
	 * 读取CSV 大文件 ( 支持分页, 支持文件编码 UTF-8, GBK, GB2312, UTF-16, GB18030 )
	 * @param  string  $file csv文件地址
	 * @param  function( $data, $line, $total)  $cb  回调函数  ($columns_map 为空，返回数组， 否则返回key:value数值)
	 * @param  array   $columns_map 字段映射关系 
	 * @param  integer $from		开始行
	 * @param  integer  $limit	   读取记录数
	 * @return 
	 */
	public static function getCSV( $file, $cb, $columns_map=[], $from=1, $limit=null ) {

		if ( !is_callable($cb) ) {
			return;
		}

		$total = self::fileLines( $file );
		$row = 1;

		if (($handle = fopen($file, "r")) !== FALSE) {

			// SEEK TO FROM 
			$i = 0;$bufcarac = 0; 
			for($i = 1;$i<$from;$i++) {
				$ligne = fgets($handle);
				$bufcarac += strlen($ligne);
			}
			fseek($handle,$bufcarac);

			ini_set('auto_detect_line_endings',TRUE);
			while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
				
				// 字符转码
				$data = array_map( function( $str ){ 
					$encoding = mb_detect_encoding( $str, ["UTF-8", "GBK", "GB2312", "GB18030", "UTF-16"] );
					if ( $encoding != "UTF-8" && $encoding != "ASCII") {

						return iconv( $encoding, "UTF-8", $str ); 
					} else {
						return $str;
					}
				}, $data );


				if ( $limit !=null && $row > $limit ) {
					break;
				}

				if ( !empty($columns_map) ) {
					$resp = [];
					foreach ($columns_map as $name => $idx ) {
						$resp[$name] = $data[$idx];
					}
					$data = $resp;
				}


				$cb( $data,  ($row - 1) + $from,  $total );
				$row++;
			}
			ini_set('auto_detect_line_endings',FALSE);
			fclose($handle);
		}


	}


	/**
	 * 读取文件一共多少行
	 * @param  string  $file 文件地址
	 * @return 返回文件行数
	 */
	public static function fileLines( $file ) {

        if ( !file_exists($file) ) {
            throw new Excp("文件不存在($file)", 404, ["file"=>$file]);
        }

        if ( !is_readable($file) ) {
            throw new Excp("没有文件读取权限($file)", 500, ["file"=>$file]);
        }

		$f = fopen($file, 'rb');
		$lines = 0;

		while (!feof($f)) {
			$lines += substr_count(fread($f, 8192), "\n");
		}

		fclose($f);

		return $lines;
    }
    



	public static function cityLocation(   $city,   $option=[] ) {
		return self::AddressLocation( $city, $city, $option );
	}


	public static function locationAddress( $lng, $lat, $option  ) {
		$ak = $option['ak'] ;
		$sk = $option['sk'] ;

		if ( $ak === null ) {
		return ['status'=>'404', 'message'=>'无百度API配置信息 AK=NULL', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]];
		}

		if ( $sk === null ) {
			return ['status'=>'404', 'message'=>'无百度API配置信息 SK=NULL', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]];
		}

		$api = empty( $option['api']) ? "/geocoder/v2/" :  $option['api'];
		$url = "http://api.map.baidu.com";
		
		$data = [
			'ak' =>$ak,
			'output'=>'json',
			'location' => "{$lat},{$lng}"
		];

		ksort($data);
		$query_string = http_build_query($data);
		$sn = md5(urlencode($api.'?'.$query_string.$sk));
		$data['sn'] = $sn;
		$request_url = $url.$api.'?'. http_build_query($data);
		
		$json_text = file_get_contents($request_url);
		// echo $request_url;

		$resp = json_decode($json_text, true);

		if ( !isset($resp['status']) ) {
			return ['status'=>'500', 'message'=>'返回结果异常', 'extra'=>['address'=>$address, 'city'=>$city, 'ak'=>$ak, 'sk'=>$sk, 'resp'=>$resp]];
		}

		if ( $resp['status'] != 'OK' || !isset($resp['result']) ) {
			return $resp;
		}

		$resp['result']['status'] = 0;
		
		return $resp['result'];

	}

	public static function addressLocation(   $address, $option=[] ) {
		$ak = $option['ak'] ;
		if ( $ak === null ) {
		return ['status'=>'404', 'message'=>'无百度API配置信息 AK=NULL', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]];
		}
		$api = empty( $option['api']) ? "/geocoder/v2/" :  $option['api'];
		$url = "http://api.map.baidu.com";
		$data = [
			'ak' =>$ak,
			'output'=>'json',
			'address' => $address,
		];
		ksort($data);
		$query_string = http_build_query($data);
		$request_url = $url.$api.'?'. http_build_query($data);
		
		$json_text = file_get_contents($request_url);

		// echo $request_url;

		$resp = json_decode($json_text, true);

		if ( !isset($resp['status']) ) {
			return ['status'=>'500', 'message'=>'返回结果异常', 'extra'=>['address'=>$address, 'ak'=>$ak, 'sk'=>$sk, 'resp'=>$resp]];
		}

		if ( $resp['status'] != 'OK' || !isset($resp['result']) ) {
			return $resp;
		}

		$resp['result']['status'] = 0;
		$resp['result']['point'] =[
			"x"=>$resp['result']['location']['lng'],
			"y"=>$resp['result']['location']['lat']
		];
		return $resp['result'];
	}



	/**
	 * 读取当前目录
	 * @param  [type] $location [description]
	 * @return [type]		   [description]
	 */
	public static function getHome( $location = null ) {
		if ( $location  == null ) {
			$location = self::getLocation();
		}

		$uri = parse_url( $location );
		$pos = strpos(   $uri['path'],'/_a' );
		$path = '';
		$port = isset($uri['port']) ? ":{$uri['port']}" : '';
		if ( $pos !== false ) {
			$path = substr( $uri['path'],0, $pos);
		}
		return "{$uri['scheme']}://{$uri['host']}{$port}{$path}";
	}


	/**
	 * 创建模块对象
	 * @param  [type] $name   [description]
	 * @param  [type] $prefix [description]
	 * @param  [type] $driver 数据库驱动 Database (默认) / Common
	 * @return [type]		 [description]
	 */
	public static function getTab( $name, $prefix = "core_", $driver='Database' ) {
		return new \Xpmse\Model( ['table'=>$name, "prefix"=>$prefix], $driver );
	}



	/**
	 * 读取微信配置信息
	 * @param  boolean $nocache [description]
	 * @return [type]		   [description]
	 */
	public static function getConf( $nocache = false ) {

		$mem = new Mem;
		$cmap = $mem->getJSON("BaaS:CONF");

		if ( $cmap == false  || $cmap == null || $nocache === true ) {

			$tab = self::getTab('sys_conf', '_baas_');
			$cmap = []; $groups = []; $map =[]; $tmap =[];
			$config = $tab->select("", ["name","value", "group","key", "gname"] );

			foreach ($config['data'] as $row ) {
				if ( empty($row['gname'])) {
					continue;
				}
				$cmap[$row['name']] = $row['value'];
				if ( !is_array($groups[$row['gname']])) {
					$groups[$row['gname']] = [];
				}
				$groups[$row['gname']][$row['key']] = $row['value'];
				$groups[$row['gname']]['group'] = $row['group'];
				$groups[$row['gname']]['group_name'] = $row['gname'];
			}

			foreach ($groups as $gname => $g) {
				$appid = $g['appid'];
				$type = $g['type'];

				if ( !empty($appid) ){
					$map[$appid] = $g;

					if (!is_array($tmap[$type])) {
						$tmap[$type] = [];
					}
					array_push($tmap[$type] , $g);
				}
			}

			$tab =  self::getTab('sys_cert','_baas_');
			$config = $tab->select("", ["name","path"] );

			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['path'];
			}

			$cmap['_groups'] = $groups;
			$cmap['_map'] = $map;
			$cmap['_type'] = $tmap;
			$mem->setJSON("BaaS:CONF", $cmap );
		}

		return $cmap;
	}


	
	/**
	 * 当前访问路径
	 * @return [type] [description]
	 */
	 public static function getProtocal() {
		
		$proto ='http';
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
			$proto = 'https';
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
			$proto = 'https';
		}
		elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
			$proto = 'https';
		}

		return $proto;
	}



	/**
	 * 对数组对象降维
	 * @param  [type] $data  [description]
	 * @param  [type] $field [description]
	 * @return [type]		[description]
	 */
	public static function pad( $data, $field ){
		
		if (!is_array(current($data))) {
			throw new Excp("输入参数错误 (data不是二维数组)", 400, ['data'=>$data, 'field'=>$field]);
		}

		if (!array_key_exists($field, current($data))) {
			throw new Excp("输入参数错误 ($field 不存在)", 400, ['data'=>$data, 'field'=>$field]);
		}


		$resp = []; $map = [];
		foreach ($data as $idx => $rs ) {
			$map[$rs[$field]] = $idx;
			array_push($resp, $rs[$field]);
		}

		return ["data"=>$resp, 'map'=>$map];
	}


	/**
	 * 检查是否是命令行运行
	 * @return [type] [description]
	 */
	public static function iscli() {
		return  PHP_SAPI == "cli" || FORWARDED_PHP_SAPI  == 'cli';
	}


	/**
	 * 仅限命令行运行
	 * @return [type] [description]
	 */
	public static function cliOnly(){
		if ( !self::iscli() ) {
			throw new Excp('请在命令行下运行', 403 );
		}
	}



	/**
	 * 生成 Session id 
	 * @return [type] [description]
	 */
	public static function sid() {
		return md5(uniqid(mt_rand(),1));
	}


	/**
	 * 读取证书路径
	 * @return [type] [description]
	 */
	public static function certpath() {

		$crtpath = Conf::G('general/cert');

		if ( $crtpath != null ) return $crtpath;

		if ( is_writable('/config/crt') ) {
			return '/config/crt';
		}

		$private  = Conf::G('storage/local/bucket/private/root');
		if ( is_writable($private) ) {

			mkdir( $private . "/crt" );
			return  $private . "/crt";
		}

		return "/tmp";

	}

	/**
	 * 返回 APP 名称
	 * @return [type] [description]
	 */
	static public function app() {
		$name = "core";

		if ( defined('APP_ROOT') ) { 
			$path_info = APP_ROOT;
		} else if ( isset( $_SERVER['APP_ROOT']) ) { 
			$path_info = $_SERVER['APP_ROOT'];

		} else if ( is_callable('getcwd') ) {
			$path_info = getcwd();
		} else {
			$path_info = dirname($_SERVER['SCRIPT_FILENAME']);
		}
 
		if ( strpos($path_info, _XPMAPP_ROOT) !== false ) {
			$path =  str_replace(_XPMAPP_ROOT . '/', '',  $path_info);  
			$info = explode('/', $path);
			$name = trim($info[0]);  // APP NAME

			if ( !empty($info[1]) ) {
				$name = $name . '_' . $info[1];
			}

		}

		if ( $name == '') {
			$name = 'core';
		}

		return $name;
	}


	/**
	 * 创建 Faker 实例
	 *
	 * @see https://github.com/fzaninotto/Faker
	 * @return [type] [description]
	 */
	static public function faker( $locale='zh_CN' ) {
		return  \Faker\Factory::create( $locale );
	}


	/**
	 * 创建 Qrcode 实例
	 * 
	 * @see https://github.com/endroid/QrCode
	 * @return [type] [description]
	 */
	static public function qrcode() {
		return new \Endroid\QrCode\QrCode();
	}


	/**
	 * 邮件消息对象
	 * 
	 * @param [type] $title [description]
	 * @param [type] $body  [description]
	 */
	static public function MailMessage( $title = null, $body=null ) {
		return \Swift_Message::newInstance( $title, $body);
	}



	static public function JsonToHtml( $json, $value, $template ) {

		$h5 =  new \Masterminds\HTML5([
			"preserveWhiteSpace" => false,
			"formatOutput" => true
		]);

		$dom = $h5->loadHTML(  $template );
		$templates = $dom->getElementsByTagName('template');
	 
		// 生成模板
		$tpls = [];
		foreach ($templates as $tpl ) {
			$type = $tpl->getAttribute("type");
			$children = $tpl->childNodes; 
			$innerHTML = '';
			foreach ($children as $child) { 
				$innerHTML .= $child->ownerDocument->saveHTML( $child ); 
			} 
			$tpls[$type] = $innerHTML;
		}

		$names = []; $fields =[];  $data = []; 

		// 处理数据
		foreach ($json as $name => $opt ) {
			
			$html = '';
			$type = $opt['type'];
			$tpl = $tpls[$type];

			$html = str_replace("{{name}}", $name, $tpl);
			
			$opt['attrs'] = empty($opt['attrs']) ?  [] : $opt['attrs'];
			$field = empty($opt['attrs']['name']) ? $name : $opt['attrs']['name'];

			foreach ($opt['attrs'] as $key=>$val ) {
			   $html = str_replace("{{attr.$key}}", $val, $html);
			}

			$v = $value[$field];
			if ( is_string($v) && $v != null ) {
				$html = str_replace("{{value}}", $v, $html);
			}

			if ( is_array($v) ) {
				foreach ($v as $idx => $vv ) {
					$html = str_replace("{{value.$idx}}", $vv, $html);
				}
			}

			if ( is_null($v) ) {
				$html = str_replace("{{value}}", '', $html);
			}

			// 处理 Scope 
			$opt['scope'] = empty($opt['scope']) ?  [] : $opt['scope'];

			if ( preg_match("/\{\{scope\}\}([\s\S]*?)\{\{\/scope\}\}/", $html, $match) ) {


				$scope_html_total = '';
				$scope_html = '';
				$scope_tpl = $match[1];
				$scopestr = $match[0];
				$iftrue_tpl = null;
				$ifstr = "";

				// if value 
				if ( preg_match("/ifvalue=[\'\"]{1}([0-9a-zA-Z ]+)[\'\"]{1}/", $scope_tpl, $ifmatch) ){
					$iftrue_tpl = $ifmatch[1];
					$ifstr = $ifmatch[0];
				}

				foreach ($opt['scope'] as $idx => $name ) {

					if (is_int($idx) ) {
						$scope_value = $idx;
						$scope_name = $name;
					} else { // K V 结构
						$scope_name = $idx; 
						$scope_value = $name;
					}

					$scope_html = str_replace("{{scope.name}}", $scope_name, $scope_tpl);
					$scope_html = str_replace("{{scope.value}}", $scope_value, $scope_html);

					if ( $iftrue_tpl !== null ) {
						if ( is_string($v) && $scope_value == $v ) {
							$scope_html = str_replace($ifstr, $iftrue_tpl, $scope_html);
						} else if ( is_array($v) && in_array($scope_value, $v) ) {
							$scope_html = str_replace($ifstr, $iftrue_tpl, $scope_html);
						} else {
							$scope_html = str_replace($ifstr, '', $scope_html);
						}
					}


					$scope_html_total = $scope_html_total. $scope_html ;
					
				}

				$html = str_replace($scopestr, $scope_html_total, $html);


			}

			$html = str_replace('&lt;?', "<?", $html);
			$html = str_replace('?&gt;', "?>", $html);
			$html = str_replace('=&gt;', "=>", $html);

			// 空值替换
			$html = preg_replace("/\{\{([a-zA-Z0-9\.-\_]+)\}\}/", "", $html);

			// if (preg_match("/([a-zA-Z0-9\.]+)/", $html, $match)) {
			//	 echo "<!-- DEBUG ";
			//	 echo $html;
			//	 echo "-->";
			// }


			$names[$name] = $html;
			$fields[$field] = $html;
			array_push($data, $html);
		}


		return ["html"=>implode("\n",$data), "data"=>$data, "fields"=>$fields, "names"=>$names];

	}


	/**
	 * 邮件发送对象
	 * @param string $option [description]
	 */
	static public function Mailer( $option="SMTP" ) {

		if ( is_string($option) ) {
			$option = strtolower( $option );
			$option = Conf::G("mailer/{$option}");
		} else if ( !is_array( $option) ) {
			throw new Excp( "缺少邮件配置信息", 404, ["option"=> $option] );
		}  

		$option['transport'] = empty( $option['transport'] ) ?  'smtp':  $option['transport'] ;


		$transport = null;

		if ( $option['transport'] == "smtp" ) {

			if ( empty($option['host']) || empty($option['pass']) || empty($option['user']) ) {
				throw new Excp( "缺少邮件配置信息", 404, ["option"=> $option] );
			}

			$transport = \Swift_SmtpTransport::newInstance($option['host'])
							->setUsername( $option['user'] )
							->setPassword( $option['pass'] )
						;

			if ( $option["ssl"] === true ) {
				$option['port'] = empty( $option['port'] ) ? "465" : $option['port'];
				$transport->setEncryption('ssl'); 
				$transport->setStreamOptions([
					"ssl"=> [
					'verify_peer' => false,
					'verify_peer_name'=>false,//Require verification of peer name.
					'allow_self_signed' => true//是否允许自签名证书
					]
				]);
			}

			$option['port'] = empty( $option['port'] ) ? "25" : $option['port'];
			$transport->setPort( $option['port']  );

		}

		if ( $transport != null  ) {
			return  \Swift_Mailer::newInstance($transport);
		}

		throw new Excp( "无效 transport ", 404, ["option"=> $option] );

	}


	/**
	 * 数据验证
	 * 
	 * @see respect/validation
	 * @see http://respect.github.io/Validation/docs/validators.html
	 *  
	 * @param [type] $data [description]
	 * @param [type] $rule [description]
	 */
	static public function v( $type ) {
		
		if ( is_callable("\Respect\Validation\Validator::$type") ) {
			return \Respect\Validation\Validator::$type();
		} 

		throw new Excp("$type not found", 404,  ['type'=>$type] );
	}





	/**
	 * 图片验证码类
	 * @return [type] [description]
	 */
	static public function vcode() {
		return new \Xpmse\Utils\ValidateCode();
	}


	/**
	 * 调试输出函数
	 * @param Utils::out( ["a"=>"a1"], "\n =>\n", [1,2,3,4]...);
	 */
	static public function out() {
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


	static public function get() {
		$args = func_get_args();
		$return = "\n";
		foreach ($args as $arg ) {

			if ( is_string($arg) ) {
				$return .= $arg;
			} else {
				$return .=  json_encode($arg,
				JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			}
		}

		return $return . "\n";
	}


	static function array_sortby($rating_key, & $data, $order=SORT_ASC, $type=SORT_NUMERIC ) {

		$rating = [];
		foreach ($data as $key => $value) {
			$rating[$key] = $value[$rating_key];
		}

		array_multisort($rating, $order, $type, $data);
		return $data;
	}


	/**
	 * 对地址排序
	 * @param  [type] $url		[description]
	 * @param  [type] $sortMethod [description]
	 * @return [type]			 [description]
	 */
	function urlSort( $url, $sortMethod=null ) { 
		$scheme = parse_url($url, PHP_URL_SCHEME);
		$host =parse_url($url, PHP_URL_HOST);  
		$path =parse_url($url, PHP_URL_PATH);  
		$query_string = parse_url($url, PHP_URL_QUERY);
		

		$query_sort =[]; $query = [];
		parse_str($query_string, $query );
		if ( is_array($query) ) {
			$keys = array_keys($query);
			sort($keys, SORT_STRING);
			foreach ($keys as $k) {
				$query_sort[$k] = $query[$k];
			}
		}
		$query_string_sort =  trim(urldecode(http_build_query($query_sort)));

		$url_sort = "{$scheme}://{$host}{$path}";
		if ( strlen($query_string_sort) > 0 ) {
			$url_sort = $url_sort . "?{$query_string_sort}";
		}
		return str_replace("\n","", $url_sort);
	}


	/**
	 * 解析链接样式  
	 * @param  string $link_string  eg: {default,news,[categoryid:$1]}
	 * @return 成功返回['c'=>$controller, 'a'=>$action, 'q'=>$querys] ，失败返回 link_string
	 */
	function parseLink( $link_string ) {

		$link_string = trim($link_string);
		if ( preg_match('/^\{([\_0-9a-zA-Z]+),[ ]*([\_0-9a-zA-Z]+)[,]*[ ]*[\[]*([0-9a-zA-Z\_\:\$,\.]+)*[\]]*\}$/', trim($link_string), $match) ){
			$controller = $match[1];
			$action = $match[2];
			$querys = [];
			$querystring = explode(',',$match[3]);
			foreach ($querystring as $query ) {
				$keyval = explode(':', $query);
				if ( count($keyval) == 2 ) {
					$key = $keyval[0];
					$val = $keyval[1];
					$querys[$key] = $val;
				}
			}
			return ['c'=>$controller, 'a'=>$action, 'q'=>$querys];
		}

		return $link_string;
	}

	 /**
	 * 解析链接样式  
	 * @param  string $link_string  eg: {default,news,[categoryid:$1]}
	 * @return 成功返回['c'=>$controller, 'a'=>$action, 'q'=>$querys] ，失败返回 link_string
	 */
	function parseNSLink( $link_string ) {

		$link_string = trim($link_string);
		if ( preg_match('/^\{([\_\-0-9a-zA-Z]+),[ ]*([\_0-9a-zA-Z]+),[ ]*([\_0-9a-zA-Z]+)[,]*[ ]*[\[]*([0-9a-zA-Z\_\:\$,\.]+)*[\]]*\}$/', trim($link_string), $match) ){
			$namespace = $match[1];
			$controller = $match[2];
			$action = $match[3];
			$querys = [];
			$querystring = explode(',',$match[4]);
			foreach ($querystring as $query ) {
				$keyval = explode(':', $query);
				if ( count($keyval) == 2 ) {
					$key = $keyval[0];
					$val = $keyval[1];
					$querys[$key] = $val;
				}
			}
			return ['n'=>$namespace,'c'=>$controller, 'a'=>$action, 'q'=>$querys];
		}

		return $link_string;
	}
   


	/**
	 * 读取 Service Root
	 */
	function getServiceRoot() {
		return dirname(dirname(__FILE__));
		//  return str_replace('/lib', '',$path);
	}

	static public function seroot() {
		return dirname( __DIR__ );
	}



	/**
	 * JavaScript escape 转义
	 * @param  [type] $str [description]
	 * @return [type]	  [description]
	 */
	function unescape($str)  { 
		$ret = ''; 
		$len = strlen($str); 
		for ($i = 0; $i < $len; $i ++) 
		{ 
			if ($str[$i] == '%' && $str[$i + 1] == 'u') 
			{ 
				$val = hexdec(substr($str, $i + 2, 4)); 
				if ($val < 0x7f) 
					$ret .= chr($val); 
				else  
					if ($val < 0x800) 
						$ret .= chr(0xc0 | ($val >> 6)) . 
						 chr(0x80 | ($val & 0x3f)); 
					else 
						$ret .= chr(0xe0 | ($val >> 12)) . 
						 chr(0x80 | (($val >> 6) & 0x3f)) . 
						 chr(0x80 | ($val & 0x3f)); 
				$i += 5; 
			} else  
				if ($str[$i] == '%') 
				{ 
					$ret .= urldecode(substr($str, $i, 3)); 
					$i += 2; 
				} else 
					$ret .= $str[$i]; 
		} 
		return $ret; 
	}



	/**
	 * 
	 * 发送邮件 ( SMTP )
	 * 
	 * @param array $option 邮件发送选项
	 *			  $option['from']  发件人 （ 支持 李四<lisi@xxx.com> 格式 ) [必填] 
	 *			  $option['to']  收件人 （ 支持 张三<zhangsan@xxx.com> 格式 ) [必填] 
	 *			  $option['title'] 邮件标题 [必填] 
	 *			  $option['body']   邮件正文(支持HTML) [body或templete/templete_path至少填写一个] 
	 *			  $option['data']   渲染模板的数据 ($option['body'] 为空时生效)
	 *			  $option['templete']  邮件模板(支持HTML) ($option['body'] 为空时生效)
	 *			  $option['templete_path']  邮件模板（绝对）路径 ($option['body']和$option['templete']为空时生效)
	 *			  $option['attchement']  邮件附件 ( Stor  warpper )
	 *
	 *			  $option['queque']  是否通过后台队列发送（ 默认为 true )
	 * 
	 * @param array $conf  邮件配置 默认读取配置文件中邮件的配置项目
	 * @return 成功返回 true, 失败返回false
	 * 
	 */
	public function SendEmail( $option, $conf=null ) {


		return true;
	}



	public static function toPHPCode( $data, $key=null, $step = 0 ) {


		$code = "";
		if ( is_array($data) ) {
			$code .=  "[";
			foreach ($data as $k => $val ) {
				$code .=  self::toPHPCode($val, $k ) ;
			}

			if ( substr($code, -1) == ',') {
				$len =strlen($code) -1 ;
				$code = substr($code, 0, $len);
			}

			$code .= "]";

		} else {


			if ( is_null($key) ) {
				 if( is_numeric($data) ) {
					return $data;
				} else if ( is_bool($data) ) {
					$print_bool = ( $data === true ) ? 'true' : 'false';
					return $print_bool;
				} else if ( is_string($data) ) {
					return "\"{$data}\"";
				} else if ( is_null($data) ) {
					return "null";
				}
			}

			$print_key = is_numeric($key) ? $key : "\"{$key}\"";


			if( is_numeric($data) ) {
				$code  .= str_repeat(" ", $step ) . "{$print_key}=>{$data},";
			} else if ( is_bool($data) ) {
				$print_bool = ( $data === true ) ? 'true' : 'false';
				$code  .=  str_repeat("\t", $step ) . "{$print_key}=>{$print_bool},";
			} else if ( is_string($data) ) {
				$code  .=  str_repeat("\t", $step ) . "{$print_key}=>\"{$data}\",";
			} else if ( is_null($data) ) {
				$code .= str_repeat("\t", $step ) . "$print_key => null,";
			}
		}

		return $code;
	}



	/**
	 * 发送短信 （ 阿里大鱼接口 ）
	 * @param [type] $content [description]
	 */
	public function SendSMS( $options, $conf=null ) {

		// 腾讯云短信接口
		if ( isset($options['type']) && $options['type'] == 'qcloud' ) {
			return self::SendSMSQcloud( $options['option'], $conf );
		} else if ( $options["type"] == "aliyun" ) {
			return self::SendSMSAliyun( $options['option'], $conf );
		}

		return  ['code'=>$resp['errorCode'], 'message'=>$resp['errorMessage'], 'extra'=>['resp'=>$resp, 'query'=>$query] ];
	}



	public static function SendSMSQcloud( $option, $data=[] ) {

		if ( empty($option['appid']) || empty($option['appkey']) ) {
			throw new Excp("无效参数 appid 或 appkey 不存在", 402, ['option'=>$option, 'data'=>$data]);
		}

		if ( empty($option['mobile']) ) {
			throw new Excp("未知手机号码", 402, ['option'=>$option, 'data'=>$data]);
		}


		$curTime = time();
		$random  = rand(100000, 999999);
		$appid = $option['appid'];
		$appkey = $option['appkey'];
		$api = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms";

		$url = $api . "?sdkappid=" . $appid . "&random=" . $random;
		$mobile = $option['mobile'];
		$message = $option['message'];
		$nationcode = !empty($option['nationcode']) ? "{$option['nationcode']}" : "86";

		if ( !empty($option['sign']) ) {
			$message= $message . "【{$option['sign']}】";
		}

		$sig = hash("sha256","appkey=".$appkey."&random=".$random."&time=".$curTime."&mobile=".$mobile, FALSE);

		if ( is_array($data) ) {
			foreach ($data as $idx => $val ) {
				$message = str_replace('{'.($idx+1).'}', $val, $message);
			}
		}


		$request_data = [
			"tel" => [
				"nationcode" => $nationcode,
				"mobile" => $mobile
			],
			"type" => 0,
			"msg" => $message,
			"sig" => $sig,
			"time" => $curTime,
			"extend" => "",
			"ext" => ""
		];


            $resp = self::Request("POST", $url, ["data"=>$request_data, "type"=>"json"]);
      
		if ( !isset($resp['result']) || $resp['result']!= '0' ) {
                  $error = $resp["ErrorInfo"];
			throw new Excp( $error, 500, ["resp"=>$resp, 'errorlist'=>[["mobile"=>$error]]]);
            }
            
		return true;
	}



	/**
	 * 阿里云短信发送
	 * @param  [type] $data   [description]
	 * @param  array  $option [description]
	 * @return [type]         [description]
	 */
	public static function SendSMSAliyun($option=[], $data) {

		if(empty($data['mobile'])){
			throw new Excp("手机号不能为空",404,$data);
		}

		if(empty($data['templateCode'])){
			throw new Excp("短信模板代码不能为空",404,$data);
		}

		if ( empty($option["accessKeyId"]) || empty($option["accessKeySecret"])) {
			throw new Excp("无效配置信息", 404,$data);
		}

		// 注意使用GMT时间
		date_default_timezone_set("GMT");
		$dateTimeFormat = 'Y-m-d\TH:i:s\Z'; // ISO8601规范
		$accessKeyId = $option["accessKeyId"]; // 'LTAIyIOPNGoWaGUu';      // 这里填写您的Access Key ID
		$accessKeySecret = $option["accessKeySecret"]; // 'E4jqn2EjZpNU3UY6pLn419W9gu2m1w';  // 这里填写您的Access Key Secret

		$host = "https://dysmsapi.aliyuncs.com/?";
		$params = array(    // 公共参数	
			'Format' => 'XML',    
			'Version' => '2017-05-25',    
			'AccessKeyId' => $accessKeyId,    
			'SignatureVersion' => '1.0',    
			'SignatureMethod' => 'HMAC-SHA1',    
			'SignatureNonce'=> uniqid(),    
			'Timestamp' => date($dateTimeFormat),   
			'Action' => 'SendSms', 
			'SignName'=>$option["signName"], //'基因故事OS',    
			'TemplateCode' => $data["templateCode"],  // 'SMS_144854545',    
			'PhoneNumbers' => $data['mobile'],	
			'TemplateParam' => json_encode($data["templateParam"]), //$mobile_code = self::random(6,1); $ParamString="{\"code\":\"".strval($mobile_code)."\"}";
		);
		// 计算签名并把签名结果加入请求参数
		//echo $data['Version']."<br>";
		//echo $data['Timestamp']."<br>";
		$params['Signature'] = self::computeSignature($params, $accessKeySecret);

		// 发送请求
		$resp = self::xml_to_array(self::https_request($host.http_build_query($params)));
		if ( !isset($resp['SendSmsResponse']) || $resp['SendSmsResponse']["Code"] != 'OK' ) {
			$error = isset($resp["Error"]) ? $resp["Error"] : [];
			$message = isset($error["Message"]) ?$error["Message"]: "未知错误";
			throw new Excp( $message, 500, ["resp"=>$resp, 'errorlist'=>[["mobile"=>$message]]]);
		}
		return true;
	}

	
	/**
	 * IP经纬度查询函数
	 * @param String $ip IP地址
	 */
	public function IPLocation( $ip, $ak=null, $sk=null ) {

		$ak = ( $ak === null ) ? Conf::G('baidu/api/ak') : $ak;
		$sk = ( $sk === null ) ? Conf::G('baidu/api/sk') : $sk;

		if ( $ak === null ) {
			throw new Excp("Utils::IPLocation无法运行, 无百度API配置信息", '404', ['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]);
		}

		if ( $sk === null ) {
			throw new Excp("Utils::IPLocation无法运行, 无百度API配置信息", '404', ['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]);
		}

		$api = "/location/ip";
		$url = "http://api.map.baidu.com";
		
		$data = [
			'ak' =>$ak,
			'ip' => $ip,
			'coor'=> 'bd09ll'
		];
		ksort($data);
		$query_string = http_build_query($data);
		$sn = md5(urlencode($api.'?'.$query_string.$sk));
		$data['sn'] = $sn;
		$resp = $this->Request('GET', $url.$api, ['query'=>$data]);
		if ( !isset($resp['status']) ) {
			throw new Excp("Utils::IPLocation错误, 返回结果异常", '500', ['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk, 'resp'=>$resp]);
		}

		if ( $resp['status'] != 0 || !isset($resp['content']) ) {
			$err = new Err($resp['status'],$resp['message'],['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk, 'resp'=>$resp]);
		}

		$resp['content']['status'] = 0;
		return $resp['content'];
	}



	/**
	 * XHprof 调试工具
	 * @param string $cmd 有效值  'start' 'stop'
	 * @param string $source XHProf Source 默认值 'xhprof_foo'
	 */
	public function Xhprof( $cmd = 'start', $source='xhprof_foo' ) {

		if ( !function_exists('xhprof_enable') ) {
			throw new Excp("Utils::Xhprof无法运行, xhprof_enable 方法不存在", '404', ['cmd'=>$cmd]);
		}

		if ( !function_exists('xhprof_disable') ) {
			throw new Excp("Utils::Xhprof无法运行, xhprof_disable 方法不存在", '404', ['cmd'=>$cmd]);
		}

		try {
			include_once "xhprof_lib/utils/xhprof_lib.php";  
		}  catch( Exception $e ) {
			throw new Excp("Utils::Xhprof无法运行, xhprof_lib.php 文件不存在", '404', ['cmd'=>$cmd]);
		}

		try {
			include_once "xhprof_lib/utils/xhprof_runs.php";  
		}  catch( Exception $e ) {
			throw new Excp("Utils::Xhprof无法运行, xhprof_runs.php 文件不存在", '404', ['cmd'=>$cmd]);
		}

		if ( !class_exists('\XHProfRuns_Default') ) {
			throw new Excp("Utils::Xhprof无法运行, XHProfRuns_Default 类不存在", '404', ['cmd'=>$cmd]);
		}

		$home = Conf::G('debug/xhprof');
		if ( $home == null ) {
			throw new Excp("Utils::Xhprof无法运行, debug/xhprof 未配置", '404', ['cmd'=>$cmd]);
		}

		if( $cmd == 'start' ) {
			xhprof_enable();
			return true;

		} else if ( $cmd == 'stop') {

			$xhprof_data = xhprof_disable();
			$xhprof_runs = new \XHProfRuns_Default();
			$run_id = $xhprof_runs->save_run($xhprof_data, "{$source}");
			$link = "{$home}?run={$run_id}&source={$source}";
			return $link;

		} else {
			 throw new Excp("Utils::Xhprof无法运行, 无效命令", '404', ['cmd'=>$cmd]);
		}
	}


	/**
	 * 将文字转换成图片
	 * @param [type] $options [description]
	 */
	public function ImageText( $options ) {

		$fonts = self::fonts();
		$fontsMap = $fonts['names'];

		$options['text'] = ( isset($options['text']) ) ? $options['text'] : '文';
		$options['width'] = ( isset($options['width']) ) ? intval($options['width']) : 50;
		$options['height'] = ( isset($options['height']) ) ? intval($options['height']) : 50;
		$options['font-style'] = ( isset($options['font-style']) ) ? $options['font-style'] : '黑体';
		$options['font-color'] = ( isset($options['font-color']) ) ? $options['font-color'] : '#ffffff';
		$options['font-size'] = ( isset($options['font-size']) ) ? intval($options['font-size']) : 18;
		$options['background-color'] = ( isset($options['background-color']) ) ? $options['background-color'] : '#000000';
		$options['text-align'] = ( isset($options['text-align']) ) ? $options['text-align'] : 'center';
		$options['text-valign'] = ( isset($options['text-valign']) ) ? $options['text-valign'] : 'top';
		$options['text-angle'] = ( isset($options['text-angle']) ) ? intval($options['text-angle']) : 0;

		$img = Image::canvas($options['width'], $options['height'], $options['background-color']);


		$x = intval($options['width']/2);
		$y = intval($options['height']/2);
		$img->text($options['text'], $x, $y, function($font) use($options, $fontsMap, $fonts) {
			
			
			$font_style = $options['font-style'];

			if ( !empty($font_style) ) {
				$f = $fontsMap[$font_style];
				if ( empty($f) ) {
					$f = current( $fonts['data']);
				}

				if ( file_exists($f['local']) ) {
					 $font->file($f['local']);
				}

			}

			
			$font->size($options['font-size']);
			$font->color($options['font-color']);
			$font->align( $options['text-align'] );
			$font->valign($options['text-valign']);
			$font->angle($options['text-angle']);
		});

		$dst_data = (string) $img->encode();
		return $dst_data;
	}



	/**
	 * 创建 Zip 压缩包实例
	 * @return \PhpZip\ZipFile 实例
	 */
	static public function zip() {
		return new \PhpZip\ZipFile();
	}


	/**
	 * 遍历文件夹下所有文件
	 * @param  string  $dir	   文件夹
	 * @param  function | null  $fn 回调
	 * @param  boolean $recursive  是否递归调用
	 * @return null
	 */
	static public function find( $dir, $fn=null,  $recursive=true ) {

		if (!is_dir( $dir ) ) {
			throw new Excp('参数错误($dir)不是文件夹', 400, ['dir'=>$dir, 'recursive'=> $recursive]);
		}

		$hd = dir($dir);
		while( $file = $hd->read() ) {
			if((is_dir("$dir/$file")) AND ($file!=".") AND ($file!="..") AND $recursive === true AND is_callable($fn) ) {
				static::find( "$dir/$file", $fn, $recursive );
				$fn( "$dir/$file", true );
			} else if ( ($file!=".") AND ($file!="..")  AND is_callable($fn) ) {
				$fn( "$dir/$file", false );
			}
		}
	}
	/**
	 * 读取文件夹
	 */
	static public function read_all ($dir){
	 	if(!is_dir($dir)) return false;
	 		static $arr = [];
		    foreach (scandir($dir) as $key => $value) {
		    	if($value != '.' && $value != '..' && $value != '__MACOSX' && $value != '.DS_Store'){
		    		if(is_dir($dir.'/'.$value)){
		    			self::read_all($dir.'/'.$value);
		    		}else{
		    			$arr[]=$dir.'/'.$value;
		    		}
		    	}
		    }
		    return $arr;
	 }
	/**
	 * 删除文件夹，并且删除目录下所有文件
	 * @param  string $dir 目录名称
	 * @return boolen 成功返回 true 失败返回 false
	 */
	static public function rmdir( $dir ) {
		static::find( $dir, function($file, $isdir ){
			if ( $isdir === true ) {
				rmdir( $file );
				return ;
			} else {
				unlink( $file );
			}
		});
		
		return rmdir($dir);
	}


	/**
	 * 解析JSON字符串，可以准确通报错误 （ 但效率较低 )
	 * 
	 * @param  string  $json JSON字符串
	 * @param  integer $flag 默认为 0  
	 *					   DETECT_KEY_CONFLICTS 删除重复键
	 *					   ALLOW_DUPLICATE_KEYS 允许重复键
	 *					   PARSE_TO_ASSOC 解析为 OBJECT   
	 *					   EG:  PARSE_TO_ASSOC & DETECT_KEY_CONFLICTS
	 * 
	 * @return mix 解析后的变量
	 * @see https://github.com/Seldaek/jsonlint
	 * 
	 */
	static public function json_decode( $json,  $flag = \Seld\JsonLint\JsonParser::PARSE_TO_ASSOC ) {
		
		if ( file_exists($json) ) {
			$json = file_get_contents( $json );
		}

		$parser = new \Seld\JsonLint\JsonParser();
		
		$e = $parser->lint($json, $flag );

		if ( $e != null ) {
			throw new Excp("JSON解析错误", 400, ['details'=>$e->getDetails(), 'error'=>$e->getMessage()]);
		}

		return $parser->parse($json, $flag);

	}



	/**
	 * 创建 Mimes 对象
	 * $mimes->getAllMimeTypes('wmz'); // array('application/x-ms-wmz', 'application/x-msmetafile')
	 * $mimes->getMimeType('json'); // application/json
	 * $mimes->getExtension('application/json'); // json
	 * 
	 * @return [type] [description]
	 */
	static public function mimes() {
		return new \Mimey\MimeTypes;
    }
    
    /**
     * 扩展 Mime Type 类型
     */
    static public function mimetypeExt( $mimetype ) {
        
        $map =  [
            "application/x-zip-compressed" => "application/zip",
            "application/vnd.ms-excel" => "text/csv",
            "audio/mp3" => "audio/mpeg"
        ];

        if ( !empty($map[$mimetype]) ) {
            return $map[$mimetype];
        }
        return $mimetype;
    }

	/**
	 * 读取文件 mimetype 
	 * @param  string $filename 文件名称
	 * @return string mimetype
	 */
	static public function mimetype( $filename ) {
		if(!function_exists('mime_content_type')) {

			function mime_content_type($filename) {

				$mime_types = array(

					'txt' => 'text/plain',
					'htm' => 'text/html',
					'html' => 'text/html',
					'php' => 'text/html',
					'css' => 'text/css',
					'js' => 'application/javascript',
					'json' => 'application/json',
					'xml' => 'application/xml',
					'swf' => 'application/x-shockwave-flash',
					'flv' => 'video/x-flv',

					// images
					'png' => 'image/png',
					'jpe' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'gif' => 'image/gif',
					'bmp' => 'image/bmp',
					'ico' => 'image/vnd.microsoft.icon',
					'tiff' => 'image/tiff',
					'tif' => 'image/tiff',
					'svg' => 'image/svg+xml',
					'svgz' => 'image/svg+xml',

					// archives
					'zip' => 'application/zip',
					'rar' => 'application/x-rar-compressed',
					'exe' => 'application/x-msdownload',
					'msi' => 'application/x-msdownload',
					'cab' => 'application/vnd.ms-cab-compressed',

					// audio/video
					'mp3' => 'audio/mpeg',
					'qt' => 'video/quicktime',
					'mov' => 'video/quicktime',

					// adobe
					'pdf' => 'application/pdf',
					'psd' => 'image/vnd.adobe.photoshop',
					'ai' => 'application/postscript',
					'eps' => 'application/postscript',
					'ps' => 'application/postscript',

					// ms office
					'doc' => 'application/msword',
					'rtf' => 'application/rtf',
					'xls' => 'application/vnd.ms-excel',
					'ppt' => 'application/vnd.ms-powerpoint',

					// open office
					'odt' => 'application/vnd.oasis.opendocument.text',
					'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
				);

				$ext = strtolower(array_pop(explode('.',$filename)));
				if (array_key_exists($ext, $mime_types)) {
					return $mime_types[$ext];
				}
				elseif (function_exists('finfo_open')) {
					$finfo = finfo_open(FILEINFO_MIME);
					$mimetype = finfo_file($finfo, $filename);
					finfo_close($finfo);
					return $mimetype;
				}
				else {
					return 'application/octet-stream';
				}
			}
		}

		return mime_content_type( $filename );
	}



	/**
	 * 读取用户IP 地址
	 * @return [type] [description]
	 */
	public function getClientIP() {
		$client_ip = null;
		if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$client_ip = $_SERVER["HTTP_CLIENT_IP"];
		}  else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) { 
			$client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else if(!empty($_SERVER["REMOTE_ADDR"])) {
			$client_ip = $_SERVER["REMOTE_ADDR"];
		}
		return $client_ip;
    }

    /**
	 * 读取用户IP 地址
	 * @return [type] [description]
	 */
    static public function clientIP(){
        $client_ip = null;
		if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$client_ip = $_SERVER["HTTP_CLIENT_IP"];
		}  else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) { 
			$client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else if(!empty($_SERVER["REMOTE_ADDR"])) {
			$client_ip = $_SERVER["REMOTE_ADDR"];
		}
		return $client_ip;
    }


	/**
	 * 读取跟地址
	 * @return [type] [description]
	 */
	 public function getHomeLink( $withProtocol = true ) {
		
		if ( $withProtocol ) {
			$proto ='http://';
			if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
				$proto = 'https://';
			}
			elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
				$proto = 'https://';
			}
			elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
				$proto = 'https://';
			}

			return $proto . $_SERVER["HTTP_HOST"];
		}

		return $_SERVER["HTTP_HOST"];

	}


	static public function getLocation() {

		if ( !empty($_SERVER['HTTP_TUANDUIMAO_LOCATION']) ) {
			return $_SERVER['HTTP_TUANDUIMAO_LOCATION'];
		}


		$proto ='http://';
		
		if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
			$proto = 'https://';
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
			$proto = 'https://';
		}
		elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')  {
			$proto = 'https://';
		}

		

		return $proto . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
	}

	static public function getRouter(){

		$location=explode('/',self::getLocation());

		if($location){

			return ['controller'=>$location[count($location)-2],'method'=>$location[count($location)-1]];

		}

	}

	/**
	 * 检查地址信息是否为地址
	 * @param  [type]  $url [description]
	 * @return boolean	   [description]
	 */
	static public function isURL( $url ) {

		if ( !is_string($url) ) {
			return false;
		}
		
		$scheme = parse_url($url, PHP_URL_SCHEME);
		
		if ( $scheme == 'http' || $scheme == 'https') {
			return true;
		}

		if ( substr($url, 0, 2) == "//") {
			return true;
		}

		return false;
	}



	/**
	 * 读取服务器IP地址
	 * @return [type] [description]
	 */
	public function getServerIP() {

		if ( isset($_SERVER['HTTP_TUANDUIMAO_HOST']) ) {
			return $this->getHostByName($_SERVER['HTTP_TUANDUIMAO_HOST']);
		}

		if(isset($_SERVER)){
			if($_SERVER['SERVER_ADDR']){
				$server_ip=$_SERVER['SERVER_ADDR'];
			}else{
				$server_ip=$_SERVER['LOCAL_ADDR'];
			}
		}else{
			$server_ip = getenv('SERVER_ADDR');
		}
		return $server_ip;
	}



	/**
	 * 读取域名IP地址 (带缓存)
	 * @param  [type] $host [description]
	 * @return [type]	   [description]
	 */
	public function getHostByName( $hostname, $no_cache=false ) {
		$mem = new Mem(false,'Host:');
		$host_ip = $mem->get("$hostname");
		if( $no_cache === true || $host_ip === false )  {
			$host_ip = @gethostbyname($hostname);
			$expires_at = 2592000; // 30天后过期
			$mem->set("$hostname", $host_ip, $expires_at );
		}
		return $host_ip;
	}



	/**
	 * 请求来源方式是否为 Ajax 
	 */
	public function isAjax() {
		return (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest");
	}



	/**
	 * 智能判断请求返回结果的数据类型
	 * @return String html | json | null
	 */
	public function responseType() {
		
		if ( isset($_GET['_tdm_respdatatype'])  &&  in_array($_GET['_tdm_respdatatype'], ['json','html']) )   {
			return $_GET['_tdm_respdatatype'];
		}

		$headers = $this->getHeaders();
		// echo "RESPONSE TYPE: ". $headers['Content-Type'];
		// echo "<pre>";
		//  print_r( $headers);
		
		if ( isset($headers['Content-Type']) && in_array($headers['Content-Type'], ['json','html','xpmse/json','application/json','application/html',"application/api","application/noframe","application/portal"]) ) {
			return $headers['Content-Type'];
		}

		if ( isset($headers['Accept']) && is_string($headers['Accept']) ){
			$types = explode(',',$headers['Accept']);
			if ( in_array('application/json', $types) ) {
				return 'application/json';
			}
		}

		return  null;
	}



	static public function genStr( $length = 16 ) {
		$ut = new Self();
		return $ut->genString( $length );
	}


	/**
	 * 生成随机字符串
	 * @param  integer $length 字符串长度(默认16位)
	 * @return string 字符串
	 */
	public function genString( $length=16 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
		  $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}


	static public function genNum( $length=12 ) {
		$chars = "0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
		  $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}


	/**
	 * 生成唯一ID
	 * @param  integer $lenght [description]
	 * @param  boolean $todec  [description]
	 * @return [type]		  [description]
	 */
	static public function uniqid( int $length = 16 ) {

		$length = ($length < 16) ?  16 : $length;
		$rlen = $length - 3;
		$bytes = (string)hexdec(bin2hex(random_bytes(ceil($rlen/2))));
		$bytes = substr( $bytes, 0, $length);
		$diff = $length - strlen( $bytes );
		for( $i=0; $i< $diff; $i++) {
			$bytes .= "0";
		}
		return $bytes;

	}




	/**
	 * 字符处理
	 */
	public function strMaxLength( $str, $max, $tail="..." ) {
		if (mb_strlen($str,"UTF-8") > $max ) {
			return mb_substr($str, 0, $max, "UTF-8") . $tail;
		}
		return $str;
	}


	/**
	 * 检查浏览器类型
	 * @return  array 浏览器信息
	 * 
	 *	agent 浏览器签名信息
	 *	browser 浏览器名称 ( browscap.ini 中定义 )
	 *	comment 浏览器描述 ( browscap.ini 中定义 )
	 *	device_type 设备类型  Mobile Phone | Desktop  ( browscap.ini 中定义 ) | Cli
	 *	ismobiledevice 是否是手持设备
	 *	istablet  是否是平板
	 *	iswechat  是否是微信浏览器 ( 在微信里打开 )
     *	isdingtalk 是否是钉钉浏览器 ( 在钉钉里打开 )
     *  iscli  是否是cli模式
	 *	platform  操作系统
	 *	version  浏览器版本
	 */
	public function getBrowser() {
        $browser  = [];
		if ( !empty($_SERVER['HTTP_USER_AGENT']) ) {
			$browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);	
        } else if ( self::iscli() ) {

            $browser = [
                "browser" => "Command",
                "device_type" => "Cli"
            ];
        }

		unset($browser['browser_name_regex']); // 这个变量存在，则程序会终止。 非常诡异？触发了PHP的BUG？
		unset($browser['browser_name_pattern']);
		$browser['agent'] = $_SERVER['HTTP_USER_AGENT'];
		$browser['iswechat'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) ? true : false;
		$browser['isdingtalk'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false ) ? true : false;
		return $browser;
	}


	public static  function browser() {
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			$browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);	
		}
		unset($browser['browser_name_regex']); // 这个变量存在，则程序会终止。 非常诡异？触发了PHP的BUG？
		unset($browser['browser_name_pattern']);

		$browser['agent'] = $_SERVER['HTTP_USER_AGENT'];
		$browser['iswechat'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) ? true : false;
		$browser['isdingtalk'] = ( strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false ) ? true : false;

		$browser['type'] = 'desktop';
		if (  $browser['ismobiledevice']  ) {
			$browser['type'] = 'mobile';
		}

		if (  $browser['iswechat']  ) {
			$browser['type'] = 'wechat';
		}


		if( $_GET['_debug'] == 1 ){
			echo "<pre>";
			var_dump($browser);
			var_dump($_SERVER);
			echo "</pre>";
		}


		return $browser;
	}






	public function surl( $url, $nocache=false ) {
		$ut = new Utils;
		return $ut->ShortUrl($url, $nocache);
	}


	/**
	 * 短地址生成器
	 * @param string $url 源网址
	 * @param bool $nocache 不启用缓存，默认false 启用缓存
	 * @return string 短网址
	 */
	public function ShortUrl( $url, $nocache=false ) {

		// Host 短链地址，提升访问速度
		$mem = new Mem(false, 'ShortUrl:');
		$key = md5($url);
		$shorturl = $mem->get("$key");

	   	if ( $shorturl === false || $nocache )  {
			$resp = $this->Request('GET','https://api.weibo.com/2/short_url/shorten.json', 
				['query' => ['url_long'=>urlencode($url), 'source'=>'3945477619']
			]);
			
			if ( isset($resp['urls']) && count($resp['urls']) > 0 ) {
				$respUrl = end($resp['urls']);
				$shorturl = $respUrl['url_short'];
				$urlr = parse_url($shorturl);
				if ( $urlr['host'] == 't.cn') {
					$mem->set("$key", $shorturl );
				}
			} else {
				throw new Excp('生成短链错误', 400, ["url"=>$url, "resp"=>$resp] );
			}
 
		}

		return $shorturl;
	}

	/**
	 * 读取HTTP Request Header
	 * @return [type] [description]
	 */
	public function getHeaders() {

	   if (!function_exists('apache_request_headers'))
		{   

		   foreach( $_SERVER as $key => $value )
		   {
			   if ( substr($key,0,5)=="HTTP_" )
			   {
				   $key = str_replace( " " , "-" , ucwords( strtolower( str_replace( "_" , " " , substr( $key , 5 ) ))));
				   $out[$key]=$value;
			   }
			   else
			   {
				   $out[$key]=$value;
			   }
		   }
		   return $out;
		} 

		return apache_request_headers();
	}


	/**
	 * 设定当前页面响应类型
	 * @param string $type [description]
	 */
	public function setRespType( $type = 'text/html') {
		@header("Content-type: $type");
		$GLOBALS['_RESPONSE-CONTENT-TYPE'] = $type;
	}

	/**
	 * 读取当前页面响应类型
	 * @param  string $type [description]
	 * @return [type]	   [description]
	 */
	public function getRespType() {

		$type = $this->responseType();
		
		// Global
		if ( isset( $GLOBALS['_RESPONSE-CONTENT-TYPE'] ) ) {
			$type =  $GLOBALS['_RESPONSE-CONTENT-TYPE'];
		}

		if ( $type == null ) {
			$headers = $this->getHeaders();
			$type = (isset($headers['CONTENT_TYPE'])) ? $headers['CONTENT_TYPE'] : 'text/html';
			$accept = (isset($headers['Accept'])) ? explode(',',$headers['Accept']) : [];
			if ( in_array('application/json', $accept )) {
				$type = 'application/json';
			}
		}
		
		return $type;
	}



	/**
	 * 二位数组唯一主键去重
	 * @param  array $array   需排序的数组 (按引用传递)
	 * @param  string $key 唯一主键名称
	 * @return array 去重后的数组
	 */
	public function array_unique_2d( & $array, $key ) {
		$temp_array = [];
		$key_array = []; 
		$i = 0; 
		
		foreach($array as $val) { 
			if (isset($val[$key]) && !in_array($val[$key], $key_array)) { 
				$key_array[$i] = $val[$key]; 
				$temp_array[$i] = $val; 
			} 
			$i++; 
		} 

		$array = $temp_array;
		return $temp_array;
	}


	/**
	 * 将字节数转换为可读输出
	 * @param  int $size 字节大小
	 * @return 
	 */
	public static function readableFilesize($size, $precision = 2, $space = ' ') {
		if( $size <= 0 ) {
			return '0' . $space . 'KB';
		}

		if( $size === 1 ) {
			return '1' . $space . 'byte';
		}
		
		$mod = 1024;
		$units = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
		for( $i = 0; $size > $mod && $i < count($units) - 1; ++$i ) {
			$size /= $mod;
		}
		return round($size, $precision) . $space . $units[$i];
    }
    

	/**
	 * 推送文件到云端地址 (支持分段)
	 * @param  [type] $bigfile [description]
	 * @param  [type] $url	 [description]
	 * @param  array  $opt	 [description]
	 * @return [type]		  [description]
	 */
	public static function upload( $file, $url, $opt=[], $before=null, $complete=null ) {

        $chunk = empty($opt['chunk']) ? 1048576 : intval($opt['chunk']); // 默认 1M

        // zip support
        if ( strpos($file,"zip://") === 0 ) {
            $fh = fopen($file, 'r');
            $exists = ($fh !== false);
            fclose($fh);
            if ( !$exists ) {
                throw new Excp("文件不存在或没有读取权限", 404, ['file'=>$file]);
            }

            $filesize = $opt["filesize"];
            $info = explode("#",basename($file));
            if ( empty($info[1]) ) {
                $info[1] = $info[0];
            }
            $fname = basename($info[1]);

            // 解压缩文件
            $folder = sys_get_temp_dir() . "/". time();
            @mkdir($folder, 0777, true);
            $tmpfile =  "$folder/$fname";
            $fw = fopen($tmpfile, 'a+');
            if ( $fw === false ) {
                echo $tmpfile;
                throw new Excp("无法解压缩文件", 500, ['tmpfile'=>$tmpfile]);
            }
            
            $fd = fopen($file, 'r');
            while (!feof($fd)) {
                $content = fread($fd, $chunk);
                fwrite($fw, $content);
            }
            fclose( $fw );
            fclose( $fd );
            $file = $tmpfile;
        }

        if ( !file_exists($file) ) {
            throw new Excp("文件不存在", 404, ['file'=>$file]);
        }
        if ( !is_readable($file) ) {
            throw new Excp("文件无读取权限", 403, ['file'=>$file]);
        }
        $filesize = filesize($file);
        $fname = basename($file);

		
		$chunks = ($filesize > 0) ? ceil( floatval( $filesize / $chunk) ) : 0;
		$opt['data']['chunks'] = $chunks;
		$opt['type'] = 'media';

		if ( $filesize > 2147483648 ) { // 文件大小不能超过2G
			throw new Excp("文件不能超过2G", 403, ['file'=>$file, 'size'=>$filesize]);
		}

		if ( $before == null ) {
			$before = function( & $opt ){};
		}

		if ( $complete == null ) {
			$complete = function( $resp, & $opt ){ };
		}

		$fp = fopen($file, "r");
		$resp=[]; $cnt = 0;
        $mimetype = self::mimetype( $file );
        $header = is_array($opt["header"]) ?  $opt["header"] : [];
        // array_push($header, "Content-Type: multipart/form-data");

		for ($len = 0; $len < $filesize; $len += $chunk) {
			fseek($fp, $len);
			$opt['data']['chunk'] = $cnt;
			$opt['data']['__files'][0] = [
				"filename" => $file,
				"name" => !empty($opt["name"]) ? $opt["name"] : $fname,
				"mimetype" => $mimetype,
				"data" => fread($fp, $chunk)
            ];
            
            // Count Header
            $total = $len + $chunk;
            if ( $total >= $filesize ) {
                $total = $filesize - 1;
            }
            $content_range = "Content-Range: bytes {$len}-{$total}/{$filesize}";  // content-range: bytes 51200-102399/181879
            $content_disposition = "Content-Disposition: attachment; filename=\"{$fname}\"";  // content-disposition: attachment; filename="icon-sm.png"
            $opt["header"] = $header;
            array_push($opt["header"], $content_disposition );
            array_push($opt["header"], $content_range );
            
			try {
				$before( $opt );
				$resp[$cnt] = self::Request( 'POST', $url, $opt );
				if ( is_array($resp[$cnt]) &&  array_key_exists('code', $resp[$cnt]) &&  $resp[$cnt]['code'] != 0 ) {
					throw new Excp( $resp[$cnt]['message'],  $resp[$cnt]['code'],  $resp[$cnt]['extra']);
					fclose($fp);
				}
				$complete( $resp[$cnt], $opt );
			} catch( Excp $e ) {
				fclose($fp);
				throw $e;
			} catch( Exception $e ) {
				fclose( $fp );
				throw $e;
			}

			$cnt ++;
		}

        fclose( $fp );
        if ( $tmpfile != "") {
            @unlink($tmpfile);
        }
		return $resp;
	}


	/**
	 * 通用 Requst 方法 ( 快捷操作 )
	 * @param [type] $method [description]
	 * @param [type] $url	[description]
	 * @param array  $opt	[description]
	 */
	public static  function Req( $method , $url, $opt=[] ) {
		$ut = new self();
		return $ut->Request($method , $url, $opt);
	}


	/**
	 * 通用 Requst 方法 ( DNS缓存在内存中 )
	 * @param [type] $method 请求方法 POST / GET / PUT /DELETE
	 * @param [type] $url   请求地址 http://xxxx.com
	 * @param [type] $opt   header HTTP REQUEST 头  ['ak: xxxxds', 'sk: xxx' ]
	 *					cookie HTTP REQUEST Cookie ["key"=>"value"...]
	 *					  query HTTP GET查询参数 ['name'=>'value','name2'=>'value2']
	 *					  data  HTTP POST查询参数 ['name'=>'value','name2'=>'value2']
	 *					  type  HTTP REQUEST TYPE 默认为 form . 有效数值 form/json/raw/media
	 *					  datatype HTTP RESPONSE TYPE 默认为json. 有效值 json/html/auto/xml  
	 *					  follow 是否抓取301/302之后的地址 默认为true. 有效值 true/false	CURLOPT_FOLLOWLOCATION
	 *					  nocheck 是否验证HTTP状态码（默认是false)
	 *					  verifypeer 是否校验HTTPS证书 (默认是false)
	 *					  verifyhost 是否校验HTTPS证书与域名关系 ( 默认是false ) 
	 *					  urlencode 是否 ENCODE QUERY 参数（默认是true)
	 *					  dnscache 是否开启DNS缓存 默认为true. 有效值 true/false
	 *					  curlopt = [] 其他 CURLOPT_*  ( KEY => VALUE)
	 *					  cert 双向认证证书路径
	 *					  cert.key 双向认证证书私钥路径
	 *					  cert.keytype 证书类型，默认为 PEM
	 *					  rootca CA证书 (验证服务器证书的真实性)
	 *					  
	 *					  
	 * @return string/array datatype = json 返回数组 
	 *					  datatype = html 返回 RESPONSE Body String
	 *					  datatype = auto 返回 [ "body"=>RESPONSE Body String, "type"=>Content-Type ]
	 */	
	public static function Request( $method, $url, $opt=[] ) {

		$ch = curl_init();
		$options = array();
		$resp_body = array();

		$header = (isset( $opt['header'] ) ) ? $opt['header'] : [];
		$query = (isset( $opt['query'] ) ) ? $opt['query'] : [];
		$data = (isset( $opt['data'] ) ) ? $opt['data'] : [];
		$requestType =(isset( $opt['type'] ) ) ? strtolower($opt['type']) : 'form';
		$responseType =(isset( $opt['datatype'] ) ) ? strtolower($opt['datatype']) : 'json';
		$nocheck = (isset( $opt['nocheck'] ) ) ? strtolower($opt['nocheck']) : false;
		$verifypeer = (isset( $opt['verifypeer'] ) ) ? $opt['verifypeer'] : false;
		$verifyhost = (isset( $opt['verifyhost'] ) ) ? $opt['verifyhost'] : false;
		$debug = (isset( $opt['debug'] ) ) ? $opt['debug'] : false;

		$urlr = parse_url($url);
		$urlr['path'] = ( isset($urlr['path']) ) ? $urlr['path'] : "";
		$host_name = $urlr['host'];
		$user = ( isset($urlr['user']) && $urlr['user'] != "")? "{$urlr['user']}@" : ""; 
		$user = (  isset($urlr['user']) &&  isset($urlr['pass']) && $urlr['user'] != "" && $urlr['pass'] != "")? "{$urlr['user']}:{$urlr['pass']}@" : $user;
		$port = ( isset($urlr['port']) && $urlr['port'] != "")? ":{$urlr['port']}" : "";
		$options['url'] = "{$user}{$host_name}{$port}{$urlr['path']}";


		// Cookies 
		$cookie = (isset( $opt['cookie'] ) ) ? $opt['cookie'] : [];
		if ( !empty($cookie) ) {
			$header[] = "cookie: ". http_build_query($_COOKIE, '', ';');
		}

		// CURL 的可选参配置
		$opt['follow'] = (isset($opt['follow'])) ? $opt['follow'] : true;
		$opt['dnscache'] = (isset($opt['dnscache'])) ? $opt['dnscache'] : true;
		$opt['curlopt'] = (isset($opt['curlopt'])) ? $opt['curlopt'] : [];
		$opt['urlencode']= (isset($opt['urlencode'])) ? $opt['urlencode'] : true;


		// 缓存Host IP加速请求速度
		$mem = new Mem(false,'Host:');
		if( $opt['dnscache'] === true && $verifyhost === false )  {
			$host_ip = $mem->get("$host_name");
			if ( $host_ip === false )  {
				$host_ip = gethostbyname($host_name);
				$expires_at = 2592000; // 30天后过期
				$mem->set("$host_name", $host_ip, $expires_at );
			}
			$options['url'] = str_replace($host_name, $host_ip, $options['url']);
		}


		// HTTPS 配置选项
		if ($urlr['scheme'] == 'https') {

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifypeer);   // 不验证证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifyhost);   // 不验证域名匹配关系
			
			// CA根证书（用来验证的网站证书是否是CA颁布
			if ( isset( $opt['rootca']) && file_exists( $opt['rootca'] ) ) {
				 curl_setopt($ch, CURLOPT_CAINFO, $cacert);
			}

			// 双向验证 KEY 
			if ( isset( $opt['cert.key']) && file_exists( $opt['cert.key'] ) ) {

				$opt['cert.keytype']  = empty($opt['cert.keytype']) ? 'PEM' : $opt['cert.keytype'];
				
				curl_setopt($ch,CURLOPT_SSLKEYTYPE,$opt['cert.keytype']);
				curl_setopt($ch,CURLOPT_SSLKEY, $opt['cert.key'] );
			}

			// 双向验证 证书
			if ( isset( $opt['cert']) && file_exists( $opt['cert'] ) ) {
				curl_setopt($ch,CURLOPT_SSLCERT, $opt['cert']);
			}

		}



		// Query String 解析
		$query_string_arr = [];
		if( isset($urlr['query'] )  && $urlr['query']  != "" ) {
		   	$query_string_arr = explode('&', $urlr['query']);
		}
		foreach ( $query as $key => $value) {
			if ( $opt['urlencode'] == true) {
				$value = urlencode($value);
			}
			$query_string_arr[] = "$key=$value";
		}
		$query_string = implode('&', $query_string_arr );
		$options['url'] = "{$urlr['scheme']}://{$options['url']}";
		if ( $query_string != "" ) {
			$options['url'] = $options['url'] . "?$query_string";
		}

		
		// POST Data 解析
		$postfields = null;

		if ( count($data) > 0  ) {
			switch ($requestType) {
				case 'form':
					// $options['body'] = $data;
					$postfields = $data;
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
					break;

				case 'media':
					srand((double)microtime()*1000000); 
					$boundary = '------WebKitFormBoundary'.substr(md5(rand(0,32000)),0,10); 
					$header[] = "Content-Type: multipart/form-data; boundary=$boundary"; 
					$content = '--'.$boundary."\r\n";
					$files = (isset($data['__files'])) ? $data['__files'] : [];
					if (isset($data['__files'])) {
						unset( $data['__files'] );
					}

					// Form Data
					$formdata = '';
					foreach ($data as $key => $val) {
						$formdata .= "Content-Disposition: form-data; name=\"".$key."\"\r\n"; 
						$formdata .= "Content-Type: text/plain\r\n\r\n"; 
						if(is_array($val)){ 
							$formdata .= json_encode($val)."\r\n"; // 数组使用json encode后方便处理 
						}else{ 
							$formdata .= rawurlencode($val)."\r\n"; 
						} 
						$formdata .= '--'.$boundary."\r\n"; 
					}

					// Files
					$filedata = ''; $filedata_debug = '';

					foreach($files as $val){ 
						$val['filename'] = isset($val['filename']) ? basename($val['filename']) : 'unknown.tdm';
						$val['name'] = isset( $val['name'] ) ? $val['name'] : 'tdm_file';
						$val['mimetype'] = isset( $val['mimetype'] ) ? $val['mimetype'] : mime_content_type($val['filename']);
					   
						$filedata .= "Content-Disposition: form-data; name=\"".$val['name']."\"; filename=\"".$val['filename']."\"\r\n"; 
						$filedata .= "Content-Type: ".$val['mimetype']."\r\n\r\n";
						$filedata .= $val['data']."\r\n"; 
						$filedata .= '--'.$boundary.""; 

						$filedata_debug .= "Content-Disposition: form-data; name=\"".$val['name']."\"; filename=\"".$val['filename']."\"\r\n"; 
						$filedata_debug .= "Content-Type: ".$val['mimetype']."\r\n\r\n";
						$filedata_debug .= base64_encode($val['data'])."\r\n"; 
						$filedata_debug .= '--'.$boundary.""; 
					}

					$content.= $formdata.$filedata."--\r\n\r\n"; 
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					$postfields = $formdata.$filedata_debug."--\r\n\r\n";
					curl_setopt($ch, CURLOPT_POSTFIELDS, $content );

					break;

				case 'json':
					$postfields = json_encode($data);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data) );
					break;

				case 'raw':
					$postfields = json_encode($data, JSON_UNESCAPED_UNICODE);
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE) );
					break;
				default:
					curl_setopt($ch, CURLOPT_POST, 1);
					$postfields = $data;
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
					break;
			}
		}


		 // 请求地址和Header
		$header[] = "Host: $host_name";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $options['url'] );

		// CURL 的其他配置项
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //设置连接超时时间为5妙
		curl_setopt($ch, CURLOPT_FAILONERROR, FALSE );
		curl_setopt($ch, CURLOPT_NOBODY, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE); // RESPONSE Header

			// 是否抓取跳转之后的页面
			if ( $opt['follow'] ) {  
				  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
			}

		// CURL 自定义配置项
		foreach ($opt['curlopt'] as $key => $value) {
			eval("\$opt_name = CURLOPT_$key;");
		   /* echo "<pre>";
			echo "$opt_name = $value \n";
			echo "CURLOPT_USERAGENT = ".CURLOPT_USERAGENT." \n";
			echo "</pre>"; */
			curl_setopt($ch, $opt_name, $value);
		}


		$respData = curl_exec($ch);
		
		// 响应头
		$respHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$respHeader = substr($respData, 0, $respHeaderSize);
		$respHeaders = array_filter(explode("\n", $respHeader));
		$respHeaderMap = [];
		foreach ($respHeaders as $hd) {
			$kv = explode(':', $hd);
			$cnt = count($kv);

			if ( isset($kv[1]) ) {
				$k = trim($kv[0]);
				$v = trim($kv[1]);
				
				$vr = $kv;
				unset($vr[0]);
				$vs = trim(implode(':', $vr));
				$respHeaderMap[$k] = $vs;
			}
		}

		$body = substr($respData, $respHeaderSize);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// 输入调试信息
		if ( $debug !== false ) {
			if ( is_array($postfields) ) {
				$postfields = json_encode($postfields, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			}

			$debugmsg = "\n\n <h2 style='color:red'> Utils:Request Debug info </h2> "  
					  ."\n\n<b style='color:red'>Function Options: </b>\n". json_encode($opt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
					  . "\n\n<b style='color:red'>Reqeust URL :</b>\n" . $url

					  . "\n\n<b style='color:red'>Response Status: </b> \n$http_code \n"
					  . "\n\n<b  style='color:red'>Response Header: </b>\n". json_encode($respHeaderMap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
					  . "\n\n<b  style='color:red'>Response Body: </b>\n". $body 

					  . "\n\n<b style='color:red'>Reqeust Method :</b>\n" . $method
					  . "\n\n<b style='color:red'>Request Header: </b>\n" . json_encode($header, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )

					  . "\n\n<b  style='color:red'>POST FIELDS: </b>\n". $postfields 

					  . "\n\n<b style='color:red'>Request Url: </b>\n". json_encode($urlr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)

					  . "\n\n<b style='color:red'>Request Reqeust URL: </b>\n". var_export($options['url'], true) . "\n"

					  . "\n\n<b style='color:red'>Request Options: </b>\n". json_encode($options, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
					  . "\n\n<b style='color:red'>Request Query: </b>\n". json_encode($query_string, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
					  . "\n\n<b style='color:red'>Request Body: </b>\n". json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)

					  ;


			echo "<pre>$debugmsg</pre>";

			if ( $debug == "break") {
				return "";
			}

		}


		if ( $http_code != 200 && $nocheck == false ) {

			if ( ( $http_code == 302 && !$opt['follow'] )|| ( $http_code == 301 && !$opt['follow'] ) ) {
				$respHeaderMap['Status'] = $http_code;
				return $respHeaderMap;
			}

			//清空缓存，重试一次
			if ( self::$_request_retry < 1 ) {
				$mem->del("cache/host/$host_name"); //清除Host缓存，防止因缓存失效导致请求失败
				self::$_request_retry = self::$_request_retry + 1;
				return self::Request( $method, $url, $opt );
			}

			$errcode = "$http_code";
			$errmsg = "网关未知错误 ( http_code=$http_code ) " ;
			self::$_request_retry = 0;

			if ( curl_errno( $ch ) ) {
				$errmsg = curl_error( $ch );
				$errcode = curl_errno( $ch );
			} else {
				$errcode = "$http_code";
				$errmsg = "<b style='color:red'>网关错误: $http_code </b>\n"
						. "\n<b>Reqeust URL :</b>" . $url
						. "\n\n<b  style='color:red'>Response Header: </b>". var_export($respHeaderMap, true )
						. "\n\n<b  style='color:red'>Response Body: </b>". $body 
						. "\n\n<b>Reqeust Method :</b>" . $method
						. "\n\n<b>Request Header: </b>" . var_export($header, true )
						. "\n\n<b>Request Url: </b>". var_export($urlr, true)
						. "\n\n<b>Request Options: </b>". var_export($options, true )
						. "\n\n<b>Request Query: </b>". var_export($query_string, true )
						. "\n\n<b>Request Body: </b>". var_export($data, true );
			}

			throw new Excp("[$http_code] Utils::Request 请求异常", $errcode, 
				[
					'response' => [
						'status' => $http_code,
						'header' => $respHeaderMap,
						'body' => $body
					],
					'message' => $errmsg,
					'http_code'=> self::get($http_code),
					'Response Header' => self::get( $respHeaderMap ),
					'Response Body' => self::get( $body ),
					'Reqeust URL' => self::get( $url ),
					'Reqeust Method' => self::get( $method ),
					'Reqeust Header' => self::get( $header ),
					'Reqeust URLR ' => self::get( $urlr ),
					'Reqeust Query ' => self::get( $query_string ),
					'Reqeust Data ' => self::get( $data ),
					'Reqeust Options ' => self::get( $options )

				]);

		}

		if ( isset($respHeaderMap['Content-Type']) ) {

			if ( $responseType == 'auto' ) {
				return ['body'=>$body, 'type'=>$respHeaderMap['Content-Type']];
			}

			if ( $responseType == 'html' || $responseType == 'text' ) {
				@header("Content-Type: {$respHeaderMap['Content-Type']}");
			}

			if ( $responseType == 'xml' ) {
				@header("Content-Type: {$respHeaderMap['Content-Type']}");
			}

			$t = explode('/', $respHeaderMap['Content-Type']);
			if( $t[0] == 'image') {
				// header("Content-Type: {$respHeaderMap['Content-Type']}");
				echo $body;
				die();
			}
		}
		

		if ( $http_code != 200 && $nocheck == true ) {
			if ( $body != null && $responseType == 'json') {
				$resp_body = json_decode($body, true );
			}
			return ['http_code'=>$http_code, 'resp_body'=>$resp_body];
		}

		$resp_body = null;
		if ( $body != null && $responseType == 'json') {
			$resp_body = json_decode($body, true );

			if( json_last_error() !== JSON_ERROR_NONE) {

				$errcode = '500';
				$errmsg = "<b style='color:red'>返回结果解析错误:".json_last_error_msg() ." </b>\n"
						. "\n<b>Reqeust URL :</b>" . $url
						. "\n\n<b  style='color:red'>Response Header: </b>". var_export($respHeaderMap, true )
						. "\n\n<b  style='color:red'>Response Body: </b>". $body 
						. "\n\n<b>Reqeust Method :</b>" . $method
						. "\n\n<b>Request Header: </b>" . var_export($header, true )
						. "\n\n<b>Request Url: </b>". var_export($urlr, true)
						. "\n\n<b>Request Options: </b>". var_export($options, true )
						. "\n\n<b>Request Query: </b>". var_export($query_string, true )
						. "\n\n<b>Request Body: </b>". var_export($data, true );

				// throw new Exception("Parse Error: " . $errmsg );
				throw new Excp("[$http_code] Utils::Request 返回数据解析错误: ".json_last_error_msg(), $errcode, [ 

					'response' => [
						'status' => $http_code,
						'header' => $respHeaderMap,
						'body' => $body
					],

					'http_code'=> self::get($http_code),
					'Response Header' => self::get( $respHeaderMap ),
					'Response Body' => self::get( $body ),
					'Reqeust URL' => self::get( $url ),
					'Reqeust Method' => self::get( $method ),
					'Reqeust Header' => self::get( $header ),
					'Reqeust URLR ' => self::get( $urlr ),
					'Reqeust Query ' => self::get( $query_string ),
					'Reqeust Data ' => self::get( $data ),
					'Reqeust Options ' => self::get( $options )
					]);
			}

			if ( $resp_body === null ) {
				
				$errcode = '500';
				$errmsg = "<b style='color:red'>返回结果解析错误: 未知解析错误 </b>\n"
						. "\n<b>Reqeust URL :</b>" . $url
						. "\n\n<b  style='color:red'>Response Header: </b>". var_export($respHeaderMap, true )
						. "\n\n<b  style='color:red'>Response Body: </b>". $body 
						. "\n\n<b>Reqeust Method :</b>" . $method
						. "\n\n<b>Request Header: </b>" . var_export($header, true )
						. "\n\n<b>Request Url: </b>". var_export($urlr, true)
						. "\n\n<b>Request Options: </b>". var_export($options, true )
						. "\n\n<b>Request Query: </b>". var_export($query_string, true )
						. "\n\n<b>Request Body: </b>". var_export($data, true );

				// throw new Exception("Parse Error: " . $errmsg );
				throw new Excp("[$http_code] Utils::Request 返回数据解析错误", $errcode, [

					'response' => [
						'status' => $http_code,
						'header' => $respHeaderMap,
						'body' => $body
					],

					'http_code'=> self::get($http_code),
					'Response Header' => self::get( $respHeaderMap ),
					'Response Body' => self::get( $body ),
					'Reqeust URL' => self::get( $url ),
					'Reqeust Method' => self::get( $method ),
					'Reqeust Header' => self::get( $header ),
					'Reqeust URLR ' => self::get( $urlr ),
					'Reqeust Query ' => self::get( $query_string ),
					'Reqeust Data ' => self::get( $data ),
					'Reqeust Options ' => self::get( $options )
				]);
			}

		} else if ( $body != null && $responseType == 'xml' ) { 
			$xml = simplexml_load_string(
				$body, 
				'SimpleXMLElement', 
				LIBXML_NOCDATA | LIBXML_NOBLANKS
			);
			return $resp_body =  json_decode(json_encode($xml),TRUE);

		} else {
			return $resp_body = $body;
		}
		return $resp_body; 
	}



	public static function https_request($url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		if (curl_errno($curl)) {return 'ERROR '.curl_error($curl);}
		curl_close($curl);
		return $data;
	}
	public static function xml_to_array($xml){
		$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
		if(preg_match_all($reg, $xml, $matches)){
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++){
			$subxml= $matches[2][$i];
			$key = $matches[1][$i];
				if(preg_match( $reg, $subxml )){
					$arr[$key] = self::xml_to_array( $subxml );
				}else{
					$arr[$key] = $subxml;
				}
			}
		}
		return @$arr;
	}
	public static function random($length = 6 , $numeric = 0) {
		PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
		if($numeric) {
			$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
		} else {
			$hash = '';
			$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
			$max = strlen($chars) - 1;
			for($i = 0; $i < $length; $i++) {
				$hash .= $chars[mt_rand(0, $max)];
			}
		}
		return $hash;
	}
	public static function percentEncode($str){
		// 使用urlencode编码后，将"+","*","%7E"做替换即满足ECS API规定的编码规范
	    $res = urlencode($str);
	    $res = preg_replace('/\+/', '%20', $res);
	    $res = preg_replace('/\*/', '%2A', $res);
	    $res = preg_replace('/%7E/', '~', $res);
	    return $res;

	}
	public static function computeSignature($parameters, $accessKeySecret){
	    // 将参数Key按字典顺序排序
	    ksort($parameters);
	    // 生成规范化请求字符串
	    $canonicalizedQueryString = '';
	    foreach($parameters as $key => $value)
		{
	    	$canonicalizedQueryString .= '&' . self::percentEncode($key) . '=' . self::percentEncode($value);
		}
		// 生成用于计算签名的字符串 stringToSign
		$stringToSign = 'GET&%2F&' . self::percentencode(substr($canonicalizedQueryString, 1));

		//echo "<br>".$stringToSign."<br>";

	    // 计算签名，注意accessKeySecret后面要加上字符'&'
		$signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));

		return $signature;
	}


	

}

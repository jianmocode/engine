<?php
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils;
use \Mina\Cache\Redis as Cache;

/**
 * 全局函数库
 */

/*********************************************************************************************************************************************************
 *   ===============
 *     通用函数
 *   ===============
 *********************************************************************************************************************************************************


/**
 * 更新 XpmSE LICENSE
 */
function licenseUpdate() {

	$api = _XPMSE_API_LICENSE . '/update';
	$home = Utils::getHome();
	
	$sysinfo = GetSysinfo();
	$sysinfo['exts'] = implode(',', $sysinfo['exts']);
	$sysinfo['cpu_loads'] = implode(',', $sysinfo['cpu_loads']);

	$query = getSecretQuery(['cmd'=>'updateLicense']);
	$data = array_merge($sysinfo, ['home'=>$home]);
	try {
		$resp = Utils::Request("POST", $api, ['query'=>$query, 'data'=>$data]);
	} catch( Excp $e ) {
		$resp = $e->toArray();
	} catch ( Exception $e ) {
		$resp = [ 'code'=>$e->getCode(), 'message'=>$e->getMessage() ];
	}

	// 系统信息异常 ( 1小时后重试 )
	if ( isset($resp['code']) || $resp['code'] != 0  ) {
		$cache = 'license';
		$c = new Cache( [
			"prefix" => '_pipeline:',
			"host" => Conf::G("mem/redis/host"),
			"port" => Conf::G("mem/redis/port"),
			"passwd"=> Conf::G("mem/redis/password")
		]);

		$c->set("{$cache}:code", 'UNKNOWN', 3600);
		$c->set("{$cache}:text", 'UNKNOWN', 3600);
	}

	return $resp;
}


/**
 * 校验 XpmSE LICENSE
 */
function isLicenseEffect() {

	if ( isset($GLOBALS['_XPMSE_LICENSE']) ) {
		return $GLOBALS['_XPMSE_LICENSE'];
	}

	$c = new Cache( [
		"prefix" => '_pipeline:',
		"host" => Conf::G("mem/redis/host"),
		"port" => Conf::G("mem/redis/port"),
		"passwd"=> Conf::G("mem/redis/password")
	]);

	$cache = 'license';
	$code = $c->get("{$cache}:code");
	$text = $c->get("{$cache}:text");

	// 自动更新 LICENSE
	if ( $code === false || $text === false ) {
		$GLOBALS['_XPMSE_LICENSE'] = false;
		licenseUpdate();
		return false;
	}

	$begin = date('Y-m-01', strtotime(date("Y-m-d")));
	$host = $_SERVER['HTTP_HOST'];
	$license = sha1( $begin . $host . $text );

	if ( $license != $code ) {
		$GLOBALS['_XPMSE_LICENSE'] = false;
		return false;
	}
	$GLOBALS['_XPMSE_LICENSE'] = true;
	return true;
}



/**
 * 生成加密访问签名
 * @param  [type] $params [description]
 * @return [type]         [description]
 */
function getSecretQuery( $params ) {

	$sc = new \Xpmse\Secret();
	$keypair = $sc->getFirstKeypair();
	$appid = $keypair['appid'];
	$signs = $sc->signature($params, $keypair['secret'], $keypair['appid']);
	return array_merge($params, $signs);
}




/**
 * 读取系统环境信息
 * @return [type] [description]
 */
function GetSysinfo() {
	$mem = get_server_memory();
	$cpu = get_server_cpu();
	$xpmse = __GET_VISION();
	$se = __GET_SE_VISION();



	$data = [
		'phpversion'=>PHP_VERSION,
		'company' => Conf::G("general/company"),
		'name' => Conf::G("general/name"),
		'short' => Conf::G("general/short"),
		'created_at' => Conf::G("general/at"),
		'xpmse_version' => $xpmse['version'],
		'xpmse_revision' => $xpmse['revision'],
		'service_version' => $se['version'],
		'service_revision' => $se['revision'],
		'php_version' => PHP_VERSION,
		'os' => PHP_OS,
		'uname'=>php_uname('a'),
		'cpu'=>$cpu['cores'],
		'cpu_loads'=>$cpu['loads'],
		'memory'=>$mem['total'],
		'memory_usage'=>$mem['usage'],
		'domain'=> $_SERVER['SERVER_NAME'],
		'server'=> $_SERVER['SERVER_SOFTWARE'],
		'host'=>$_SERVER['HTTP_HOST'],
		'ip'=>$_SERVER['SERVER_ADDR'],
		'port'=>$_SERVER['SERVER_PORT'],
		'user'=>$_SERVER['USER'],
		'exts' => get_loaded_extensions(),  // PHP 扩展
	];

	$data["roots"] = [
		'app' => _XPMAPP_ROOT,
		'code' => realpath(__DIR__ . '/..'),
		'service' => realpath(__DIR__ . '/../service'),
		'config' => $GLOBALS['_XPMSE_CONFIG_ROOT']
	];

	// 路径信息
	$data['home'] = Utils::getHome();
	$data['backend']  = $data['home'] ."/_a";
	$data['frontend']  = $data['home'];
	$data['api']  = $data['home']."/_api";

	// 读取系统工作时长
	$fp = fopen($data['roots']['config'] . '/config.json', "r");
	$fstat = fstat($fp);
	fclose($fp);

	$data['created_at'] = date('Y年m月d日 H:i:s', $fstat['mtime']);
	$data['uptime'] = time() - $fstat['mtime'];
	$data['uptime_days'] =  floor( $data['uptime']/ (3600*24) );
	$data['uptime_hours'] =  floor( ( $data['uptime'] - $data['uptime_days'] * 3600 * 24) / 3600 );
	$data['uptime_mins'] =  floor( ( $data['uptime'] - $data['uptime_days'] * 3600 * 24 - $data['uptime_hours'] * 3600) /60 );
	$data['uptime_secs'] =  floor( ( $data['uptime'] - $data['uptime_days'] * 3600 * 24 - $data['uptime_hours'] * 3600 - $data['uptime_mins'] * 60 ) );


	// 刷新系统信息
	// $cache = 'sysinfo';
	// $c = new Cache( [
	// 	"prefix" => '_system:',
	// 	"host" => Conf::G("mem/redis/host"),
	// 	"port" => Conf::G("mem/redis/port"),
	// 	"passwd"=> Conf::G("mem/redis/password")
	// ]);
	// $c->setJSON($cache, $data);

	return $data;
}


function get_server_memory(){

	$memory = [
		'total' => 'UNKNOWN',
		'usage' => 'UNKNOWN',
	];

	if ( PHP_OS == 'Linux') {
	    $free = shell_exec('free');
	    $free = (string)trim($free);
	    $free_arr = explode("\n", $free);
	    $mem = explode(" ", $free_arr[1]);
	    $mem = array_filter($mem);
	    $mem = array_merge($mem);
	    $memory = [
	    	'total' => $mem[1],
	    	'usage' => $mem[2],
		];
	}

    return $memory;
}

function get_server_cpu(){
	
	$cpu = [
		'loads'=> 'UNKNOWN',
		'cores'=> ['UNKNOWN','UNKNOWN','UNKNOWN']
	];

	if ( PHP_OS == 'Linux') {
    	$exec_loads = sys_getloadavg();
		$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
		$cpu = [
			'loads'=> $exec_loads,
			'cores'=> $exec_cores,
		];

    }
    return $cpu;
}

function get_server_cpu_usage(){
	if ( PHP_OS == 'Linux') {
    	$exec_loads = sys_getloadavg();
		$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
		$cpu = round($exec_loads[1]/($exec_cores + 1)*100, 0) . '%';
    }
    return $cpu;
}


function get_server_memory_usage(){

	$memory_usage = 'UNKNOWN';
	if ( PHP_OS == 'Linux') {
	    $free = shell_exec('free');
	    $free = (string)trim($free);
	    $free_arr = explode("\n", $free);
	    $mem = explode(" ", $free_arr[1]);
	    $mem = array_filter($mem);
	    $mem = array_merge($mem);
	    $memory_usage = $mem[2]/$mem[1]*100;
	}

    return $memory_usage;
}


function IPLocation( $ip, $ak, $sk ) {
    if ( $ak === null ) {
        return ['status'=>'404', 'message'=>'无百度API配置信息 AK=NULL', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]];
    }

    if ( $sk === null ) {
        return ['status'=>'404', 'message'=>'无百度API配置信息 SK=NULL', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk]];
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
    $request_url = $url.$api.'?'. http_build_query($data);
    
    $json_text = file_get_contents($request_url);
    $resp = json_decode($json_text, true);

    if ( !isset($resp['status']) ) {
    	return ['status'=>'500', 'message'=>'返回结果异常', 'extra'=>['ip'=>$ip, 'ak'=>$ak, 'sk'=>$sk, 'resp'=>$resp]];
    }

    if ( $resp['status'] != 0 || !isset($resp['content']) ) {
        return $resp;
    }

    $resp['content']['status'] = 0;
    return $resp['content'];
}


/**
 * 生成随机密码
 */

function create_password($pw_length = 8)
{
    $randpwd = '';
    for ($i = 0; $i < $pw_length; $i++) 
    {
    	$c = array(
	    	chr(mt_rand(65, 90)),
	    	chr(mt_rand(97, 122)),
	    	chr(mt_rand(48, 57))
    	);
    	$cnt = mt_rand(0, 2);
        $randpwd .= $c[$cnt];
    }
    return $randpwd;
}


/**
 * 是否在微信中打开
 * @return boolean [description]
 */
function is_weixin(){ 
	if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
			return true;
	}	
	return false;
}


/**
 * 生成随机字符串
 * @param  integer $length [description]
 * @return [type]          [description]
 */
function gen_string( $length=16 ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}



/**
 * Translates a number to a short alhanumeric version
 *
 * Translated any number up to 9007199254740992
 * to a shorter version in letters e.g.:
 * 9007199254740989 --> PpQXn7COf
 *
 * specifiying the second argument true, it will
 * translate back e.g.:
 * PpQXn7COf --> 9007199254740989
 *
 * this function is based on any2dec && dec2any by
 * fragmer[at]mail[dot]ru
 * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
 *
 * If you want the alphaID to be at least 3 letter long, use the
 * $pad_up = 3 argument
 *
 * In most cases this is better than totally random ID generators
 * because this can easily avoid duplicate ID's.
 * For example if you correlate the alpha ID to an auto incrementing ID
 * in your database, you're done.
 *
 * The reverse is done because it makes it slightly more cryptic,
 * but it also makes it easier to spread lots of IDs in different
 * directories on your filesystem. Example:
 * $part1 = substr($alpha_id,0,1);
 * $part2 = substr($alpha_id,1,1);
 * $part3 = substr($alpha_id,2,strlen($alpha_id));
 * $destindir = "/".$part1."/".$part2."/".$part3;
 * // by reversing, directories are more evenly spread out. The
 * // first 26 directories already occupy 26 main levels
 *
 * more info on limitation:
 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
 *
 * if you really need this for bigger numbers you probably have to look
 * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
 * or: http://theserverpages.com/php/manual/en/ref.gmp.php
 * but I haven't really dugg into this. If you have more info on those
 * matters feel free to leave a comment.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $number_in = 2188847690240;
 * $alpha_in  = "SpQXn7Cb";
 *
 * // Execute //
 * $alpha_out  = alphaID($number_in, false, 8);
 * $number_out = alphaID($alpha_in, true, 8);
 *
 * if ($number_in != $number_out) {
 *	 echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";
 *	 echo "desired: ".$number_in."\n";
 * }
 * if ($alpha_in != $alpha_out) {
 *	 echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";
 *	 echo "desired: ".$alpha_in."\n";
 * }
 *
 * // Show //
 * echo $number_out." => ".$alpha_out."\n";
 * echo $alpha_in." => ".$number_out."\n";
 * echo alphaID(238328, false)." => ".alphaID(alphaID(238328, false), true)."\n";
 *
 * // expects:
 * // 2188847690240 => SpQXn7Cb
 * // SpQXn7Cb => 2188847690240
 * // aaab => 238328
 *
 * </code>
 *
 * @author	Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author	Simon Franz
 * @author	Deadfish
 * @author  SK83RJOSH
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link	  http://kevin.vanzonneveld.net/
 *
 * @param mixed   $in	  String or long input to translate
 * @param boolean $to_num  Reverses translation when true
 * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
 * @param string  $pass_key Supplying a password makes it harder to calculate the original ID
 *
 * @return mixed string or long
 */
function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
{
	$out   =   '';
	$index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$base  = strlen($index);
	if ($pass_key !== null) {
		// Although this function's purpose is to just make the
		// ID short - and not so much secure,
		// with this patch by Simon Franz (http://blog.snaky.org/)
		// you can optionally supply a password to make it harder
		// to calculate the corresponding numeric ID
		for ($n = 0; $n < strlen($index); $n++) {
			$i[] = substr($index, $n, 1);
		}
		$pass_hash = hash('sha256',$pass_key);
		$pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);
		for ($n = 0; $n < strlen($index); $n++) {
			$p[] =  substr($pass_hash, $n, 1);
		}
		array_multisort($p, SORT_DESC, $i);
		$index = implode($i);
	}
	if ($to_num) {
		// Digital number  <<--  alphabet letter code
		$len = strlen($in) - 1;
		for ($t = $len; $t >= 0; $t--) {
			$bcp = bcpow($base, $len - $t);
			$out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
		}
		if (is_numeric($pad_up)) {
			$pad_up--;
			if ($pad_up > 0) {
				$out -= pow($base, $pad_up);
			}
		}
	} else {
		// Digital number  -->>  alphabet letter code
		if (is_numeric($pad_up)) {
			$pad_up--;
			if ($pad_up > 0) {
				$in += pow($base, $pad_up);
			}
		}
		for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
			$bcp = bcpow($base, $t);
			$a   = floor($in / $bcp) % $base;
			$out = $out . substr($index, $a, 1);
			$in  = $in - ($a * $bcp);
		}
	}
	return $out;
}



/**
 * 数据安全过滤
 * XSS 等
 */
function F( $arr ) {
	return $arr;
}



/**
 * 读取用户IP地址
 * @return [type] [description]
 */
function get_client_ip(){
    $headers = array('HTTP_X_REAL_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
    foreach ($headers as $h){
        $ip = $_SERVER[$h];
        // 有些ip可能隐匿，即为unknown
        if ( isset($ip) && strcasecmp($ip, 'unknown') ){
            break;
        }
    }
    if( $ip ){
        // 可能通过多个代理，其中第一个为真实ip地址
        list($ip) = explode(', ', $ip, 2);      
    }
    /* 如果是服务器自身访问，获取服务器的ip地址(该地址可能是局域网ip)
    if ('127.0.0.1' == $ip){
        $ip = $_SERVER['SERVER_ADDR'];
    }
    */
    return $ip; 
}


/**
 * 根据Wrapper，获取文件内容 
 * @param  string $string  eg: @readme.md
 * @param  string $root    eg: /root
 * @return string          如文件传入String为 Wrapper 则读取文件并返回内容。 如不是 Wrapper, 则返回传入字符串。
 */
function wrapper_file_get_contents( $string, $root = '' ) {
	
	if ( preg_match("/^[@]{1}([a-z0-9A-Z\.]+)$/", $string, $match) ) {
		$name = $match[1];
		$file_name = "$root/$name";
		if ( file_exists($file_name) ) {
			return file_get_contents( $file_name);
		}
		return null;
	}

	return $string;
}



/**
 * 创建数据模型对象 （废弃）
 * 
 * @param string $module_name model名称  结构 <Modules Name>::<model Name> / <model Name> 示例: param::struct , type 
 */
function OM( $module_name="" ) {
	

	$option = explode('::', $module_name );
	
	if ( count($option) == 2 ) {
		$name = $option[1];
		$module = strtolower($option[0]); // 文件名小写
	} else {
		$name = $option[0];
	}

	//把首个字符专为大写
	$name = strtolower($name);
	$c = strtoupper($name[0]);
	$name[0] = $c;

	$model_file = AROOT  . 'model'; 

	if ( $module != "" )  $model_file = $model_file. "/". $module;

	$model_file = $model_file . "/$name.class.php";

	if (!file_exists($model_file) ) die( "$name ($module) 文件不存在 <strong>$model_file</strong>" );

	require_once( $model_file );

	$class_name = "$module"."$name";
	if ( !class_exists($class_name) ) die( "$class_name 类不存在  $model_file" );

	
	return new $class_name();
}


/**
 * 创建模型对象
 * @param [type] $model_name [description]
 * @param [type] $params     [description]
 */
function M( $model_name, $params=[] , $options =[] ) {

	$module_root = AROOT  . '/model';

	$option = explode('::', $model_name );
	if ( count($option) == 2 ) {
		$name = $option[1];
		$module_path = $module_root."/". strtolower($option[0]);
		$class_name = "\\Xpmse\\Model\\{$name}{$option[0]}";
	} else {
		$name = $option[0];
		$module_path = $module_root;
		$class_name = "\\Xpmse\\Model\\{$name}";
	}

	//把首个字符专为大写
	$name = ucwords(strtolower($name)); //Name
	
	$model_file = $module_path . "/$name.php";
	if (!file_exists($model_file) ) {
		$model_file =  $module_path . "/" .strtolower($name) . ".class.php";
	}

	if (!file_exists($model_file) ) {
		throw new Excp('模块不存在', "404", ['model_file'=>$model_file, 'model_name'=>$model_name, 'params'=>$params]);
	}

	require_once( $model_file );
	if ( !class_exists($class_name) ) {
		echo $class_name;
		throw new Excp('模块未定义', "404", ['class_name'=>$class_name, 'model_file'=>$model_file, 'model_name'=>$model_name, 'params'=>$params]);
	}

	return new $class_name( $params,  $options );
}


/**
 * 页面路由地址
 * @param [type] $namespace [description]
 * @param [type] $c         [description]
 * @param [type] $a         [description]
 */
function R( $namespace=null, $c=null, $a=null, $query=array(), $rewrite=true ) {

	$url = '';
	$params = array();

	// echo "rewrite: $rewrite\n";
	if ( $rewrite == true ) {
		$url = '/_a';
		if ($namespace != null) { $url .= "/{$namespace}"; unset($query['n']); }
		if ($c != null) { $url .= "/{$c}"; unset($query['c']); }
		if ($a != null) { $url .= "/{$a}"; unset($query['a']); }

	} else { 

		if ($namespace != null) { array_push($params,"n=$namespace"); unset($query['n']); }
		if ($c != null){ array_push($params,"c=$c"); unset($query['c']); }
		if ($a != null) { array_push($params,"a=$a"); unset($query['a']); }
	}
	

	// echo "url:$url\n";
	
	foreach ($query as $key => $value) {
		$value = urlencode($value);
		array_push($params,"$key=$value");
	}
	
	$queryString = implode('&',$params);

	if ( $queryString != "" ) {
		$url =empty($url) ? "/" : $url;
		$url = $url . "?$queryString";
	}

	return $url;
}


function ASR ( $app_slug,  $path=null, $rewrite=true ) {
	
	$url = '';
	if ( $rewrite == true ) {
		$url = "/s/$app_slug";
		if ($path != null) { $url .= "$path"; }
	} else {
		$url = "/?n=core-app&c=route&a=s&path=";
		if ($path != null) { $url .= urlencode($path); }
	}

	return $url;
}


function AR( $app_slug, $type="i", $app_c=null, $app_a=null, $query=[], $rewrite=true ) {
	
	$url = '/';
	$params = array();

	if ( $rewrite == true ) {

		$url = "/_a/$type/$app_slug";
		if ($app_c != null) { $url .= "/{$app_c}"; unset($query['app_c']); }
		if ($app_a != null) { $url .= "/{$app_a}"; unset($query['app_a']); }

	} else {

		array_push($params,"n=core-app"); unset($query['n']);
		array_push($params,"c=route"); unset($query['c']);
		array_push($params,"a=$type"); unset($query['a']);

		// 解析组织结构
		$app_slug_arr = explode('/', $app_slug);


		if ( strlen( $app_slug) == 32 ) {
			array_push($params,"app_id=$app_slug"); unset($query['app_id']);
		} else if ( count($app_slug_arr) == 1 ) {
			array_push($params,"app_name=$app_slug"); unset($query['app_name']);
		} else {  // 带有组织结构的地址
			array_push($params,"app_org=$app_slug_arr[0]"); unset($query['app_org']);
			array_push($params,"app_name={$app_slug_arr[1]}"); unset($query['app_name']);
		}

		if ($app_c != null) { array_push($params,"app_c=$app_c"); unset($query['app_c']); }
		if ($app_a != null) { array_push($params,"app_a=$app_a"); unset($query['app_a']); }
	}

	foreach ($query as $key => $value) {
		$value = urlencode($value);
		array_push($params,"$key=$value");
	}
	
	$queryString = implode('&',$params);

	if ( $queryString != "" ) {
		$url = $url . "?$queryString";
	}

	return $url;

}



/**
 * 根据请求来源和要求的返回值，跳转到地址
 * @param [type]  $url          [description]
 * @param boolean $isAjax       [description]
 * @param [type]  $responseType [description]
 */
function TO( $url, $isAjax=false, $responseType=null ) {
	
	if ( !$isAjax ) {
		$url = R('core-dept', 'account', 'login');
	 	header("Location: $url");

	} else {
		if ( $responseType == 'json' )  {
			$e = new Excp( '尚未登录', '403',  [['_FIELD'=>'error','message'=>'请登录后继续操作']]);
			echo $e->error->toJSON(); 
			die();

		} else {
			$url = R('core-dept', 'account', 'login');
			echo "<html><head><meta HTTP-EQUIV=refresh Content='0;url=$url'></head><body></body></htm>";
			die();
		}
	}
}


/**
 * 运行 Controller，并输出结果
 * @param [type] $namespace [description]
 * @param [type] $c         [description]
 * @param [type] $a         [description]
 * @param array  $query     [description]
 */
function RUN( $namespace=null, $c=null, $a=null, $query=array() ) {

	$n =  strtolower( trim($namespace) );
	$c =  strtolower( trim($c) );
	$a =  basename(strtolower( trim($a) ));
	$_GET = $query;
	$_GET['n'] = $n;
	$_GET['c'] = $c;
	$_GET['a'] = $a;

	$class_prefix = $filepath_prefix = '';
	if ( $n !== "" ) {
		$namespace = explode('-', $n);
		$class_prefix = implode('', $namespace);
		$filepath_prefix = implode('/', $namespace) . DS;
	}

	$post_fix = '.class.php';
	$c = urldecode($c);
	$cont_file = AROOT . 'controller'  . DS . $filepath_prefix . $c . $post_fix;

	$class_name = $class_prefix . basename($c) .'Controller' ; 
	if( !file_exists( $cont_file ) )
	{
		$cont_file = CROOT . 'controller' . DS . $c . $post_fix;
		if( !file_exists( $cont_file ) ) die('Can\'t find controller file - ' . $filepath_prefix . $c . $post_fix );
	} 

	require_once( $cont_file );
	if( !class_exists( $class_name ) ) die('Can\'t find class - '  .  $class_name );

	$o = new $class_name;
	if( !method_exists( $o , $a ) ) die('Can\'t find method - '   . $a . ' ');

	return call_user_func( array( $o , $a ) );
}



/**
 * 运行Controller，并返回结果
 * @param [type] $namespace [description]
 * @param [type] $c         [description]
 * @param [type] $a         [description]
 * @param array  $query     [description]
 */
function RRUN( $namespace=null, $c=null, $a=null, $query= [] ) {

	ob_start();
	try {
		$return = RUN($namespace, $c, $a, $query);
	} catch( Exception $e ) {
		throw $e;
	}
	$main_content = ob_get_contents();
	ob_end_clean();
	return $main_content;
}





/**
 * 获取模板路径
 * @param  [type] $name [description]
 * @return [type]       [description]
 */
function tpl( $name ) {
	$name = strtolower($name);
	$file_name = AROOT . "/view/$name.tpl.html";
	return $file_name;
}





function goto_404() {
	//http_response_code(404);
	die('404');

	exit();
}

function goto_503() {
	die('503');
	//http_response_code(503);
	exit();
}

function wprint_r( $data ) {
	echo "<pre>";
	print_r( $data );
	echo "</pre>";
}

function wecho( $data ) {
	echo "<pre>";
	echo $data;
	echo "</pre>";
}


/**
 * Utf-8、gb2312都支持的汉字截取函数
 * cut_str(字符串, 截取长度, 开始长度, 编码);
 * 编码默认为 utf-8
 * 开始长度默认为 0
 */
function cut_str($string, $sublen, $start = 0, $code = 'UTF-8'){
	if($code == 'UTF-8'){
		$pa ="/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
		preg_match_all($pa, $string, $t_string); if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen));
		return join('', array_slice($t_string[0], $start, $sublen));
	}else{
		$start = $start*2;
		$sublen = $sublen*2;
		$strlen = strlen($string);
		$tmpstr = '';
		for($i=0; $i<$strlen; $i++){
			if($i>=$start && $i<($start+$sublen)){
				if(ord(substr($string, $i, 1))>129){
					$tmpstr.= substr($string, $i, 2);
				}else{
					$tmpstr.= substr($string, $i, 1);
				}
			}
			if(ord(substr($string, $i, 1))>129) $i++;
		}
		if(strlen($tmpstr)<$strlen ) $tmpstr.= "";
		return $tmpstr;
	}
}

function truncate($string, $length = 30, $etc = '...', $break_words = false){
	if($length == 0)
		return '';
	$string = html_entity_decode(trim(strip_tags($string)), ENT_QUOTES, 'utf-8');
	for($i = 0, $j = 0; $i < strlen($string); $i++){
		if($j >= $length){
			for($x = 0, $y = 0; $x < strlen($etc); $x++){
				if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
					$x += $number - 1;
					$y++;
				}else{
					$y += 0.5;
				}
			}
			$length -= $y;
			break;
		}
		if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
			$i += $number - 1;
			$j++;
		}else{
			$j += 0.5;
		}
	}
	for($i = 0; (($i < strlen($string)) && ($length > 0)); $i++){
		if($number = strpos(str_pad(decbin(ord(substr($string, $i, 1))), 8, '0', STR_PAD_LEFT), '0')){
			if($length < 1.0){
				break;
			}
			$result .= substr($string, $i, $number);
			$length -= 1.0;
			$i += $number - 1;
		}else{
			$result .= substr($string, $i, 1);
			$length -= 0.5;
		}
	}
	$result = htmlentities($result, ENT_QUOTES, 'utf-8');
	if($i < strlen($string)){
		$result .= $etc;
	}
	return $result;
}

function url($class,$action,$param=''){
	if($class=='') $class = 'default';
	if($action=='') $action = 'index';

	if(C('url_rewrite')=='0'){
		$url = C('root').'index.php?c='.$class.'&a='.$action;
		if($param!='') $url .= '&'.$param;
	}else{
		$url = url_rewrite($class,$action,$param);
	}
	return $url;
}


<?php
 // + XSFDL (XpmJS Simple Form Description Language) + UI 升级  + MINA  + XpmJS 升级
 // + 1.5.6 + 新的 GateWay , Http & Local
 // + 1.6.1 > 更名为 XpmSE 
 // + 1.6.7 > 预发布
 // + 1.6.8 > + 应用商店
 // + 1.6.9 > + TEXT & XML 解析 & 更新菜单
 // + 1.6.10 > + 修复队列BUG & 增加数据库迅捷函数 & CSV 迅捷函数
 // + 1.6.12 > + 代码生成器预览版 & 修复各种BUG
 // + 1.6.13 > + 代码生成器 Alpha 1  + XpmSE T 模板引擎
 // + 1.6.14 > + 代码生成器 Alpha 2  代码生成器功能基本完成
 // + 1.6.20 > + Instant 支持 
 // + 1.6.21 > + 升级数据库
 // + 1.6.22 > + 优化 Instant 支持
 // + 1.7.1 > +  开源 APACHE 2.0 协议
 // + 1.7.2 > +  增加页面管理UI、增加配置管理UI、修复LOGO呈现BUG、升级配置文件格式。
 // + 1.8.1 > +  增加服务管理 & 随容器启动服务 | 容器版本 1.8.1
 // + 1.8.2 > +  升级WebSocket服务 & 信道调试面板 | 容器版本 1.8.1
 // + 1.9.2 > +  +多语言支持 + 页面分层 
 // + 1.9.3 > +  +多语言编译器 +语言包内容源定义 +全局内容源选项

define('__VERSION', '1.9.3');
define('__REVISION', '$Id: 56615529713b45fc2b566210bcce5ef733d8d553 $');
function __GET_VISION() {
    $id = str_replace('$Id: ', '', __REVISION);
    $id = str_replace(' $', '', $id);
    return ["version"=>__VERSION, 'revision'=>$id];
}



function transcribe($aList, $aIsTopLevel = true) 
{
   $gpcList = array();
   $isMagic = get_magic_quotes_gpc();
  
   foreach ($aList as $key => $value) {
       if (is_array($value)) {
           $decodedKey = ($isMagic && !$aIsTopLevel)?stripslashes($key):$key;
           $decodedValue = transcribe($value, false);
       } else {
           $decodedKey = stripslashes($key);
           $decodedValue = ($isMagic)?stripslashes($value):$value;

           //+ 安全过滤
           $decodedValue = filter_security_interceptor( $decodedValue );

       }
       $gpcList[$decodedKey] = $decodedValue;
   }
   return $gpcList;
}


//  $_GLOBALS['_REQ'] = $_REQUEST;
// $_GET = transcribe( $_GET ); 
// $_POST = transcribe( $_POST ); 
// $_REQUEST = transcribe( $_REQUEST );


function filter_security_interceptor( $value ) {

	// 过滤XSS
	return dhtmlspecialchars($value);
}



function htmlDecode( $html ) {

	preg_match_all("/\&lt;([^\&lt;]+)\&gt;/is", $html, $ms);

    $searchs[] = '&lt;';
    $replaces[] = '<';
    $searchs[] = '&gt;';
    $replaces[] = '>';

    if($ms[1]) {
        $allowtags = 'img|a|font|div|table|tbody|caption|tr|td|th|br|p|b|strong|i|u|em|span|ol|ul|li|blockquote|h1|h2|pre|strike|hr';
        $ms[1] = array_unique($ms[1]);
        foreach ($ms[1] as $value) {
            $searchs[] = "&lt;".$value."&gt;";

            $value = str_replace('&amp;', '_uch_tmp_str_', $value);
            $value = dhtmlspecialchars($value);
            $value = str_replace('_uch_tmp_str_', '&amp;', $value);
            $value = str_replace(array('\\','/*'), array('.','/.'), $value);
            $skipkeys = array('onabort','onactivate','onafterprint','onafterupdate','onbeforeactivate','onbeforecopy','onbeforecut','onbeforedeactivate',
                    'onbeforeeditfocus','onbeforepaste','onbeforeprint','onbeforeunload','onbeforeupdate','onblur','onbounce','oncellchange','onchange',
                    'onclick','oncontextmenu','oncontrolselect','oncopy','oncut','ondataavailable','ondatasetchanged','ondatasetcomplete','ondblclick',
                    'ondeactivate','ondrag','ondragend','ondragenter','ondragleave','ondragover','ondragstart','ondrop','onerror','onerrorupdate',
                    'onfilterchange','onfinish','onfocus','onfocusin','onfocusout','onhelp','onkeydown','onkeypress','onkeyup','onlayoutcomplete',
                    'onload','onlosecapture','onmousedown','onmouseenter','onmouseleave','onmousemove','onmouseout','onmouseover','onmouseup','onmousewheel',
                    'onmove','onmoveend','onmovestart','onpaste','onpropertychange','onreadystatechange','onreset','onresize','onresizeend','onresizestart',
                    'onrowenter','onrowexit','onrowsdelete','onrowsinserted','onscroll','onselect','onselectionchange','onselectstart','onstart','onstop',
                    'onsubmit','onunload','javascript','script','eval','behaviour','expression','style');
            $skipstr = implode('|', $skipkeys);
            $value = preg_replace(array("/($skipstr)/i"), '.', $value);
            if(!preg_match("/^[\/|\s]?($allowtags)(\s+|$)/is", $value)) {
                $value = '';
            }
            $replaces[] = empty($value)?'':"<".str_replace('&quot;', '"', $value).">";
        }
    }
    $html = str_replace($searchs, $replaces, $html);
    return $html;
}


function checkhtml($html)
{
    preg_match_all("/\<([^\<]+)\>/is", $html, $ms);
    $searchs[] = '<';
    $replaces[] = '&lt;';
    $searchs[] = '>';
    $replaces[] = '&gt;';

    if($ms[1]) {

        $allowtags = 'img|a|font|div|table|tbody|caption|tr|td|th|br|p|b|strong|i|u|em|span|ol|ul|li|blockquote|h1|h2|pre|strike|hr';
        $ms[1] = array_unique($ms[1]);
        foreach ($ms[1] as $value) {
            $searchs[] = "&lt;".$value."&gt;";
            $value = str_replace('&amp;', '_uch_tmp_str_', $value);


            $value = dhtmlspecialchars($value);
            $value = str_replace('_uch_tmp_str_', '&amp;', $value);


            $value = str_replace(array('\\','/*'), array('.','/.'), $value);
            $skipkeys = array('onabort','onactivate','onafterprint','onafterupdate','onbeforeactivate','onbeforecopy','onbeforecut','onbeforedeactivate',
                    'onbeforeeditfocus','onbeforepaste','onbeforeprint','onbeforeunload','onbeforeupdate','onblur','onbounce','oncellchange','onchange',
                    'onclick','oncontextmenu','oncontrolselect','oncopy','oncut','ondataavailable','ondatasetchanged','ondatasetcomplete','ondblclick',
                    'ondeactivate','ondrag','ondragend','ondragenter','ondragleave','ondragover','ondragstart','ondrop','onerror','onerrorupdate',
                    'onfilterchange','onfinish','onfocus','onfocusin','onfocusout','onhelp','onkeydown','onkeypress','onkeyup','onlayoutcomplete',
                    'onload','onlosecapture','onmousedown','onmouseenter','onmouseleave','onmousemove','onmouseout','onmouseover','onmouseup','onmousewheel',
                    'onmove','onmoveend','onmovestart','onpaste','onpropertychange','onreadystatechange','onreset','onresize','onresizeend','onresizestart',
                    'onrowenter','onrowexit','onrowsdelete','onrowsinserted','onscroll','onselect','onselectionchange','onselectstart','onstart','onstop',
                    'onsubmit','onunload','javascript','script','eval','behaviour','expression','style');
            $skipstr = implode('|', $skipkeys);
            $value = preg_replace(array("/($skipstr)/i"), '.', $value);
            if(!preg_match("/^[\/|\s]?($allowtags)(\s+|$)/is", $value)) {
                $value = '';
            }
            $replaces[] = empty($value)?'':"<".str_replace('&quot;', '"', $value).">";
        }
    }

    $html = str_replace($searchs, $replaces, $html);
    return $html;
}

function dhtmlspecialchars($string, $flags = null)
{
   
    if($flags === null) {
        $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
        if(strpos($string, '&amp;#') !== false) {
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
        }
    } else {
        if(PHP_VERSION < '5.4.0') {
            $string = htmlspecialchars($string, $flags);
        } else {
            if(strtolower(CHARSET) == 'utf-8') {
                $charset = 'UTF-8';
            } else {
                $charset = 'ISO-8859-1';
            }
            $string = htmlspecialchars($string, $flags, $charset);
        }
    }

    return $string;
}



function v( $str )
{
	return isset( $_REQUEST[$str] ) ? $_REQUEST[$str] : false;
}

function z( $str )
{
	return strip_tags( $str );
}

function c( $str )
{
	return isset( $GLOBALS['config'][$str] ) ? $GLOBALS['config'][$str] : false;
}

function g( $str )
{
	return isset( $GLOBALS[$str] ) ? $GLOBALS[$str] : false;	
}

function t( $str )
{
	return trim($str);
}

function u( $str )
{
	return urlencode( $str );
}

// render functiones
function render( $data = NULL , $layout = NULL , $sharp = 'default', $return = false)
{
   
    if ( $return ) ob_start();

	if( $layout == null )
	{
		if( is_ajax_request() )
		{
			$layout = 'ajax';
		}
		elseif( is_mobile_request() )
		{
			$layout = 'mobile';
		}
		else
		{
			$layout = 'web';
		}
	}
	
	$GLOBALS['layout'] = $layout;
	$GLOBALS['sharp'] = $sharp;
	
	$layout_file = AROOT . 'view/' . $layout . '/' . $sharp . '.tpl.html';
     

	if( file_exists( $layout_file ) )
	{
		@extract( $data );
		require( $layout_file );
	}
	else
	{
		$layout_file = CROOT . 'view/' . $layout . '/' . $sharp .  '.tpl.html';
		if( file_exists( $layout_file ) )
		{
			@extract( $data );
			require( $layout_file );
		}	
	}

    if ( $return ) {
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }
}



function ajax_echo( $info )
{
	if( !headers_sent() )
	{
		header("Content-Type:text/html;charset=utf-8");
		header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
	}
	
	echo $info;
}


function info_page( $info , $title = '系统消息' )
{
	if( is_ajax_request() )
		$layout = 'ajax';
	else
		$layout = 'web';
	
	$data['top_title'] = $data['title'] = $title;
	$data['info'] = $info;
	
	render( $data , $layout , 'info' );
	
}

function is_ajax_request()
{
	$headers = apache_request_headers();
	return (isset( $headers['X-Requested-With'] ) && ( $headers['X-Requested-With'] == 'XMLHttpRequest' )) || (isset( $headers['x-requested-with'] ) && ($headers['x-requested-with'] == 'XMLHttpRequest' ));
}

if (!function_exists('apache_request_headers')) 
{ 
	function apache_request_headers()
	{ 
		foreach($_SERVER as $key=>$value)
		{ 
			if (substr($key,0,5)=="HTTP_")
			{ 
				$key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
                    $out[$key]=$value; 
			}
			else
			{ 
				$out[$key]=$value; 
			}
       } 
       
	   return $out; 
   } 
} 

function is_mobile_request()
{

    return false;

    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
 
    $mobile_browser = '0';
 
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;
 
    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
 
    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
 
    if(isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
 
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
                        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
                        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
                        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
                        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
                        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
                        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
                        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
                        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
                        'wapr','webc','winw','winw','xda','xda-'
                        );
 
    if(in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;
 
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;
 
    // Pre-final check to reset everything if the user is on Windows
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        $mobile_browser=0;
 
    // But WP7 is also Windows, with a slightly different characteristic
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        $mobile_browser++;
 
    if($mobile_browser>0)
        return true;
    else
        return false;
}

function uses( $m )
{
	load( 'lib/' . basename($m)  );
}

function load( $file_path ) 
{
    echo "<pre>";
    echo "Load: file_path \n";

	$file = AROOT . $file_path;
	if( file_exists( $file ) )
	{
		//echo $file;
		require( $file );
	
	}
	else
	{
		//echo CROOT . $file_path;
		require( CROOT . $file_path );
	}
	
}

// ===========================================
// load db functions
// ===========================================
if( defined('SAE_APPNAME') ) {
	include_once( CROOT .  'lib/db.sae.function.php' );
} else {
	include_once( CROOT .  'lib/db.function.php' );
}


if (!function_exists('_'))
{
	function _( $string , $data = null )
	{
		if( !isset($GLOBALS['i18n']) )
		{
			$c = c('default_language');
			if( strlen($c) < 1 ) $c = 'zh_cn';
			
			$lang_file = AROOT . 'local' . DS . basename($c) . '.lang.php';
			if( file_exists( $lang_file ) )
			{
				include_once( $lang_file );
				$GLOBALS['i18n'] = $c;
			}
			else
				$GLOBALS['i18n'] = 'zh_cn';
			
			
		}
		
		//print_r( $GLOBALS['language'][$GLOBALS['i18n']] );
		
		if( isset( $GLOBALS['language'][$GLOBALS['i18n']][$string] ) )
			$to = $GLOBALS['language'][$GLOBALS['i18n']][$string];
		else
			$to = $string;
		
		if( $data == null )
			return $to;
		else
		{
			if( !is_array( $data ) ) $data = array( $data );
			return vsprintf( $to , $data );
		}	
			
	}
}
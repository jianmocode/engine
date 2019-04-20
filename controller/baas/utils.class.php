<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'baas/base.class.php' );

use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Endroid\QrCode\QrCode as QrCode;

class baasUtilsController extends baasBaseController {

	private $prefix = '_baas_';
	
	function __construct() {
		parent::__construct();
		$this->prefix = empty($this->data['_prefix']) ? '' : '_baas_' . $this->data['_prefix'] . '_';

		// $this->event = M('Event', [
		// 	'table.prefix' => $this->prefix,
		// 	'wxapp.appid'  => $this->wxconf['wxapp.appid'. $this->cid],
		// 	'wxapp.secret' => $this->wxconf['wxapp.secret'. $this->cid]
		// ]);
	}


	function send() {

		$params = $this->data;
		$loginInfo = empty($_SESSION['_loginInfo']) ? [] : $_SESSION['_loginInfo'];
		if ( !isset( $loginInfo['openid']) )  {
			throw new Excp('请登录后访问', 403, ['params'=>$params] );
		}

		$params['touser'] = $loginInfo['openid'];
		unset($params['_sid']);
		unset($params['_cid']);
		$resp = $this->wxapp->templateMessageSend( $params );
		Utils::out( $resp );
	}

	function pageqr() {
		
		$option = $_REQUEST;
		$width  = !empty($option['width'])? $option['width'] : 430;

		if ( empty($option['path']) ) {
			$_REQUEST['label'] = '路径不正确';
			$_REQUEST['size'] = $width;
			$this->qrcode();
			return;
		}

		$path  =  Utils::unescape($option['path']);
		
		try {
			$resp = $this->wxapp->getQrcode($path, $width);
		} catch( Excp $e) {
			$_REQUEST['label'] = '微信API错误';
			$_REQUEST['size'] = $width;
			$this->qrcode();
			return;
		}

		header("Content-Type: {$resp['type']}");
		echo $resp['body'];

	}



	function qrcode() {

		$code =  !empty($_REQUEST['code']) ? Utils::unescape($_REQUEST['code']) : 'xpmjs.com';
		
		$option = $_REQUEST;
		if ( isset( $option['background']) ) {
			$c = explode(',', $option['background']);
			if ( count($c) == 4) {
				$option['background'] = ['r' => $c[0], 'g' => $c[1], 'b' => $c[2], 'a' => $c[3]];
			}
		}

		if ( isset( $option['foreground']) ) {
			$c = explode(',', $option['foreground']);
			if ( count($c) == 4) {
				$option['foreground'] = ['r' => $c[0], 'g' => $c[1], 'b' => $c[2], 'a' => $c[3]];
			}
		}
		$option['size'] = !empty($option['size']) ? $option['size'] : 300;
		$option['padding'] = !empty($option['padding']) ? $option['padding'] : 10;
		$option['background'] = is_array($option['background']) ? $option['background'] : ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];
		$option['foreground'] = is_array($option['color']) ? $option['color'] : ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
		$option['fontsize'] = !empty($option['fontsize']) ? $option['fontsize'] : 14;
		$option['label'] = isset($option['label']) ? $option['label'] : '扫描二维码';
		$option['font'] = !empty($option['font']) ? $option['font'] : '黑体';

		$font_style = $option['font'];
		$fonts = Utils::fonts();
        $fontsMap = $fonts['names'];

        $fontpath = "";
        if ( !empty($font_style) ) {
            $fontpath = $fontsMap[$font_style];
            if ( empty($fontpath) ) {
                $fontpath = current( $fonts['data']);
            }
        }

		$qr = new QrCode();
		$qr ->setText($code)
		    ->setSize($option['size'])
		    ->setPadding( $option['padding'] )
		    ->setErrorCorrection('high')
		    ->setForegroundColor($option['foreground'])
		    ->setBackgroundColor($option['background']);
		
		if ( file_exists($fontpath['local']) ) {
			$qr->setLabelFontPath($fontpath['local']);
		}

		$qr->setLabel($option['label'])
		    ->setLabelFontSize( $option['fontsize']  )
		    ->setImageType( QrCode::IMAGE_TYPE_PNG);

		// now we can directly output the qrcode
		// echo Utils::seroot() . DS . 'lib' . DS . 'fonts' . DS . $option['font'];

		header('Content-Type: image/png');
		$qr->render();
		// echo Utils::seroot();

	}

}
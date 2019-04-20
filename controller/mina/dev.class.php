<?php
if( !defined('IN') ) die('bad request');
include_once( CROOT . 'controller' . DS . 'core.class.php' );
include_once( AROOT . 'controller' . DS . 'mina/base.class.php' );

use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wxapp as Wxapp;
use \Xpmse\Excp as Excp;
use \Mina\Storage\Local;
use \Mina\Template\Lang;


class minaDevController extends minaBaseController {

	function __construct() {
		parent::__construct();
	}

	// 本地存储器（ 图片上传 ）
	function storage() {	

		$this->auth();
		
		$name = trim($_POST['name']);
        $op = trim($_POST['op']);
        $instance = empty($_POST["instance"]) ? "root" : $_POST["instance"];

		if ( empty($name) ) {
			throw new Excp("数据格式错误(未设置 name 数据)", 400, ['_POST'=>$_POST, "_FILES"=>$_FILES]);	
		}

		if ( !in_array($op, ['upload', 'delete']) ) {
			throw new Excp("数据格式错误(非法 op 数据指令)", 400, ['_POST'=>$_POST, "_FILES"=>$_FILES]);	
		}


		$filecontent = $_FILES['filecontent'];
		if ( empty($filecontent) && $op == "upload" ) {
			throw new Excp("数据格式错误(未设置 filecontent 数据)", 400, ['_POST'=>$_POST, "_FILES"=>$_FILES]);	
		}

		$root = Conf::G("storage/local/bucket/public/root");

		$options = [
			"prefix" => $root,
			"url" => "/static-file",
			"origin" => "/static-file",
			"cache" => [
				"engine" => 'redis',
				"prefix" => '_pagesStorage:',
				"host" => Conf::G("mem/redis/host"),
				"port" => Conf::G("mem/redis/port"),
				"passwd" =>Conf::G("mem/redis/password"),
				"raw" =>28800,  // 数据缓存 8小时
				"info" => 28800   // 信息缓存 8小时
			]
		];

		$stor = new Local( $options );
        $message ="[{$op}]{$instance}::{$name}";
        if ( $op == 'upload' ) {  //上传
            $info = $stor->upload($name, file_get_contents( $filecontent['tmp_name']));
            $info["input"] = $message;
			Utils::out($info);
			return;
		} else if ( $op == 'delete' ) {
            $info = $stor->remove($name);
            $info["input"] = $message;
			Utils::out($info);
			return;
		}


	}

	function compile() {

		$this->auth();

		if ( !isset( $_FILES['file']) ) {
			throw new Excp('没有上传任何文件', 501, ['file'=>$_FILES]);
        }
        
        // 编译单个页面
		if ( !empty($_POST['page']) ) {

			$page = M('Page');
			$zipFile = $_FILES['file']['tmp_name'];
			$project = !empty( $_POST['project']) ? trim($_POST['project']) : "default";
            $pagename = $_POST['page'];
            $instance = empty($_POST["instance"]) ? "root" : $_POST["instance"];
            $priority = !isset($_POST["priority"]) ? 9999 : intval($_POST["priority"]);

            $page->importPage( $zipFile , ['project'=>$project, 'page'=>$pagename, 'priority'=>$priority, 'config'=>$_POST] )
                 ->build($project, $pagename, $instance);
                 
			Utils::out( ["code"=>0, "message"=>"编译成功!", "pagename"=>$pagename, "project"=>$project, "instance"=>$instance]);
			return;
		}

        // 编译所有页面
		if ( $_POST['type'] == 'zip' ) {

			$page = M('Page');
			$zipFile = $_FILES['file']['tmp_name'];
			$project = !empty( $_POST['project']) ? trim($_POST['project']) : "default";
            $domain = !empty( $_POST['domain']) ? trim($_POST['domain']) : null;
            $instance = empty($_POST["instance"]) ? "root" : $_POST["instance"];
            $priority = !isset($_POST["priority"]) ? 9999 : intval($_POST["priority"]);

			try {
                $page->import( $zipFile , ['project'=>$project, 'domain'=>$domain, 'priority'=>$priority, 'config'=>$_POST] )
                     ->build($project, null, $instance);

			} catch ( Exception $e ) {

				Utils::out( [
					"code" =>$e->getCode(), 
					"message" =>$e->getMessage(), 
					"extra" => [
						"file"=>$e->getFile(),
						"line"=>$e->getLine()
					],
					"trace" => $e->getTrace()
				]);
				return;

			}
			Utils::out( ["code"=>0, "message"=>"编译成功!", "project"=>$project, "instance"=>$instance]);
            return;
        
        // 编译语言包
		} else if ($_POST['type'] == 'lang'  ) {
            
            $root = Conf::G("storage/local/bucket/private/root") . "/lang";
            $config = [
                "cache" => [
                    "engine" => 'redis',
                    "prefix" => 'Page:Pages:',
                    "host" => Conf::G("mem/redis/host"),
				    "port" => Conf::G("mem/redis/port"),
				    "passwd" =>Conf::G("mem/redis/password"),
                ]
            ];
            $project = !empty( $_POST['project']) ? trim($_POST['project']) : "default";
            $instance = empty($_POST["instance"]) ? "root" : $_POST["instance"];
            $lang = new Lang($config);
            $zipFile = $_FILES['file']['tmp_name'];
            
            $lang->install("{$instance}", "{$project}", $zipFile, "{$root}");
            $lang->load("{$root}");
            Utils::out( ["code"=>0, "message"=>"编译成功!", "root"=>"{$root}/lang", "project"=>$project, "instance"=>$instance]);
            return;
        }

		throw new Excp('请提交程序压缩包', 400, ['file'=>$_FILES]);
	}

}

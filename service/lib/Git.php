<?php

namespace Xpmse;

require_once( __DIR__ . '/Inc.php');

use \Exception as Exception;
use \Xpmse\Conf;
use \Xpmse\Log;
use \Xpmse\Utils;
use \Mina\Cache\Redis as Cache;


class Git {

    private $conf = [];
 
	/**
	 * Git API 
	 * @param array  $conf   配置信息
	 * @param string $engine 处理引擎，默认为百度AI
	 */
	function __construct( $conf = [] ) {
		$this->conf = $conf;
    }



    /**
     * 拉取代码
     */
    function pull( string $path ) {

        if ( !is_dir($path) ) {
            throw new Excp("路径不存在", 500, ["path"=>$path, "output"=>$output]);
        }

        $git = !empty($this->conf["git"]) ?  $this->conf["git"] : "/usr/bin/git";
        $cmd = "";
        $key = trim($this->conf["key"]);
        if ( !empty($key) ) {
            $keyfile = "/tmp/".md5($repo);
            \file_put_contents($keyfile, "$key");
            $cmd = "GIT_SSH_COMMAND=\"ssh -i $keyfile \"";
        }

        $cmd = "cd {$path} && {$cmd}{$git} pull";
        exec($cmd, $output, $return);
        
        // 清理密钥
        if ( file_exists($keyfile)){
            @unlink($keyfile);
        }
        if ($return !== 0) {
            $output = \implode("", $output);
            throw new Excp("Git命令运行失败\n{$cmd}\n{$output}", 500, ["cmd"=>$cmd, "output"=>$output]);
        }
        return true;
    }


    /**
     * 克隆代码
     */
    function clone ( string $repo, string $path, array $args ) {


        // GIT_SSH_COMMAND="ssh -i ~/.ssh/id_rsa_example" 
        // git clone --single-branch --branch master https://github.com/trheyi/jm-document-test.git  /docs/1001
        // git clone --single-branch --branch v1.0.0 https://github.com/trheyi/jm-document-test.git  /docs/1002

        $git = !empty($this->conf["git"]) ?  $this->conf["git"] : "/usr/bin/git";
        $branch = !empty($args["branch"]) ?  $args["branch"]  :  "master" ;
        if ( !empty($args["tag"]) ){
            $branch = $args["tag"];
        }
        $cmd = "";

        $key = trim($this->conf["key"]);
        if ( !empty($key) ) {
            $keyfile = "/tmp/".md5($repo);
            \file_put_contents($keyfile, "$key");
            $cmd = "GIT_SSH_COMMAND=\"ssh -i $keyfile \"";
        }

        $cmd = "{$cmd}{$git} clone --single-branch --branch {$branch} {$repo} {$path}";
        exec($cmd, $output, $return);
        // 清理密钥
        if ( file_exists($keyfile)){
            @unlink($keyfile);
        }

        if ($return !== 0) {
            $output = \implode("", $output);
            throw new Excp("Git命令运行失败 return=" . var_export($return, true), 500, ["cmd"=>$cmd, "output"=>$output]);
        }
        return true;
    }
    
}

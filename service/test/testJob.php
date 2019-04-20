<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Job 测试... \n\n\t";

class testJob extends PHPUnit_Framework_TestCase {


    function testStart() {
        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $info = $job->start([
            "daemonize" => 1,
            "host" => "127.0.0.1",
            "port" => 0,
            "user" => 0,
            "worker_num" => 1
        ]);
        
        $this->assertTrue( $info["master_pid"] > 0 );
    }


    function testCall(){

        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $tmpdir = sys_get_temp_dir();
        $created_at  = "testCall:". date('Y-m-d H:i:s');
        $log =  "{$tmpdir}/ServiceUnitTest.log";
        $resp = $job->call("test-call", "\\Xpmse\\Job", "forUnitTest", $log,  0, $created_at );

        usleep(200000); // 等待200毫秒
        $json_text = file_get_contents( $log );
        $json_data = json_decode( $json_text, true );
        unlink( $log );

        if ( $json_data === false ) {
            $this->assertTrue(false);
            return;
        }
        
        echo "\n testCall Response:\n";
        Utils::out( $json_data );
        $this->assertTrue( $json_data["created_at"] == $created_at  );
    }


    function testCallAfter(){

        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $tmpdir = sys_get_temp_dir();
        $created_at  = "testCallAfter:". date('Y-m-d H:i:s');
        $log =  "{$tmpdir}/ServiceUnitTest.log";
        $resp = $job->callAfter(5000, "test-call-after", "\\Xpmse\\Job", "forUnitTest", $log,  0, $created_at );

        usleep(5500000); // 等待2200毫秒
        $json_text = file_get_contents( $log );
        if ( $json_text === false ) {
            $this->assertTrue(false);
            return;
        }
        $json_data = json_decode( $json_text, true );
        unlink( $log );

        if ( $json_data === false ) {
            $this->assertTrue(false);
            return;
        }

        echo "\n testCallAfter 5000ms Response:\n";
        Utils::out( $json_data );
        $this->assertTrue( $json_data["created_at"] == $created_at  );
    }


    function testCallAt(){

        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $tmpdir = sys_get_temp_dir();
        $created_at  = "testCallAt:". date('Y-m-d H:i:s');
        $log =  "{$tmpdir}/ServiceUnitTest.log";
        $at = date('Y-m-d H:i:s', strtotime("+3s", time()));
        $resp = $job->callAt($at, "test-call-at", "\\Xpmse\\Job", "forUnitTest", $log,  0, $created_at );

        usleep(5500000); // 等待10500毫秒
        $json_text = file_get_contents( $log );
        if ( $json_text === false ) {
            $this->assertTrue(false);
            return;
        }
        $json_data = json_decode( $json_text, true );
        unlink( $log );

        if ( $json_data === false ) {
            $this->assertTrue(false);
            return;
        }

        
        echo "\n testCallAt {$at} Response:\n";
        Utils::out( $json_data );
        $this->assertTrue( $json_data["created_at"] == $created_at  );
    }


    function testCallTick(){

        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $tmpdir = sys_get_temp_dir();
        $created_at  = "testCallTick:". date('Y-m-d H:i:s');
        $log =  "{$tmpdir}/ServiceUnitTest.log";
        $resp = $job->callTick(5000, "test-call-tick", "\\Xpmse\\Job", "forUnitTest", $log,  0, $created_at );
        usleep(200000); // 等待200毫秒
        for( $i=1; $i<=2; $i++ ) {
            usleep(5200000); // 等待2200毫秒
            $json_text = file_get_contents( $log );
            unlink( $log );

            if ( $json_text === false ) {
                $this->assertTrue(false);
                return;
            }
            $json_data = json_decode( $json_text, true );
            

            if ( $json_data === false ) {
                $this->assertTrue(false);
                return;
            }
            echo "\n testCallTick 5000ms Times={$i} Response:\n";
            Utils::out( $json_data );
        }

        $this->assertTrue( $json_data["created_at"] == $created_at  );
    }


    function testInspect() {
        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $info = $job->inspect();
        $master_pid = $info["setting"]["master_pid"];
        $this->assertTrue( $master_pid > 0 );
    }


    function testReload(){
        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $reponse = $job->reload();
        $this->assertTrue( $reponse  );
    }

    function testRestart(){
        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $info = $job->inspect();
        $master_pid = $info["setting"]["master_pid"];

        $info_new = $job->restart();
        $master_pid_new = $info_new["manager_pid"];
        $this->assertTrue( $master_pid_new > 0  );
        $this->assertTrue( $master_pid_new != $master_pid  );
    }

    function testShutdown() {
        $job = new \Xpmse\Job(["name"=>"ServiceUnitTest"]);
        $reponse = $job->shutdown();
        $this->assertTrue( $reponse  );
    }
	
}
<?php
require_once('lib/Excp.php');
require_once('lib/Stor.php');
require_once('lib/Ding.php');

use \Xpmse\Stor as Stor;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Ding as Ding;

echo "\nXpmse\Ding 测试... \n\n\t";

class testDing extends PHPUnit_Framework_TestCase {

	private $photo = "http://h.hiphotos.baidu.com/image/pic/item/4ec2d5628535e5dd2820232370c6a7efce1b623a.jpg";

	function testGetAccessTokenCrop() {
		$ding = new Ding;
		$resp = $ding->getAccessToken();
		echo "\nCorp TOKEN=$resp\n";
		$this->assertEquals( is_string($resp), true );
	}
	/*
	function testGetAccessTokenSso() {
		$ding = new Ding;
		$resp = $ding->getAccessToken('sso');
		echo "\nSSO  TOKEN=$resp\n";
		$this->assertEquals( is_string($resp), true );
	}


	function testGetAccessTokenFailure() {
		$ding = new Ding;
		$resp = $ding->getAccessToken('sso2');
		$this->assertEquals( is_a($resp, '\Xpmse\Err'), true );
	}

	function testGetDepartmentList(){
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$this->assertEquals( is_array($resp), true );
	}

	function testGetDepartment(){
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$id = end($resp)['id'];
		$info = $ding->getDepartment($id);
		$this->assertEquals( is_array($info), true );
	}

	function testCreateUpdateDeleteDepartment() {
		$ding = new Ding;
		$id = $ding->createDepartment('市场部');
		$this->assertEquals( is_numeric($id), true );

		$upRet = $ding->updateDepartment( $id, [
			'name'=>'市场部2',
			"createDeptGroup" => true
		]);
		$this->assertEquals( $upRet, true );

		$delRet = $ding->deleteDepartment($id);
		$this->assertEquals( $delRet, true );
	}

	function testgetMemberList(){
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$id = end($resp)['id'];
		$info = $ding->getMemberList($id);
		$this->assertEquals( isset(end($info)['isAdmin']), false );
		$this->assertEquals( isset(end($info)['name']), true );
	}

	function testGetMemberListComplete(){
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$id = end($resp)['id'];
		$info = $ding->getMemberList($id, true);
		$this->assertEquals( isset(end($info)['isAdmin']), true );
		$this->assertEquals( isset(end($info)['name']), true );
	}


	function testGetMember() {
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$id = end($resp)['id'];
		$mbs = $ding->getMemberList($id);
		$userid = end($mbs)['userid'];
		$detail = $ding->getMember($userid);

		$this->assertEquals( isset($detail['name']), true );
		$this->assertEquals( isset($detail['isAdmin']), true );
	}


	function testCreateUpdateDeleteMember(){
		$ding = new Ding;
		$resp = $ding->getDepartmentList();
		$id = end($resp)['id'];
		$mbs = $ding->getMemberList($id);
		$userid = end($mbs)['userid'];


		$newUser = $ding->createMember([
				'name'=>'新成员',
				'department'=>[1],
				'mobile'=>'13263191229',
			]);


		$this->assertEquals( is_string($newUser), true );
		$delRet = $ding->deleteMember( $newUser );
		$this->assertEquals( $delRet, true );
		

		$resp = $ding->updateMember($userid, [
			'remark'=>'备注信息',
			'tel' => '100081',
			'extattr'=>['颜色'=>'中国红']
		]);

		$detail = $ding->getMember($userid);
		$this->assertEquals( $detail['remark'], '备注信息' );
		$this->assertEquals( $detail['tel'], '100081' );
		$this->assertEquals( $detail['extattr'], ['颜色'=>'中国红']);

	}

	function testGetUploadMedia() {
		$ding = new Ding;
		$stor = new Stor;
		$resp = $stor->put('local://public::/test/ding.jpg', $this->photo);
		$resp = $stor->resize('local://public::/test/ding.jpg','local://public::/test/ding_200.jpg', 200, 200);
		$this->assertEquals( $resp, true );
		$mid = $ding->uploadMedia('local://public::/test/ding_200.jpg');
		$this->assertEquals( is_string($mid),true );
		$info = $ding->getMedia( $mid );
		$this->assertEquals( strpos($info, 'ttp'),1 );

		$resp = $stor->del('local://public::/test/ding.jpg');
		$resp = $stor->del('local://public::/test/ding_200.jpg');
	} */

	function testCreateMicroapp(){

		$ding = new Ding;
		$stor = new Stor;
		$resp = $stor->put('local://public::/test/microapp.jpg', $this->photo);
		$resp = $stor->crop('local://public::/test/microapp.jpg','local://public::/test/microapp_128.jpg', 128, 128, 260,420);
		$this->assertEquals( $resp, true );
		$icon = $ding->uploadMedia('local://public::/test/microapp_128.jpg');
		$this->assertEquals( is_string($icon),true );
		$resp = $stor->del('local://public::/test/microapp.jpg');
		$resp = $stor->del('local://public::/test/microapp_128.jpg');

		
		$id = $ding->createMicroapp([
			'icon' => $icon,
			'name' => '美图',
			'desc' => '最新美女图片',
			'moble' => 'http://dev.JianMoApp.com/test.php?a=mobile',
			'web' => 'http://dev.JianMoApp.com/test.php?a=web'
		]);


		$this->assertEquals( is_numeric($id),true );

	}


}
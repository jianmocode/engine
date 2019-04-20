<?php
require_once('lib/Excp.php');
require_once('lib/Stor.php');
use \Xpmse\Stor as Stor;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;

echo "\nXpmse\Stor 测试... \n\n\t";

class testStor extends PHPUnit_Framework_TestCase {

	private $photo = "http://h.hiphotos.baidu.com/image/pic/item/4ec2d5628535e5dd2820232370c6a7efce1b623a.jpg";

	function testWrapperParse() {
		$stor = new Stor;
		$w = $stor->wrapperParse('local://public::/test/ok.jpg');
		$this->assertEquals( $w, ['engine'=>'local','bucket'=>'public','file'=>'/test/ok.jpg']);
	}

	function testPutOK() {
		$stor = new Stor;
		$resp = $stor->put('local://public::/test/ok.jpg', $this->photo);
		$this->assertEquals( $resp, true );
	}

	function testGetUrl() {
		$stor = new Stor;
		$resp = $stor->getUrl('local://public::/test/ok.jpg');
		$home = Conf::G("storage/local/bucket/public/home");
		$this->assertEquals( $resp, "$home/test/ok.jpg" );
	}

	function testGetData() {
		$stor = new Stor;
		$data = $stor->getData('local://public::/test/ok.jpg');
		$odata = file_get_contents( $this->photo );
		$this->assertEquals( $data, $odata );
	}


	function testMimetype() {
		$stor = new Stor;
		$resp = $stor->mimetype('local://public::/test/ok.jpg');
		$this->assertEquals( $resp, 'image/jpeg' );
	}


	function testCrop() {
		$stor = new Stor;
		$resp = $stor->crop('local://public::/test/ok.jpg','local://public::/test/ok_crop_200.jpg', 200, 200, 260,420);
		$this->assertEquals( $resp, true);	
	}

	function testFit() {
		$stor = new Stor;
		$resp = $stor->fit('local://public::/test/ok.jpg','local://public::/test/ok_fit.jpg');
		$this->assertEquals( $resp, true);	
	}

	function testResize(){
		$stor = new Stor;
		$resp = $stor->resize('local://public::/test/ok_fit.jpg','local://public::/test/ok_resize_400.jpg', 400, 400);
		$this->assertEquals( $resp, true);	
	}


	function testToMeida() {
		$stor = new Stor;
		$resp = $stor->toMedia('local://public::/test/ok_resize_400.jpg', 'hello');
		$this->assertEquals( $resp['filename'], 'ok_resize_400.jpg');
		$this->assertEquals( $resp['mimetype'], 'image/jpeg');
		$this->assertEquals( $resp['name'], 'hello');
	}


	function testDel() {
		$stor = new Stor;
		$resp = $stor->del('local://public::/test/ok.jpg');
		$this->assertEquals( $resp, true );

		$resp = $stor->del('local://public::/test/ok_200.jpg');
		$resp = $stor->del('local://public::/test/ok_crop_200.jpg');
		$resp = $stor->del('local://public::/test/ok_fit.jpg');
		$resp = $stor->del('local://public::/test/ok_resize_400.jpg');
		
	}


}
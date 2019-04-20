<?php
require_once(__DIR__ . '/../lib/Utils.php');
require_once(__DIR__ . '/../lib/Excp.php');

use \Xpmse\Utils as Utils;
use \Xpmse\Excp;

echo "\nXpmse\Utils 测试... \n\n\t";

class testUtils extends PHPUnit_Framework_TestCase {


	function testJsonToForm() {

		$json_text = file_get_contents(__DIR__ . '/assets/form.json');
		$template_text =  file_get_contents(__DIR__ . '/assets/form.tpl.html');
		$json_struct = json_decode($json_text, true );

		$data = [
			"name_text" => "这是一只懒狗",
			"name_checkbox" => ["1", "2"],
			"name_radio" => "2",
			"name_select" => "1",
			"name_select_mutiple" => ["0","2"],
			"name_textarea" => "这是一个多行文本\n新启一行",
			"name_file" => ["url"=>"http://www.meinv.com/", "path"=>"/some/file/path", "placeholder"=>"文件", "type"=>"pdf"],
			"name_image" => ["url"=>"http://xxx/image.png", "path"=>"/some/image/path"]
		];

		$resp = Utils::JsonToHtml( $json_struct, $data, $template_text );
		echo $resp['html'];
	}


	/*
	function testJSON() {

		$json_text = '{"a":"50", "c":100,"c":500}';
		$json_data = Utils::json_decode( $json_text );
		$this->assertEquals( $json_data['c'],  "500");
		$json_text = '{"a":"50", "c":100,"c":500a3}';
		try {
			$json_data = Utils::json_decode( $json_text );
		} catch( Excp $e ) {
			$extra =  $e->getExtra();
			$this->assertEquals( $extra['details']['text'],  5);
		}
	}

	
	function testRequestHtml() {
		$u = new \Xpmse\Utils;
		$resp = $u->Request('GET', 'http://www.baidu.com', ['datatype'=>'html']);
		$this->assertEquals( is_string($resp),  true);
		$this->assertEquals( empty($resp),  false);
	}

	function testRequestJSON() {
		$u = new \Xpmse\Utils;
		$resp = $u->Request('GET', 'http://dwz.cn/create.php');
		$this->assertEquals( is_array($resp),  true);
		$this->assertEquals( $resp['status'],  -1);
	}

	function testShortUrl() {
		$u = new \Xpmse\Utils;
		$url = $u->ShortUrl('http://www.baidu.com');
		$this->assertEquals( is_string($url),  true);
		$this->assertEquals( $url,  'http://dwz.cn/yes');
	}
 
	function testUrlSort(){
		$ut = new \Xpmse\Utils;
		$resp = [];
		$resp['sa'] = $ut->urlSort('http://dev.JianMoApp.com/?n=core-app&c=route&a=portal&app_name=oscproject&app_id=825d6613bcc196c24c90cbc8638eb6b0&app_c=default&app_a=index');
		print_r($resp);

	}

	function testFaker() {
		$faker = Utils::Faker();
		$data = [
	         'company'=> $faker->company,
	         'name'=> $faker->name,
	         'title' => $faker->jobTitle,
	         'mobile'=> $faker->phoneNumber,
	         'email'=> $faker->email,
	         'address'=> $faker->address,
	         'remark'=> $faker->text(100),
	         'status'=>'active'
    	];
		
		print_r( $data );

	}

	function testSwiftMailer() {

		$message = Utils::MailMessage( "HELLO WORLD" )
                  ->setFrom(['test@diancloud.com' => '测试程序'])
                  ->addPart( "<b> 你好世界 ！！！</b>", 'text/html') // 正文 HTML
                  ->setTo( ["weiping@diancloud.com" => "www"]) ;

		$mailer = Utils::Mailer(['host'=>'smtp.exmail.qq.com','user'=>'test@diancloud.com', 'pass'=>'passworkd','ssl'=>true]);


		$numSent = $mailer->send( $message );

		printf("Sent %d messages\n", $numSent);

	} */

	// function testValidator() {
	// 	$_vc = Utils::vcode();
	// }

	
}
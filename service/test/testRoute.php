<?php
require_once('lib/Route.php');

echo "\nXpmse\Route 测试... \n\n\t";

class testUtils extends PHPUnit_Framework_TestCase {


	// 测试读取配置
	function testConf() {
		$rt = new \Xpmse\Route;
		$conf = $rt->conf();
		$this->assertEquals( is_string($conf['home']),  true);
		$this->assertEquals( empty($conf['home']),  false);
	}

	// 测试增删改查
	function testAddGetRM(){
		$resp = [];
		$rt = new \Xpmse\Route;
		$resp['empty'] = $rt->emptyRoute();
		$resp['add'] = $rt->add('/work', '/?a=defaults&action=work');
		$resp['get'] = $rt->get('/work');
		$resp['rm'] = $rt->rm('/work');
		$this->assertEquals( $resp['add'],  true);
		$this->assertEquals( $resp['get'], '/?a=defaults&action=work');
		$this->assertEquals( $resp['rm'],  true);		
	}


	// 测试域名增删改查
	function testAddGetRMDomain() {
		$resp = [];
		$rt = new \Xpmse\Route;
		$resp['add'] = $rt->addDomain('pt.JianMoApp.com');
		$resp['getall'] = $rt->getDomain();
		$resp['get'] = $rt->getDomain(0);
		$resp['rm'] = $rt->rmDomain('pt.JianMoApp.com');

		$this->assertEquals( $resp['add'],  true);
		$this->assertEquals( $resp['getall'], ['pt.JianMoApp.com']);
		$this->assertEquals( $resp['get'], 'pt.JianMoApp.com');
		$this->assertEquals( $resp['rm'],  true);	
	}

	// 测试规则增删改查
	function testAddGetRMRule() {
		$resp = [];
		$rt = new \Xpmse\Route;
		$rt->emptyRule();
		$resp['add'] = $rt->addRule([
			"re"=>'/id\/([0-9a-z]+)/', 
			'uri'=>'/?n=core-dept&c=account&a=login&id={$1}', 
			'pri'=>1,
			'cache'=>1000
		]);

		$resp['getall'] = $rt->getRule();
		$resp['get'] = $rt->getRule(0);

		$resp['rm'] = $rt->rmRule(0);

		$this->assertEquals( $resp['add'],  true);
		$this->assertEquals( $resp['getall'], [[
			"re"=>'/id\/([0-9a-z]+)/', 
			'uri'=>'/?n=core-dept&c=account&a=login&id={$1}', 
			'pri'=>1,
			'cache'=>1000
		]]);
		$this->assertEquals( $resp['get'], [
			"re"=>'/id\/([0-9a-z]+)/', 
			'uri'=>'/?n=core-dept&c=account&a=login&id={$1}', 
			'pri'=>1,
			'cache'=>1000
		]);
		$this->assertEquals( $resp['rm'],  true);	
	}


	// 测试缓存增删改查
	function testCache() {
		$resp = [];
		$rt = new \Xpmse\Route;
		$resp['empty'] = $rt->emptyCache();
		$resp['put'] = $rt->putCache("/?n=core-dept&c=account&a=login&id=193.html&str=hello", 1000);
		$resp['content'] = $rt->getCache("/?n=core-dept&c=account&a=login&id=193.html&str=hello");
		$resp['rm'] = $rt->rmCache("/?n=core-dept&c=account&a=login&id=193.html&str=hello");
		
		$this->assertEquals( $resp['put'],  true);
		$this->assertEquals( is_string($resp['content']),  true);
		$this->assertEquals( $resp['rm'],  true);
	}

	// 测试parseRule 
	function testParseRule() {
		$resp = [];
		$rt = new \Xpmse\Route;
		$resp['rule'] = $rt->parseRule('/news/([0-9]+).html<sp>  {default,news,[categoryid:$1]}<sp>100<sp>3600', function( $uri ) {
			return 'app_c=' .$uri['c'] . '&app_a=' . $uri['a'] . '&'. str_replace('%24', '$',http_build_query($uri['q']));
		});

		$this->assertEquals( $resp['rule']["re"],  "/news/([0-9]+).html");
		$this->assertEquals( $resp['rule']["uri"],  "app_c=default&app_a=news&categoryid=$1");
		$this->assertEquals( $resp['rule']["pri"],  100);
		$this->assertEquals( $resp['rule']["cache"],  3600);

	}



	function testData(){
		$resp = [];
		$rt = new \Xpmse\Route;
		$resp['add'] = $rt->addDomain('pt.JianMoApp.com');
		$resp['login'] = $rt->add('/login', '/?n=core-dept&c=account&a=login');
		$resp['rule'] = $rt->addRule([
			"re"=>'/id/([0-9]+)', 
			'uri'=>'/?n=core-dept&c=account&a=login&id=$1', 
			'pri'=>1,
			'cache'=>1000
		]);
		$resp['rule'] = $rt->addRule([
			"re"=>'/id/([0-9a-z\.]+)', 
			'uri'=>'/?n=core-dept&c=account&a=login&id=$1&str=hello', 
			'pri'=>2,
			'cache'=>1000
		]);
		$resp['cache'] = $rt->putCache("/?n=core-dept&c=account&a=login&id=193.html&str=hello", 1000);

		print_r($resp);
	}


}
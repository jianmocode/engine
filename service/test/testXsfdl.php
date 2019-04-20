<?php
require_once(__DIR__ . '/../lib/Utils.php');
require_once(__DIR__ . '/../lib/Excp.php');
require_once(__DIR__ . '/../lib/Xsfdl.php');

use \Xpmse\Utils as Utils;
use \Xpmse\Excp;
use \Xpmse\XSFDL;

echo "\nXpmse\Utils 测试... \n\n\t";

class testXSFDL extends PHPUnit_Framework_TestCase {

	function testLoad() {
		return;
		$struct = __DIR__ . '/assets/xsfdl.json';
		$template = __DIR__ . '/assets/xsfdl.tpl.html';

		try {

			$xsf = XSFDL::load( $struct );
			$xsf->loadTemplate( $template )
				->reload(["org_type"=>["name"=>"新名称"]])
				->select(["org_type", "manu_name"])
				// ->render(["org_type"=>2])
				// ->render( ["org_type"=>"2"] )
			;

			$js = $xsf->getValidationJSCode();
			Utils::out( $js );

			$code = $xsf->get();
			foreach ($code as $name => $html) {
				Utils::out( $html );
			}
			
		} catch ( Excp $e ) {
			Utils::out ( $e->toArray() );
		}
	}



	// 检查校验函数
	function testValidate() {

		$struct = __DIR__ . '/assets/xsfdl.json';
		$template = __DIR__ . '/assets/xsfdl.tpl.html';

		$xsf = XSFDL::load( $struct );
		$xsf->loadTemplate( $template )
				->reload(["org_type"=>["name"=>"新名称"]])
				->select(["manu_name", "lp_sex"])
		;

		$xsf->validate(["manu_name"=>""]);

		$this->assertEquals( $xsf->errors["manu_name"],  "厂商名称不能为空");

		$xsf->validate(["org_type"=>"1", "manu_name"=>"哦"]);
		$this->assertEquals( $xsf->errors["manu_name"],  "厂商名称不能少于2个字");

		$xsf->validate(["org_type"=>"1", "manu_name"=>"哦哦哦哦哦哦"]);
		$this->assertEquals( $xsf->errors["manu_name"],  "厂商名称不能超过5个字");

		$xsf->validate(["lp_sex"=>""]);
		$this->assertEquals( $xsf->errors["lp_sex"],  "性别格式不正确");
		
		$xsf->validate(["lp_sex"=>0]);
		$this->assertEquals( $xsf->errors["lp_sex"],  null);
		
	}

}
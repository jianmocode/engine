<?php
require_once(__DIR__ . "/env.inc.php" );

use Mina\Gateway\Base as GW;
use Xpmse\Utils;
use Xpmse\Excp;

class BaseTest extends PHPUnit_Framework_TestCase {
	
	public function testLoad() {

		$_GET['a'] = "10093";
		$_GET['time'] = time();

		$gw = new GW();
		$params = $gw->load("mina/pages", function( $app ) {

			// 读取应用逻辑
			$tab = Utils::getTab('app', 'core_');
			$slug = implode('/', $app);
			$rows = $tab->query()->where('slug', '=', $slug)->limit(1)->get()->toArray();
			if ( empty($rows)) {
				throw new Excp('应用不存在或未安装', 404);
			}
			return current($rows);

		})->init();
	}
}
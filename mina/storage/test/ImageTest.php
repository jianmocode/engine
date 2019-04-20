<?php
require_once(__DIR__ . "/../vendor/autoload.php" );

require_once(__DIR__ . "/../../cache/src/Object.php" );
require_once(__DIR__ . "/../../cache/src/Base.php" );
require_once(__DIR__ . "/../../cache/src/Redis.php" );


require_once(__DIR__ . "/../src/Object.php" );
require_once(__DIR__ . "/../src/Base.php" );
require_once(__DIR__ . "/../src/Local.php" );

use Mina\Storage\Local as Storage;

class ImageTest extends PHPUnit_Framework_TestCase {

	function __construct( $options = [] ) {
		parent::__construct();

		$this->stor = new Storage([
			"prefix" => "/data/stor/public",
			"url" => "//wss.xpmjs.com/static-file",
			"origin" => function( $path, $options ) {
				return "//wss.xpmjs.com/static-file/o" . $path;
			},
			"image" => [
				"driver" => "imagick"
			],
			"cache" => [
				"engine" => 'redis',
				"prefix" => 'LocalTest:',
				"host" => "172.17.0.1",
				"port" => 6379,
				"raw" =>1,
				"info" => 1
			]
		]);


	}

	public function testUpload() {

		$stor = $this->stor;
		if ( !$stor->isExist("/crop/transport.png") ) {
			$stor->upload("/crop/transport.png", file_get_contents(__DIR__.  '/assets/transport.png'));
		}

		if ( !$stor->isExist("/crop/static.jpg") ) {
			$stor->upload("/crop/static.jpg", file_get_contents(__DIR__.  '/assets/static.jpg'));
		}

		if ( !$stor->isExist("/crop/montion.gif") ) {
			$stor->upload("/crop/montion.gif", file_get_contents(__DIR__.  '/assets/montion.gif'));
		}

		if ( !$stor->isExist("/crop/wm.png") ) {
			$stor->upload("/crop/wm.png", file_get_contents(__DIR__.  '/assets/wm.png'));
		}

	}


	public function testCrop() {

		return;

		$stor = $this->stor ;
		
		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_fit.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_fit.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_fit.jpg",
		    "width" => 1286,
		    "height" => 1930
		];

		$tinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/transport_fit.png",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/transport_fit.png",
		    "mime" => "image/png",
		    "path" => "/crop/transport_fit.png",
		    "width" => 900,
		    "height" => 506
		];

		$minfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/montion_fit.gif",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/montion_fit.gif",
		    "mime" => "image/gif",
		    "path" => "/crop/montion_fit.gif",
		    "width" => 2400,
		    "height" => 1800
		];

		$sinfo = $stor->crop("/crop/static.jpg", "/crop/static_fit.jpg", ["ratio"=>2/3, "x"=>800, "y"=>200] );

		$this->assertEquals( $sinfo, $sinfoShoudbe);

		$tinfo = $stor->crop("/crop/transport.png", "/crop/transport_fit.png", ["ratio"=>16/9, "x"=>100, "y"=>20] );

		$this->assertEquals( $tinfo, $tinfoShoudbe);

		$minfo = $stor->crop("/crop/montion.gif", "/crop/montion_fit.gif", ["ratio"=>4/3, "x"=>100, "y"=>20] );

		$this->assertEquals( $minfo, $minfoShoudbe);
	
		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_CROP_500X300.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_CROP_500X300.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_CROP_500X300.jpg",
		    "width" => 500,
		    "height" => 300
		];
		$sinfo = $stor->crop("/crop/static.jpg", "/crop/static_CROP_500X300.jpg", [
			"width"=>500, "height"=>300, "x"=>800, "y"=>200] );
		$this->assertEquals( $sinfo, $sinfoShoudbe);


		$minfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/montion_CROP_500X300.gif",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/montion_CROP_500X300.gif",
		    "mime" => "image/gif",
		    "path" => "/crop/montion_CROP_500X300.gif",
		    "width" => 500,
		    "height" => 300
		];

		$minfo = $stor->crop("/crop/montion.gif", "/crop/montion_CROP_500X300.gif", [
			"x"=>560, "y"=>700, "width"=>500, "height"=>300
		]);

		$this->assertEquals( $minfo, $minfoShoudbe);

	}

	public function testResize() {

		return;

		$stor = $this->stor;
		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_RESIZE_200.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_RESIZE_200.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_RESIZE_200.jpg",
		    "width" => 200,
		    "height" => 110
		];
		$sinfo = $stor->resize("/crop/static.jpg", "/crop/static_RESIZE_200.jpg", ["width"=>200] );
		$this->assertEquals( $sinfo, $sinfoShoudbe);

		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_RESIZE_H100.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_RESIZE_H100.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_RESIZE_H100.jpg",
		    "width" => 180,
		    "height" => 100
		];
		$sinfo = $stor->resize("/crop/static.jpg", "/crop/static_RESIZE_H100.jpg", ["height"=>100] );
		$this->assertEquals( $sinfo, $sinfoShoudbe);

		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_RESIZE_300_200.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_RESIZE_300_200.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_RESIZE_300_200.jpg",
		    "width" => 300,
		    "height" => 200
		];
		$sinfo = $stor->resize("/crop/static.jpg", "/crop/static_RESIZE_300_200.jpg", [ "height"=>200, "width"=>300] );
		$this->assertEquals( $sinfo, $sinfoShoudbe);


		$minfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/montion_RESIZE_300_200.gif",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/montion_RESIZE_300_200.gif",
		    "mime" => "image/gif",
		    "path" => "/crop/montion_RESIZE_300_200.gif",
		    "width" => 300,
		    "height" => 200
		];
		$minfo = $stor->resize("/crop/montion.gif", "/crop/montion_RESIZE_300_200.gif", [ "height"=>200, "width"=>300] );
		$this->assertEquals( $minfo, $minfoShoudbe);


	}


	public function testWaterMark() {

		$stor = $this->stor;

		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_WMIMG_RAND.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_WMIMG_RAND.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_WMIMG_RAND.jpg",
		    "width" => 3840,
		    "height" => 2130,
		    "watermark" => [
		    	"width" => 115,
		    	"height" => 137,
		    	"position" => "top-left",
		    	"alpha" => 30
		    ]
		];

		$sinfo = $stor->watermark("/crop/static.jpg", "/crop/static_WMIMG_RAND.jpg", [
				"image" => "/crop/wm.png",
				"position"=>"rand"
			]);

		$sinfoShoudbe['watermark']['x'] = $sinfo['watermark']['x'];
		$sinfoShoudbe['watermark']['y'] = $sinfo['watermark']['y'];
		$sinfoShoudbe['watermark']['angle'] = $sinfo['watermark']['angle'];

		$this->assertEquals( $sinfo, $sinfoShoudbe);


		$minfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/montion_WMIMG_RAND.gif",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/montion_WMIMG_RAND.gif",
		    "mime" => "image/gif",
		    "path" => "/crop/montion_WMIMG_RAND.gif",
		    "width" => 300,
		    "height" => 200,
		    "watermark" => [
		    	"width" => 115,
		    	"height" => 137,
		    	"position" => "top-left",
		    	"alpha" => 30
		    ]
		];

		$minfo = $stor->watermark("/crop/montion_RESIZE_300_200.gif", "/crop/montion_WMIMG_RAND.gif", [ 
			"image" => "/crop/wm.png",
			"position"=>"rand"
		]);

		$minfoShoudbe['watermark']['x'] = $minfo['watermark']['x'];
		$minfoShoudbe['watermark']['y'] = $minfo['watermark']['y'];
		$minfoShoudbe['watermark']['angle'] = $minfo['watermark']['angle'];

		$this->assertEquals( $minfo, $minfoShoudbe);


		$sinfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/static_WMTXT_RAND.jpg",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/static_WMTXT_RAND.jpg",
		    "mime" => "image/jpeg",
		    "path" => "/crop/static_WMTXT_RAND.jpg",
		    "width" => 3840,
		    "height" => 2130,
		    "watermark" => [
		    	"text" => "北冥有鱼，其名为鲲。鲲之大，一锅炖不下！",
		    	"font" => "/code/mina/storage/test/assets/Lanting.ttf",
		    	"alpha" => 50,
		    	"rgb" => [0,0,0, 0.5],
		    	"color" => "#000000",
		    	"align" => "left",
		    	"valign" => "bottom",
		    	"size" => 72
		    ]
		];

		$sinfo = $stor->watermark("/crop/static.jpg", "/crop/static_WMTXT_RAND.jpg", [
				"text" => "北冥有鱼，其名为鲲。鲲之大，一锅炖不下！",
				"font"=>__DIR__.  '/assets/Lanting.ttf',
				"size" => 72,
				"alpha" => 50,
				"color" => '#000000',
				// "x" => 100,
				// "y" => 100
				"position" => 'rand'
			]);

		$sinfoShoudbe['watermark']['x'] = $sinfo['watermark']['x'];
		$sinfoShoudbe['watermark']['y'] = $sinfo['watermark']['y'];
		$sinfoShoudbe['watermark']['angle'] = $sinfo['watermark']['angle'];

		$this->assertEquals( $sinfo, $sinfoShoudbe);


		$minfoShoudbe = [
			"url" => "//wss.xpmjs.com/static-file/crop/montion_WMTXT_RAND.gif",
		    "origin" => "//wss.xpmjs.com/static-file/o/crop/montion_WMTXT_RAND.gif",
		    "mime" => "image/gif",
		    "path" => "/crop/montion_WMTXT_RAND.gif",
		    "width" => 300,
		    "height" => 200,
		    "watermark" => [
		    	"text" => "北冥有鱼，其名为鲲。鲲之大，一锅炖不下！",
		    	"font" => "/code/mina/storage/test/assets/Lanting.ttf",
		    	"alpha" => 50,
		    	"rgb" => [255,0,0, 0.5],
		    	"color" => "#FF0000",
		    	"align" => "center",
		    	"size" => 14,
		    	"x" => 150,
		    	"y" => 185,
		    	"angle" => 0
		    ]
		];

		$minfo = $stor->watermark("/crop/montion_RESIZE_300_200.gif", "/crop/montion_WMTXT_RAND.gif", [
			"text" => "北冥有鱼，其名为鲲。鲲之大，一锅炖不下！",
			"font"=>__DIR__.  '/assets/Lanting.ttf',
			"size" => 14,
			"alpha" => 50,
			"color" => '#FF0000',
			"x" => 150,
			"y" => 185,
			"align" => "center",
			// "angle" => -20
			// "position" => 'rand'
		]);
		
		$this->assertEquals( $minfo, $minfoShoudbe);
		
	}


}
<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Media;


echo "\nXpmse\Media 测试... \n\n\t";

class testMedia extends PHPUnit_Framework_TestCase {

	function testZip() {

		$media = new Media();

		$f1 = $media->uploadFile( __DIR__ . "/assets/file-1.ttf");
		$i1 = $media->uploadFile(__DIR__ . "/assets/img-1.gif");
		$i2 = $media->uploadFile(__DIR__ . "/assets/img-2.jpg");


		$zip = $media->zip([
			$f1['path'] => "/字体/仿宋体.ttf",
			$i1['path'] => "/图片/动图.gif",
			$i2['path'] => "/图片/静图.jpg",
		]);

		$media->rm( $f1['media_id']);
		$media->rm( $i1['media_id']);
		$media->rm( $i2['media_id']);

		
	}

}
<?php
require_once(__DIR__ . '/env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Content;
// use \Mina\Pages\Api\Article;

echo "\nXpmse\Content 测试... \n\n\t";

class testContent extends PHPUnit_Framework_TestCase {
    

    function testLoadContent() {

        $c = new Content(["title"=>"商业模式画布"]);
        $c->withNLP([
            "appid"=>"11703465",
            "apikey"=>"xqh5yAF8VGUpamnR7wGzCAVm",
            "secretkey"=>"ERSxQOkYQlikRHyFgTHcwmjLSxK7rgtB"
        ], "baidu");

        $file = __DIR__ . "/assets/content/sample.html";
        $source =file_get_contents( $file );
        $html = $c->loadContent( $source )
                  ->html();
        // echo $html; 

        $wxapp = $c->loadContent( $source )
                   ->wxapp();
    
        // print_r($wxapp);  
        
        $app = $c->loadContent( $source )
                  ->app();

        // print_r($app);  

        $images = $c->images();
        $videos = $c->videos();
        $audios = $c->audios();
        $attachments = $c->attachments();

        $summary = $c->summary();
        $title = $c->title();
        $keywords = $c->keywords();


        echo "\n{$title}\n{$summary}\n";
        print_r( $keywords );

    }
}
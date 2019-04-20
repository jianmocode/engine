<?php
require_once(__DIR__ . '/../lib/Excp.php');
require_once(__DIR__ . '/../lib/Stor.php');
require_once(__DIR__ . '/../lib/Utils.php');
require_once(__DIR__ . '/../lib/Wechat.php');
require_once(__DIR__ . '/../lib/Wxapp.php');

use \Xpmse\Stor as Stor;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Wxapp as Wxapp;


echo "\nXpmse\Wechat 测试... \n\n\t";

class testWechat extends PHPUnit_Framework_TestCase {

	private $photo = "http://h.hiphotos.baidu.com/image/pic/item/4ec2d5628535e5dd2820232370c6a7efce1b623a.jpg";


  function testEncodeData() {
      $wxapp = new Wxapp([
        'appid'=> "wx7c841a9e71bca1a1",
        'secret'=> "e0c9c85d5ddcad1ab2c0444acb5cd43c"
      ]);

      $appid = 'wx4f4bc4dec97d474b';
      $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

      $encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                      QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                      9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                      3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                      NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                      evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                      /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                      u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                      /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                      8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                      C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                      6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                      /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                      lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                      oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                      20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                      Db/XcxxmK01EpqOyuxINew==";

      $iv = 'r7BXXKkLb8qrSNn05n0qiA==';

      $resp = $wxapp->decryptData( $encryptedData, $iv, $sessionKey, $appid );
      Utils::out( $resp );


      $DATA = "XDAtkthKzoN9+bu1HDb94Kf6QZqxOpfUbsyw7oiNsB9Il56mPVp9cm9Tqbs/fDxCciX5pDAgGYxv32aWQiJrZI3Efe7d9k4CXzlfvkoIQ6gnqatpqfiapcB/TymHbHV9EECesy7LoUDaoomHDH4xbwFpBx1Hj8pzb6XRPkqDUewsLHUeOrtBuOJTmkS3O7cSQ9jxxtrdd+so6jTRKRbhpSlJhh2aHgLc11W3QHulJe+iBPBp0tHu4X5upW96h3Rz7YsTSQ904QtJAF6gNxZoERO7yJNdxAgnpchLLhg5meMjtZkrJZW4/+ZWgOUpaWae9xbuJMMNUbaInlRzz2GiuJNjz7RbPTYaObFALorJ0HswkmyWH5nMOyhjpyPxrSD179xyRUgY9JCG/o1Z16YwaTFqQ98R2wBIdrORLt8CM69LCxdYZJnkOeQScMtgDJMHFXY55vB1QFzV7Jx6o6bqFEquk9R6aWpcr2C1IaQ6Mlmyz7442r28dduHLGHZF5PvF/xVKYMxiHU6NSYPmJMAIXRFbY1WoAWV/qAInYOXZOoJZDwlJMoa1pq/ZARjhRCoqJB3jPhbcqZ0PGdHKJh93LuJwsxy4oaE4bAQAT6tBq4UUFcGAGCjERN1OMkIgQCLNj2csh+70RxKceytZipf0raxoH2fMNvgW08Nogcw9UAU5BbG9dk6JZTY6kIZAhlg7hO/3dpfa8/TsHFulgA8ZiygVv6pTx3MUyn61UxdQeevVESIQNQk8QLU6IIRkgGFLpG5Lh4Kkvq4RcFipe66FEVosV6hRKzuw+rIbMxCjipEEDKohgS9HMyhiTQBN/0FJuqa00zcXxmow4FPyPARJRkEYb8MMiE60x7dyRkp43q8onBnSBM6cuCW242Gb4L+S4lMXBZRs9CHteOTmB91u6WXS+sxUE7GD+yz7CitwrQ/MgRCkrW6bvzLJWG8qkGFXkRvlXDvAiA3HB+N390UZYjMKfGUcvQnxBMDi+A399Q8hbjYQBWrCmoM1mrErtulREEuZVhKrhMAU1Dy7eHIHskDknjrer+MB/049v3pUDBI0EeVHd5Jr6/3xv3kPi83X7gjHLpzg5vnI0r4TFulSRi8t5z8U/6F0McbincVezvaKXa+BexTNFd14SQPQUZcvFOIXeS2hEsQr8jDA3d3WFp+ESJNzVnBjAUSsUh9D0agJIhiYcyaRaA5O0v2S0LH501bnG9I5jcg0UnX9P4cPnsXKwPLhIwqU8zbSvTfKu0SOc3h0sn/hiva9DeLwiYUvKEaRCi/5gglgjhpfRFJWwVjsRUK0kmGpLn0XmCWGoHu/OvEkgoesWMlkw2byM9b6PcNdEjygWK2JfQhISWPl2WrXweCfU+IZXE9BB5gwZ4ZpV6cooq8VvUsLpREFXEpQav8keSb2Uiyo1Hyica5A9yEa2eZ7+weWwV3hGIZhuAAkMmgohlOLOUqJsVupdJP62HbMAXiAYDH2pjG5xKPDTVSF1zZ845bhH13jkiIQ8loy9pval80Zu5OBJrxG36y";

      // echo "\n\n\n";
      // for ( $i=0; $i<strlen($DATA); $i=$i+32) {
      //   echo substr($DATA, $i, 32 ) . "\n";
      // }
      // echo "\n";


      // $DATA = 'PfuFM6PoHnLm7oTb4b3adTjeOJQ4KGtO54dk2XwfgJ7mlsdXcRfb8EVDAAE5ySNVnRKRwv2eQyirzONzvOAv50122JFknHd32zGA1cMLeoDkoIjuqcvizz1LfPjDdKywlIT0qz6NYkxV9OZN486itH2S0Ea3+v+Kxjw+6t4hYV1PjA1aTUe6H8UsOklki58qBFr3NQNlZRMwGpXMq8Ly0GI3GMri1XGZk8OWvbZeq15HjbmzzkmvpDb/4FRkMPFhND+pluoZd0h+TpKvFYL9R2vYBUNJVHIZyeBdTVeEDtmj932Ulq64RLOPjykv95FD9qtIqZLvkJ8kfBCL1Tr+RVSNsR3Bk2SLuF80ikhX0RyLVxoLv/NI8XQfn2ADaKIaVmkVorvT2Rqne0Z7ku05nB1XdE/IUlMMsrTF9/jIFWVLtS5epXaPbWdnJEIZFKW9ZaleyvDd3T/y/snRq1p9AOD1ilB3c7dqzJBBWHOHgCyVgaZGQ88i3tG7PrKet1V22HHOXPta53jRixeBJ3IeJ61RzgKugaMv7s7YiSMSphsAJ7bRslhsw9qJnim2t9wHnjDiXu7ALs+ljiWT0wfijDmHxhbt7hIjXEXKYcV6oZOvygAiK4jGO02PyJBQIZStWxKk8DDqK/h/zJlKX6lqcfgU8XmQPtnng+A1pf3u0hZEiKL6TN65uMjAoJvP4MwTuEUhvhi0vSpjCNIaMFQm2dNJ1Us6ANPgbtlnsdsypGaiqbDzrCrdS5mnJYSLnMb/XTef44tm/S4lUAhKfLJvHvWxEXKp0B2enTr9JOE5VQAGS7Hthqr7HBQ5OZlZbGyygxeO4Ii1KzIp5tw59YOLoz1yK7VOg9wSFyP+Rz5WpLFORMr3OXjBg5QCwG3zVM0/0NSQ5BHc2+8O+LIEjvtSA0TeZofeMU2c5XqnZpim6IZhXYXoF8TBI+HCGg62nvp7fi7J2jibbjwE4ObQW5BrGANclm+gKJ4ggA76u/BoPWHEVyJTw5vYpilRton8zpsJueN1NE6nxh4LFbttJiIXdgjsv62uwMuVXWXlWW8jhqnScRJYLZ33Goc1AOc+RUoSSm7cGIeK8w5/SQaARx+BaE9/W6/DdDnyXquAAoEv1IvydsbjW65rRgn1WGW4EvtNGJmhTxCLXAWH0Lbd5AcO1ngHeHMotoaPkfmrGhf38SHMwFl2XTOYDdkcH3Myjb8l8hHXt2rQnWcfecJN6qCJ0jqF4F0Hdp+9Ecd42GY/NmvBmbAJxk2qGDZqhPDqljg9uQludOw8qZDLiudF3FFtTRJdPY4SPa+UjCKR+WGYw1DDzqsW97f1IVY2b/m9nc5puXKK9gVnUEOfsTwv1q92Wf2CgiqB8/ilctoffRtyAAFY8XGa9CIhW7bsPIxYZl5DPXOvbGseNyQpCfHeV0F9RZQ15ovN/98mYPVLCodQtJvv91e6SV4Vq4WQMP0y9mLB2EXaRjzhK9/9kQ/SkE1p4FxewOSTxTkuHLdsxY25e5ISZO95PDtsMbUIT39N0hiV';
      // $iv = "pplDdDe6mE/PPSfaUXFGJw==";
      // $ss = "NPW4qFZ9q6lCo/fAC58mVQ==";



    //   try {
    //   $resp = $wxapp->decryptData(
    //       $DATA,
    //       $iv,
    //       $ss,
    //       "wxf47402e6c12bde14"
    //     );
    // } catch( Excp $e ) {

    //     Utils::out( $e->toArray() );
    // }

  }

	function testCardCreate() {
    return;

		$wechat = new Wechat([
			'appid'=> "wx7c841a9e71bca1a1",
			'secret'=> "e0c9c85d5ddcad1ab2c0444acb5cd43c"
		]);


		$data = json_decode('{
  "card": {
      "card_type": "GROUPON",
      "groupon": {
          "base_info": {
              "logo_url":  "http://mmbiz.qpic.cn/mmbiz_png/cnnEa88yibTCnv5WflDWGH7CUXA8ibAMd73TbrtfZzScLFvc87JuoduF1vkxdMPGichibWoTnibxukndq0rMb4w2b8w/0",
              "brand_name": "微信餐厅",
              "code_type": "CODE_TYPE_TEXT",
              "title": "132元双人火锅套餐",
              "color": "Color010",
              "notice": "使用时向服务员出示此券",
              "service_phone": "020-88888888",
              "description": "不可与其他优惠同享\n如需团购券发票，请在消费时向商户提出\n店内均可使用，仅限堂食",
              "date_info": {
                  "type": "DATE_TYPE_FIX_TIME_RANGE",
                  "begin_timestamp": ' .strtotime(date('Y-m-d')). ',
                  "end_timestamp": '. (intval(strtotime(date('Y-m-d'))) + 7*24*3600) .'
              },
              "sku": {
                  "quantity": 500000
              },
              "use_limit":100,
              "get_limit": 3,
              "use_custom_code": false,
              "bind_openid": false,
              "can_share": true,
              "can_give_friend": true,
              "location_id_list": [
                  123,
                  12321,
                  345345
              ],
              "center_title": "顶部居中按钮",
              "center_sub_title": "按钮下方的wording",
              "center_url": "www.qq.com",
              "custom_url_name": "立即使用",
              "custom_url": "http://www.qq.com",
              "custom_url_sub_title": "6个汉字tips",
              "promotion_url_name": "更多优惠",
              "promotion_url": "http://www.qq.com",
              "source": "大众点评"
          },
           "advanced_info": {
               "use_condition": {
                   "accept_category": "鞋类",
                   "reject_category": "阿迪达斯",
                   "can_use_with_other_discount": true
               },
               "abstract": {
                   "abstract": "微信餐厅推出多种新季菜品，期待您的光临",
                   "icon_url_list": [
                       "http://mmbiz.qpic.cn/mmbiz_png/cnnEa88yibTCnv5WflDWGH7CUXA8ibAMd7JNp4zX4qnqZwXWAHQxqkb8hzLXiaa4agt4HrPfxRZusosgerZaEeo3Q/0"
                   ]
               },
               "text_image_list": [
                   {
                       "image_url": "http://mmbiz.qpic.cn/mmbiz_png/cnnEa88yibTCnv5WflDWGH7CUXA8ibAMd7CNDysEogNrpgq75glsfstCx8NkqkXiaQTf4YKQAUVicahiaerbRATKfNw/0",
                       "text": "此菜品精选食材，以独特的烹饪方法，最大程度地刺激食 客的味蕾"
                   },
                   {
                       "image_url": "http://mmbiz.qpic.cn/mmbiz_png/cnnEa88yibTCnv5WflDWGH7CUXA8ibAMd7CNDysEogNrpgq75glsfstCx8NkqkXiaQTf4YKQAUVicahiaerbRATKfNw/0",
                       "text": "此菜品迎合大众口味，老少皆宜，营养均衡"
                   }
               ],
               "time_limit": [
                   {
                       "type": "MONDAY",
                       "begin_hour":0,
                       "end_hour":10,
                       "begin_minute":10,
                       "end_minute":59
                   },
                   {
                       "type": "HOLIDAY"
                   }
               ],
               "business_service": [
                   "BIZ_SERVICE_FREE_WIFI",
                   "BIZ_SERVICE_WITH_PET",
                   "BIZ_SERVICE_FREE_PARK",
                   "BIZ_SERVICE_DELIVER"
               ]
           },
          "deal_detail": "以下锅底2选1（有菌王锅、麻辣锅、大骨锅、番茄锅、清补 凉锅、酸菜鱼锅可选）：\n大锅1份 12元\n小锅2份 16元 "
      }
  }
}', true );

		Utils::out( $data );

		$resp = $wechat->cardCreate( $data );
		Utils::out( $resp );
	}

	// function testGetAccessToken() {
	// 	$wechat = new Wechat;
	// 	$token =  $wechat->getAccessToken();
	// 	echo "\nPublics AccessToken: $token \n";
	// 	$this->assertEquals( is_string($token), true );
	// }

	// function testGetAccessTokenWeb() {
	// 	$wechat = new Wechat('web');
	// 	$token =  $wechat->getAccessToken();
	// 	echo "\nWebsite AccessToken: $token \n";
	// 	$this->assertEquals( is_string($token), true );
	// }


	// function testGetTicket() {
	// 	$wechat = new Wechat('public');
	// 	$ticket = $wechat->getTicket('jsapi');
	// 	echo "\nPublics JSAPI Ticket: $ticket \n";
	// 	$this->assertEquals( is_string($ticket), true );
	// }

	// function testGetSignature(){

	// 	$wechat = new Wechat('public');
	// 	$signature = $wechat->getSignature();
	// 	echo "\nPublics Signature IS: \n";
	// 	print_r( $signature );
	// }


	// function testGetUser() {
	// 	$wechat = new Wechat;
	// 	$openid = 'onHK_jhlPbk_R_wkIppGQdwnbhUk';
	// 	$user = $wechat->getUser($openid);
	// 	$this->assertEquals( $user['openid'], $openid );
	// }


	// function testGetAuthUrl() {
	// 	$wechat = new Wechat('public');
	// 	$url = $wechat->getAuthUrl('http://dev.JianMoApp.com');
	// 	echo "\nURL: ";
	// 	echo $url;
	// 	echo "\n";
	// }

}
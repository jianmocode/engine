<?php
if( !defined('IN') ) die('bad request');
include_once( AROOT . 'controller' . DS . 'private.class.php' );


use \Xpmse\Utils as Utils;
use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Stor;

class baasAdminCardController extends privateController {
	
	function __construct() {
		// 载入默认的
		parent::__construct([],['icon'=>'/static/defaults/images/icons/wechat.svg', 'icontype'=>'img', 'cname'=>'微信公众平台']);

		$this->table = 'sys_card';
		$this->prefix = '_baas_';
		$this->tables = [];

		$this->wxconf = $c = $this->loadconf();
		
		$this->wechat = new Wechat([
			'appid'=> $c['card.appid'],
			'secret'=>$c['card.secret'],
		]);

	}


	function attachment() {


		$name = $_GET['name'];
		$path = "/tmp";
		$file_name = $path . '/' . $name;
		$action = trim($_POST['action']);

		if ( $action == 'upload' ) {  // 上传附件

			if ( $_FILES['file']['error']  != 0 ||  $_FILES['file']['tmp_name'] == "" ) {
				echo json_encode(['errno'=>'100500', 'errmsg'=>'文件上传失败', 'extra'=>['_FILES'=>$_FILES, '_POST'=>$_POST]]);
				return;
			}

			$buffer = file_get_contents($_FILES['file']['tmp_name']);
			$ext = Utils::mimes()->getExtension($_FILES['file']['type']);
			$ext = !empty($ext) ?  $ext : 'jpg';
			$mimetype = Utils::mimes()->getMimeType( $ext );
			$filename = basename( $_FILES['file']['tmp_name'] ) . ".$ext";
			$resp = $this->wechat->uploadImage($buffer, [
				'mimetype'=>$mimetype, 
				'filename'=>$filename
			]);

			// echo json_encode($resp);
			echo json_encode( [
				'url'=>$resp['url'], 
				'path'=>$filename, 
				'type'=>"$ext", 
				'placeholder'=>$name 
			]);

			return;


		} else if ($action == 'delete') { // 删除文件

			echo json_encode(['ret'=>'complete', 'msg'=>'删除成功']);
			return;
		}
		
		// 无效请求
		echo json_encode(['errno'=>'100100', 'errmsg'=>'未知请求']);

	}



	function index() {

		$pg = isset($_GET['page']) ? $_GET['page'] : 1;
		$table = $this->table;

		$this->_crumb('卡券管理', R('baas-admin','card','index', ['table'=>$table]) );
	    $this->_crumb('卡券列表');

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
	    $tab = M('table', $this->table, ['prefix'=>$this->prefix]);
		if ( !$tab->tableExists() ) { // 初始化卡券表
			
			$schema =[

				// 卡券ID
				["name"=>"card_id", "type"=>"string", "option"=>["length"=>128, "unique"=>1], "acl"=>"rw:rw:r" ],

				// 卡券状态信息				
				["name"=>"card_status", "type"=>"string", "option"=>["length"=>32, 'default'=>'CARD_STATUS_NOT_VERIFY', 'index'=>1], "acl"=>"rw:rw:r" ],

				// 卡券类型 GROUPON 团购;  CASH 代金券; DISCOUNT 折扣券;  GIFT 兑换券; GENERAL_COUPON	 优惠券;
				["name"=>"card_type", "type"=>"string", "option"=>["length"=>32, 'index'=>1], "acl"=>"rw:rw:r" ],

				// 卡券名称
				["name"=>"cname",  "type"=>"string", "option"=>["length"=>64], "acl"=>"rw:rw:r" ],
				

				// 核销权限 ( :self 登录用户核销自己的, group:用户组名称 用户组拥有核销权限, user:用户ID 用户拥有核销权限; 多个用 "," 分割 )
				["name"=>"consume_policy", "type"=>"string", "option"=>["length"=>200, "default"=>":self"], "acl"=>"-:-:-" ],
			

				// 团购券专用，团购详情。
				["name"=>"deal_detail", "type"=>"text", "option"=>[], "acl"=>"rw:rw:r" ],

				// 代金券专用，表示起用金额（单位为分）,如果无起用门槛则填0。
				["name"=>"least_cost", "type"=>"integer", "option"=>["length"=>10, "default"=>0], "acl"=>"rw:rw:r" ],

				// 代金券专用，表示减免金额。（单位为分）
				["name"=>"reduce_cost", "type"=>"integer", "option"=>["length"=>10, "default"=>0], "acl"=>"rw:rw:r" ],

				// 折扣券专用，表示打折额度（百分比）。填30就是七折。
				["name"=>"discount", "type"=>"integer", "option"=>["length"=>5, "default"=>0], "acl"=>"rw:rw:r" ],

				// 兑换券专用，填写兑换内容的名称。
				["name"=>"gift", "type"=>"text", "option"=>[], "acl"=>"rw:rw:r" ],

				// 优惠券专用，填写优惠详情。
				["name"=>"default_detail", "type"=>"text", "option"=>[], "acl"=>"rw:rw:r" ],
				
				// 卡券基本信息数据，所有卡券通用。
				["name"=>"base_info",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 卡券高级信息数据
				["name"=>"advanced_info",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				


				/*
				// 卡券的商户 logo 地址，建议像素为300*300。
				["name"=>"logo_url", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],

				// 码型： 
				// "CODE_TYPE_TEXT"文本； 
				// "CODE_TYPE_BARCODE"一维码; 
				// "CODE_TYPE_QRCODE"二维码
				// "CODE_TYPE_ONLY_QRCODE",二维码无code显示；
				// "CODE_TYPE_ONLY_BARCODE",一维码无code显示；
				// "CODE_TYPE_NONE"，不显示code和条形码类型;
				["name"=>"code_type", "type"=>"string", "option"=>["length"=>32], "acl"=>"rw:rw:r" ],

				// 商户名字,字数上限为12个汉字。
				["name"=>"brand_name", "type"=>"string", "option"=>["length"=>36], "acl"=>"rw:rw:r" ],

				// 卡券名，字数上限为9个汉字。(建议涵盖卡券属性、服务及金额)。
				["name"=>"title", "type"=>"string", "option"=>["length"=>27], "acl"=>"rw:rw:r" ],

				// 券颜色。按色彩规范标注填写Color010-Color100。
				["name"=>"color", "type"=>"string", "option"=>["length"=>16], "acl"=>"rw:rw:r" ],						
				// 卡券使用提醒，字数上限为16个汉字。
				["name"=>"notice", "type"=>"string", "option"=>["length"=>48], "acl"=>"rw:rw:r" ],

				// 卡券使用说明，字数上限为1024个汉字。
				["name"=>"description", "type"=>"text", "option"=>["length"=>3072], "acl"=>"rw:rw:r" ],

				// 商品信息
				["name"=>"sku",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 卡券库存的数量，上限为100000000。
				["name"=>"quantity", "type"=>"integer", "option"=>["length"=>10], "acl"=>"rw:rw:r" ],

				// 使用日期，有效期的信息。
				["name"=>"date_info",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 使用时间的类型，旧文档采用的1和2依然生效。
				// DATE_TYPE_FIX_TIME_RANGE  表示固定日期区间，
				// DATE_TYPE_FIX_TERM 表示固定时长 （自领取后按天算。
				["name"=>"type", "type"=>"string", "option"=>["length"=>32], "acl"=>"rw:rw:r" ],


				// type为DATE_TYPE_FIX_TIME_RANGE时专用，表示起用时间。从1970年1月1日00:00:00至起用时间的秒数，最终需转换为字符串形态传入。（东八区时间,UTC+8，单位为秒）
				["name"=>"begin_timestamp", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],

				// 表示结束时间，建议设置为截止日期的23:59:59过期。（东八区时间,UTC+8，单位为秒）
				["name"=>"end_timestamp", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],

				// type为DATE_TYPE_FIX_TERM时专用，表示自领取后多少天内有效，不支持填写0。
				["name"=>"fixed_term", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],				
				// type为DATE_TYPE_FIX_TERM时专用，表示自领取后多少天开始生效，领取后当天生效填写0。（单位为天）
				["name"=>"fixed_begin_term", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],

				// 可用于DATE_TYPE_FIX_TERM时间类型，表示卡券统一过期时间，建议设置为截止日期的23:59:59过期。（东八区时间,UTC+8，单位为秒），设置了fixed_term卡券，当时间达到end_timestamp时卡券统一过期
				// ["name"=>"end_timestamp", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],


				// 是否自定义Code码。填写true或false，默认为false。通常自有优惠码系统的开发者选择, 自定义Code码，并在卡券投放时带入Code码，详情见是否自定义Code码。
				["name"=>"use_custom_code", "type"=>"boolean", "option"=>["default"=>false], "acl"=>"rw:rw:r"],

				// 填入 GET_CUSTOM_CODE_MODE_DEPOSIT 表示该卡券为预存code模式卡券， 须导入超过库存数目的自定义code后方可投放，填入该字段后，quantity字段须为0,须导入code 后再增加库存
				["name"=>"get_custom_code_mode", "type"=>"string", "option"=>["length"=>32], "acl"=>"rw:rw:r" ],

				// 是否指定用户领取，默认为false。通常指定特殊用户群体, 投放卡券或防止刷券时选择指定用户领取。
				["name"=>"bind_openid", "type"=>"boolean", "option"=>["default"=>false], "acl"=>"rw:rw:r"],

				// 客服电话。
				["name"=>"service_phone", "type"=>"string", "option"=>["length"=>24], "acl"=>"rw:rw:r" ],

				// 门店位置poiid。调用POI门店管理接口获取门店位置poiid。具备线下门店的商户为必填。
				["name"=>"location_id_list",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 设置本卡券支持全部门店，与location_id_list互斥
				["name"=>"use_all_locations", "type"=>"boolean", "option"=>["default"=>true], "acl"=>"rw:rw:r"],

				// 第三方来源名，例如同程旅游、大众点评。
				["name"=>"source", "type"=>"string", "option"=>["length"=>36], "acl"=>"rw:rw:r" ],

				// 自定义跳转外链的入口名字。详情见活用自定义入口
				["name"=>"custom_url_name", "type"=>"string", "option"=>["length"=>15], "acl"=>"rw:rw:r" ],

				// 卡券顶部居中的按钮，仅在卡券状态正常(可以核销)时显示
				["name"=>"center_title", "type"=>"string", "option"=>["length"=>18], "acl"=>"rw:rw:r" ],				
				// 显示在入口下方的提示语，仅在卡券状态正常(可以核销)时显示。
				["name"=>"center_sub_title", "type"=>"string", "option"=>["length"=>24], "acl"=>"rw:rw:r" ],

				// 顶部居中的url，仅在卡券状态正常(可以核销)时显示。
				["name"=>"center_url", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],	

				// 自定义跳转的URL
				["name"=>"custom_url", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],	

				// 显示在入口右侧的提示语。
				["name"=>"custom_url_sub_title", "type"=>"string", "option"=>["length"=>18], "acl"=>"rw:rw:r" ],

				// 营销场景的自定义入口名称。
				["name"=>"promotion_url_name", "type"=>"string", "option"=>["length"=>15], "acl"=>"rw:rw:r" ],

				// 入口跳转外链的地址链接。
				["name"=>"promotion_url", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],	

				// 显示在营销入口右侧的提示语。
				["name"=>"promotion_url_sub_title", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],	

				// 每人可领券的数量限制,不填写默认为50。
				["name"=>"get_limit", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],

				// 每人可核销的数量限制,不填写默认为50。
				["name"=>"use_limit", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],				
				// 卡券领取页面是否可分享。
				["name"=>"can_share", "type"=>"boolean", "option"=>["default"=>false], "acl"=>"rw:rw:r"],

				// 卡券是否可转赠。
				["name"=>"can_give_friend", "type"=>"boolean", "option"=>["default"=>false], "acl"=>"rw:rw:r"],

				// === Advanced_info（卡券高级信息）字段 =========================

				// 创建优惠券特有的高级字段
				["name"=>"advanced_info",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 使用门槛（条件）字段，若不填写使用条件则在券面拼写：无最低消费限制，全场通用，不限品类；并在使用说明显示：可与其他优惠共享
				["name"=>"use_condition",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],	

				// 指定可用的商品类目，仅用于代金券类型，填入后将在券面拼写适用于xxx
				["name"=>"accept_category", "type"=>"string", "option"=>["length"=>512], "acl"=>"rw:rw:r" ],	
				// 指定不可用的商品类目，仅用于代金券类型，填入后将在券面拼写不适用于xxxx
				["name"=>"reject_category", "type"=>"string", "option"=>["length"=>512], "acl"=>"rw:rw:r" ],

				// 满减门槛字段，可用于兑换券和代金券，填入后将在全面拼写消费满xx元可用。
				["name"=>"least_cost", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],	

				// 购买xx可用类型门槛，仅用于兑换，填入后自动拼写购买xxx可用。
				["name"=>"object_use_for", "type"=>"string", "option"=>["length"=>512], "acl"=>"rw:rw:r" ],


				// 不可以与其他类型共享门槛，填写false时系统将在使用须知里拼写“不可与其他优惠共享”，填写true时系统将在使用须知里拼写“可与其他优惠共享”，默认为true
				["name"=>"can_use_with_other_discount", "type"=>"boolean", "option"=>["default"=>true], "acl"=>"rw:rw:r"],

				// 封面摘要结构体名称
				["name"=>"abstract",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],	

				// 封面图片列表，仅支持填入一个封面图片链接，上传图片接口上传获取图片获得链接，填写非CDN链接会报错，并在此填入。建议图片尺寸像素850*350
				["name"=>"icon_url_list", "type"=>"string", "option"=>["length"=>128], "acl"=>"rw:rw:r" ],

				// 图文列表，显示在详情内页，优惠券券开发者须至少传入一组图文列表
				["name"=>"image_url",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 图文描述
				["name"=>"text", "type"=>"string", "option"=>["length"=>512], "acl"=>"rw:rw:r" ],

				// 商家服务类型：BIZ_SERVICE_DELIVER 外卖服务；BIZ_SERVICE_FREE_PARK 停车位；BIZ_SERVICE_WITH_PET 可带宠物；BIZ_SERVICE_FREE_WIFI 免费wifi，可多选
				["name"=>"business_service",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],

				// 使用时段限制，包含以下字段
				// 限制类型枚举值：支持填入
				// MONDAY 周一 
				// TUESDAY 周二 
				// WEDNESDAY 周三
				//  THURSDAY 周四 
				// FRIDAY 周五 
				// SATURDAY 周六 
				// SUNDAY 周日 
				// 此处只控制显示，
				// 不控制实际使用逻辑，不填默认不显示
				["name"=>"time_limit",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:rw:r"  ],
				["name"=>"type", "type"=>"string", "option"=>["length"=>24], "acl"=>"rw:rw:r" ],

				// 当前type类型下的起始时间（小时），如当前结构体内填写了MONDAY，此处填写了10，则此处表示周一 10:00可用
				["name"=>"begin_hour", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],	

				// 当前type类型下的起始时间（分钟），如当前结构体内填写了MONDAY，begin_hour填写10，此处填写了59，则此处表示周一 10:59可用
				["name"=>"begin_minute", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],	

				// 当前type类型下的结束时间（小时），如当前结构体内填写了MONDAY，此处填写了20，则此处表示周一 10:00-20:00可用
				["name"=>"end_hour", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],

				// 当前type类型下的结束时间（分钟），如当前结构体内填写了MONDAY，begin_hour填写10，此处填写了59，则此处表示周一 10:59-00:59可用
				["name"=>"end_minute", "type"=>"integer", "option"=>["length"=>11], "acl"=>"rw:rw:r"],			
				// 
				//  注意事项：
				// 	1.高级字段为商户额外展示信息字段，非必填,但是填入某些结构体后，须填充完整方可显示：如填入text_image_list结构体
				// 	时，须同时传入image_url和text，否则也会报错；
				// 	2.填入时间限制字段（time_limit）,只控制显示，不控制实际使用逻辑，不填默认不显示
				// 	3.创建卡券时，开发者填入的时间戳须注意时间戳溢出时间，设置的时间戳须早于2038年1月19日
				// 	4.预存code模式的卡券须设置quantity为0，导入code后方可增加库存
				 
				*/

				["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
				["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
			];

			$resp = $tab->__schema( $schema );

		}


	    $rs = $tab->query()
	    			->paginate(
	    				20, ["*"], '', $pg )
	    			->toArray();

	    $pages = array();		
		for ($i=1; $i<=$rs["last_page"] ; $i++) { 
			$pages[$i] = $i;
		}
	   	
		$data = [
			'tabs' =>$this->tables,
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'page'=>$pages,
			'rs' =>  $rs['data'],
			'cur'=>$rs["current_page"],
			'pre'=>$rs["prev_page_url"],
			'next'=>$rs["next_page_url"],
			'total'=>$rs['total'],
			'table_columns' => $this->getColumns($tab, true),
			'columns' => $this->getColumns($tab),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 6,
			'_page'=>'admin/card/search.index'
		];

		$data = $this->_data( $data , '卡券管理');
		render($data, 'baas', 'main');
	}





	/**
	 * 修改
	 * @return [type] [description]
	 */
	function panel() {

		$table = $this->table;
		$id = $_GET['_id'];
		$type = $_GET['type'];

		$tab = M('table', $table, ['prefix'=>$this->prefix]);
	    $rs = $tab->getLine('WHERE _id=?',['*'], [$id] );

	   
		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'rs'=>$rs,
			"id" => $id,
			'type'=> $type
		];
		$data = $this->_data( $data , '用户管理');
		render($data, 'baas/admin/card', 'panel.index');
	}

	function read() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
	    $rs = $tab->getLine('WHERE _id=?',['*'], [$id] );

		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  $rs,
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '证书管理');
		render($data, 'baas/admin/card', 'panel.read');

	}


	function modify() {

		$table = $this->table;
		$id = $_GET['id'];
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);
	    $rs = $tab->getLine('WHERE _id=?',['*'], [$id] );

		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  $rs,
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '用户管理');
		render($data, 'baas/admin/card', 'panel.modify');

	}


	function create() {

		$table = $this->table;
		$type = $_GET['type'];

	    $tab = M('table', $table, ['prefix'=>$this->prefix]);

		$data = [
			'table' => [
				'name'=>$table,
				'cname'=>$table,
			],
			'type'=> $type,
			'rs' =>  [],
			'table_columns' => $this->getColumns( $tab, true ),
			'columns' =>  $this->getColumns( $tab ),
			'cmaps' => $this->getColumnsMap($tab, $this->getColumns($tab, true) ),
			'maxcol' => 8,
		];

		$data = $this->_data( $data , '证书管理');
		render($data, 'baas/admin/card', 'panel.modify');

	}



	function save() {

		$table = $this->table;
		$m =  M('table', $table, ['prefix'=>$this->prefix]);
		$_id = $_POST['_id'];
		foreach ($_POST as $key => $value) {
			if ( strpos($value,'__JSON_TEXT__|') === 0 ) {
				$value = str_replace('__JSON_TEXT__|', '', $value);
				$_POST[$key] = json_decode($value, true);
			}
		}

		unset($_POST['name']);

		if ( isset($_POST['upload_cert_path']) ) {

			if ( file_exists($_POST['upload_cert_path']) ) {
				$_POST['path'] = str_replace("/tmp", Utils::certpath(),  $_POST['upload_cert_path']);
				$content = file_get_contents( $_POST['upload_cert_path']);
				file_put_contents($_POST['path'], $content );
			}

		} else {
			unset( $_POST['path'] );
		}

		if (!empty($_id)) {

			// 修改卡券接口
			// $card_data = $this->filterCard( $_POST );
			// $card_update = $card_data['card'];
			// $card_update['card_id'] = $_POST['card_id'];
			// $type = strtolower($card_update['card_type']);
	
			// $card_update[$type]['base_info'] = $this->wechat->cardUpdateFiliter($card_update[$type]['base_info']);

			// unset($card_update['card_type']);
			// unset($card_update[$type]['advanced_info']);

			// $resp = $this->wechat->cardUpdate( $card_update );
			// if( $resp['errcode'] != 0 ) {
			// 	$e = new Excp( "微信卡券接口返回结果异常", 500, ["resp"=> $resp, 'card_data'=>$card_update] );
			// 	echo $e->tojson();
			// 	return;
			// }


			try {
				$data = $m->update( $_id,[
					'consume_policy' => $_POST['consume_policy'],
					'cname'=>$_POST['cname'], 
					'_id'=>$_POST['_id']] );
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}else{

			// 创建卡券接口 
			$card_data = $this->filterCard( $_POST );
			$resp = $this->wechat->cardCreate( $card_data );
			if( $resp['errcode'] != 0 ) {
				$e = new Excp( "微信卡券接口返回结果异常", 500, ["resp"=> $resp, 'card_data'=>$card_data] );
				echo $e->tojson();
				return;
			}
			$_POST['card_id'] = $resp['card_id'];

			try {
				$data = $m->create($_POST);
			} catch (Excp $e) {
				echo $e->tojson();
				return;
			}
		}

		echo json_encode(["code"=>0, "data"=>$data]);
	}

	function cardsync() {
		$this->cardSyncFrom();
	}



	private function cardSyncFrom( $offset = 0 ) {

		$resp = $this->wechat->cardBatchget([
			"offset" => 0,
			"count" => 10,
			"status_list" =>["CARD_STATUS_NOT_VERIFY", "CARD_STATUS_VERIFY_OK"]
		]);

		$card_list = [];
		if ( $resp['errcode'] != 0 ) {
			$e = new Excp( "微信卡券接口返回结果异常", 500, ["resp"=> $resp] );
			echo $e->tojson();
			return;
		}

		$table = $this->table;
		$m =  M('table', $table, ['prefix'=>$this->prefix]);

		foreach ($resp['card_id_list'] as $card_id) {
			$card_resp = $this->wechat->cardGet($card_id);
			if ( $card_resp['errcode'] == 0  ) {

				
				$card_type = $card_resp['card']['card_type'];

				$card = [
					"card_id" => $card_id,
					"card_type" =>$card_type
				];

				$card = array_merge($card,  $card_resp['card'][strtolower($card_type)]);
				$card['card_status'] = $card['base_info']["status"];
				array_push( $card_list,  $card);
			}
		}

		$resp = ['update'=>0, 'create'=>0];
		foreach ($card_list as $card ) {
			try {
				$m->create( $card );
				$resp['create']++;
			}catch(Excp $e) {
				if ( $e->getCode() == 1062 ) {
					$m->updateBy('card_id', $card);
					$resp['update']++;
				}
			}
		}

		echo json_encode(["code"=>0, "data"=>$resp]);
	}


	/**
	 * 拼接
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function filterCard( $data ) {

		$card_type = strtoupper($data['card_type']);
		$resp = [
			"card"=>[
				"card_type" => $card_type,
				strtolower($card_type) => []
			]];

		switch ($card_type) {
			
			case 'GROUPON':
				
				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"deal_detail" => $data['deal_detail']
				];

				break;

			case 'CASH':

				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"least_cost" => intval($data['least_cost']),
					"reduce_cost" => intval($data['reduce_cost'])
				];

				break;

			case 'DISCOUNT':

				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"discount" => intval($data['discount'])
				];

				break;

			case 'GIFT':

				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"gift" => $data['gift']
				];

				break;

			case 'GENERAL_COUPON':

				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"default_detail" => $data['default_detail']
				];
				
				break;
			
			default:
				$resp['card'][strtolower($card_type)] = [
					"base_info" => $data['base_info'],
					"advanced_info" => $data['advanced_info'],
					"default_detail" => $data['default_detail']
				];

				break;
		}

		return $resp;
	}



	/**
	 * 删除
	 * @return [type] [description]
	 */
	function remove() {

		$table = $this->table;
		$m =  M('table', $table, ['prefix'=>$this->prefix]);
		$id = $_POST['id'];
		
		try {
			$data = $m->delete( $id  );
		} catch (Excp $e) {
			echo $e->tojson();
			return;
		}

		echo json_encode(["code"=>0, "data"=>$data]);

	}



	private function getColumns( $tab, $full = false ) {
		$columns = $tab->getColumns();
	    $table_filter = ['_id','_acl','created_at', 'updated_at', 'deleted_at','_user', '_group'];
	    if ( $full === false ) {
		    foreach ($columns as $idx=>$value ) {
		    	if ( in_array($value, $table_filter) ) {
		    		unset( $columns[$idx] );
		    	}
		    }
	    }
	    return $columns;
	}

	private function getColumnsMap( $tab, $columns ) {
		$map = [];
		foreach ($columns as $col) {
			$map[$col] = $tab->getColumn( $col );
		}

		return $map;
	}


	private function loadconf() {

		$mem = new Mem;
		$cmap = $mem->getJSON("BaaS:CONF");

		if ( $cmap == false  || $cmap == null) {

			$tab = M('table', 'sys_conf', ['prefix'=>'_baas_']);
			$cmap = [];
			$config = $tab->select("", ["name","value"] );


			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['value'];
			}


			$tab = M('table', 'sys_cert', ['prefix'=>'_baas_']);
			$config = $tab->select("", ["name","path"] );

			foreach ($config['data'] as $row ) {
				$cmap[$row['name']] = $row['path'];
			}

			$mem->setJSON("BaaS:CONF", $cmap );

		}

		return $cmap;

	}


}
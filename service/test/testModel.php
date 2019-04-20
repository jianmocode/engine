<?php
require_once(__DIR__ . '/env.php');


use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;

$driver = getenv("driver");
if ( empty($driver) ) {
    $driver = "database";
}

$driver = ucfirst(strtolower($driver));

echo "\nXpmse\Model DataDriver: {$driver} 测试... \n\n\t";

class testModelDatabase extends PHPUnit_Framework_TestCase {

	// 测试删除数据表、添加修改删除替换字段
	function testDropTableAddAlterPutDropColumn() {
        
        global $driver;
        $md = new Model([], $driver);
        
		$md->table('unit_test_helloworld')
		   ->dropTable()
		   ->addColumn('id', $md->type('bigInteger') )
		   ->addColumn('local',$md->type('string',['length'=>200]) )
		   ->addColumn('type', $md->type('string',['index'=>1]) )
		   ->addColumn('email', $md->type('string', ['length'=>80, 'unique'=>1]) )
		   ->addColumn('mobile',$md->type('string', ['length'=>80, 'unique'=>1, 'index_name'=>'the_mobile']) )
		   ->alterColumn('type', $md->type('bigInteger',['index'=>1, 'dropindex'=>1] ) )
		   ->putColumn( 'email', $md->type('string', ['length'=>120]) )
		   ->putColumn( 'name', $md->type('string', ['length'=>80]) )
		   ->putColumn( 'title', $md->type('string', ['length'=>80]) )
		   ->dropColumn('title' )
		   ->addColumn( 'title', $md->type('string', ['length'=>200]) )
		;

		$md->table("unit_test_hellojoin")
			->dropTable()
			->addColumn('join_id', $md->type('bigInteger') )
            ->addColumn( "data",   $md->type('text',['json'=>true]) )
            ->addColumn( "data_origin_1",   $md->type('json',['null'=>true]) )
            ->addColumn( "data_origin_2",   $md->type('json',['null'=>false]) )
		;
	}


	// 测试 Join 情况下，数值处理
	function testJoin() {

        global $driver;         
        $md = new Model([], $driver);
		$md->table('unit_test_helloworld')->create([
			"id" => 1024,
			"local" => "本地信息",
			"type" => "类型信息",
			"name" => "测试",
			"email" => "justtest@xpmse.cn",
			"mobile" => "13431113828",
			"title" => "录入数据测试",
			"extra_data" => "额外信息，自动忽略"
		]);

		$md->table('unit_test_hellojoin')->create([
			"join_id" => 1024,
            "data" => ["field"=>"字段1", "field2"=>"字段2"],
            "data_origin_1" => ["field"=>"字段1", "field2"=>"字段2"],
            "data_origin_2" => ["field"=>"字段1", "field2"=>"字段2"],
		]);

		$qb = $md->table('unit_test_helloworld as hello')->query();

		$resp = $qb->leftjoin( "unit_test_hellojoin as join", "hello.id","=", "join.join_id")
		   ->select("data as dt","data_origin_1 as dt1", "hello.local" , "title" , "join.join_id", "join.join_id as jid", "hello.created_at")
		   ->get()
		   ->toArray()
        ;


		$resp1 = $md->table('unit_test_hellojoin as join')->query()->get()->toArray();
		$resp2 = $md->table('unit_test_hellojoin')->query()->get()->toArray();
		$resp3 = $md->table('unit_test_hellojoin')->query()->get(["data as dt"])->toArray();
        $this->assertEquals( $resp[0]['dt']["field"], "字段1" );
        $this->assertEquals( $resp[0]['dt']["field2"], "字段2" );
        $this->assertEquals( $resp[0]['dt1']["field"], "字段1" );
        $this->assertEquals( $resp[0]['dt1']["field2"], "字段2" );
		$this->assertEquals( $resp1[0]['data'], ["field"=>"字段1", "field2"=>"字段2"] );
		$this->assertEquals( $resp2[0]['data'], ["field"=>"字段1", "field2"=>"字段2"] );
        $this->assertEquals( $resp3[0]['dt'], ["field"=>"字段1", "field2"=>"字段2"] );

		// 清空数据表
		$this->testDropTableAddAlterPutDropColumn();
	}



	// 测试读取字段信息
	function testGetColumn() {
        global $driver;         
        $md = new Model([], $driver);
		$column = $md->table('unit_test_helloworld')
		   ->getColumn('mobile');
		$this->assertEquals( $column['name'], 'mobile' );
	}

	// 测试读取字段列表
	function testGetColumns() {
        global $driver;         
        $md = new Model([], $driver);
		$columns = $md->table('unit_test_helloworld')
		   ->getColumns();
		$this->assertEquals( in_array('mobile', $columns), true );
	}

	// 测试创建数据信息
	function testCreate() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->create([
			"id" => 1024,
			"local" => "本地信息",
			"type" => "类型信息",
			"name" => "测试",
			"email" => "justtest@xpmse.cn",
			"mobile" => "13431113828",
			"title" => "录入数据测试",
			"extra_data" => "额外信息，自动忽略"
		]);

		$this->assertEquals( $resp['id'], 1024 );

	}


	// 测试查询
	function testSelect() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->select( "where id=:id", "id,name,title",['id'=>1024] );
		$this->assertEquals( $resp['data'][0]['id'], 1024 );
    }
    

    // 测试查询 Order
	function testSelectOrder() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->select( "where id=:id order by id desc limit 1", "id,name,title",['id'=>1024] );
		$this->assertEquals( $resp['data'][0]['id'], 1024 );
	}


	// 测试 查询一行
	function testGetLine() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->getLine( "where id=:id", "id,name,title",['id'=>1024] );
		$this->assertEquals( $resp['id'], 1024 );
	}

	// 测试 查询一个数值
	function testGetVar() {
        global $driver;         
        $md = new Model([], $driver);
		$title = $md->table('unit_test_helloworld')->getVar("title", "where id=:id", ['id'=>1024] );
		$this->assertEquals( $title, '录入数据测试' );
	}


	// 测试更新数据信息
	function testUpdate() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->update(1,[
			"name" => "测试修改",
			"extra_data" => "额外信息，自动忽略"
        ]);
        
		$this->assertEquals( $resp['name'], '测试修改' );

	}


	// 测试更新数据信息 By
	function testUpdateBy() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->updateBy('id',[
			"id" => 1024,
			"name" => "测试修改V2",
			"extra_data" => "额外信息，自动忽略"
		]);

		$this->assertEquals( $resp['name'], '测试修改V2' );
	}

	// 测试数据查询
	function testQuery() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->query()
				   ->where('id','=',1024)
				   ->get()
				   ->toArray();

        $this->assertEquals( $resp[0]['name'], '测试修改V2' );
	}

	function testQuerySelectRaw() {
        global $driver;         
        $md = new Model([], $driver);
		$query = $md->table('unit_test_helloworld')->query()
				   ->where('id','=',1024)
				   ->selectRaw('{mp}.test');

		$prefix = $md->getPrefix();
		// var_dump("table prefix = " . $prefix) ;
		// $bindings = $query->getBindings();
		$sql = $query->getSql();
		$this->assertEquals( $sql, "select {$prefix}mp.test from `{$prefix}unit_test_helloworld` where `{$prefix}unit_test_helloworld`.`deleted_at` is null and `id` = 1024" );
	}


	// 测试数据查询
	function testQueryToSQL() {
        global $driver;         
        $md = new Model([], $driver);
		$query = $md->table('unit_test_helloworld')->query()
				   ->where('id','=',1024);

		$prefix = $md->getPrefix();

		$bindings = $query->getBindings();
		$sql = $query->getSql();
		$this->assertEquals( $sql, "select * from `{$prefix}unit_test_helloworld` where `{$prefix}unit_test_helloworld`.`deleted_at` is null and `id` = 1024" );
	}


	// 测试运行SQL语句
	function testRunsql() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->runsql("show tables",true);
		$this->assertEquals( is_array($resp[0]), true );
		$this->assertEquals(  $md->runsql("show tables"), true );
	}



	// 测试删除
	function testDelete() {

        global $driver;         
        $md = new Model([], $driver);
		for($i=0; $i<20; $i++) {

			$md->table('unit_test_helloworld')->create([
				"id" => 1028 + $i,
				"local" => "{$i}_本地信息",
				"type" => "{$i}_类型信息",
				"name" => "{$i}_测试 $i",
				"email" => "{$i}_justtest@xpmse.cn",
				"mobile" => "{$i}_13431113828",
				"title" => "{$i}_录入数据测试",
				"extra_data" => "{$i}_额外信息，自动忽略{$i}"
			]);
		}
			
		$resp = $md->table('unit_test_helloworld')->delete(1);
		$id = $md->table('unit_test_helloworld')->getVar('id','where _id=?', [1]);
		$this->assertEquals( $id, null );

		// 测试标记删除
		$resp = $md->table('unit_test_helloworld')->delete(2, true);
        $deleted_at = $md->table('unit_test_helloworld')->getVar('deleted_at','where _id=? and deleted_at IS NOT NULL', [2]);

		$this->assertEquals( empty($deleted_at), false );

		$deleted_at = $md->table('unit_test_helloworld')->getVar('deleted_at','where _id=?', [2]);
		$this->assertEquals( empty($deleted_at), true );
	}



	// 测试数据分页
	function testPagination() {
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->query()
				   ->where('id','>',1024)
				   ->orderBy('id', 'asc')
				   ->select('id','email','title', 'deleted_at')
				   ->paginate(4,['_id'], '/index.php?a=100&page=', 1 )

				   ->toArray();

		// print_r( $resp );
		$this->assertEquals( $resp['data'][0]['id'], 1029 );
		$this->assertEquals( count($resp['data']), 4 );
    }


    //  测试数据分页2
    function testPaginationWithJSON() {

        global $driver;         
        $md = new Model([], $driver);

        $md->table('unit_test_hellojoin')->create([
			"join_id" => 1029,
            "data" => ["field"=>"字段1", "field2"=>"字段2"],
            "data_origin_1" => ["field"=>"字段1", "field2"=>"字段2"],
            "data_origin_2" => ["field"=>"字段1", "field2"=>"字段2"],
        ]);
        
        $resp = $md ->table('unit_test_helloworld as hello')->query()
                    ->leftjoin( "unit_test_hellojoin as join", "hello.id","=", "join.join_id")
                    ->where('id','>',1024)
                    ->orderBy('id', 'asc')
                    ->select('id','email','title', 'hello.deleted_at', 'data as dt', 'data_origin_1 as dt1')
                    ->paginate(4,['hello._id'], '/index.php?a=100&page=', 1 )
                    ->toArray();

		$this->assertEquals( $resp['data'][0]['id'], 1029 );
        $this->assertEquals( count($resp['data']), 4 );
        $this->assertEquals( $resp['data'][0]['dt'], ["field"=>"字段1", "field2"=>"字段2"] );
		$this->assertEquals( $resp['data'][0]['dt1'], ["field"=>"字段1", "field2"=>"字段2"] );
    }

    

	// 测试删除2
	function testRemove() {
		
        global $driver;         
        $md = new Model([], $driver);
		$resp = $md->table('unit_test_helloworld')->remove(1029, 'id', false);
		$id = $md->table('unit_test_helloworld')->getVar('id','where id=?', [1029]);
		$this->assertEquals( $id, null );

		// 测试标记删除
		$resp = $md->table('unit_test_helloworld')->remove(1030, 'id');
		$deleted_at = $md->table('unit_test_helloworld')->getVar('deleted_at','where id=? and deleted_at IS NOT NULL', [1030]);
		$this->assertEquals( empty($deleted_at), false );

		$deleted_at = $md->table('unit_test_helloworld')->getVar('deleted_at','where id=?', [1030]);
		$this->assertEquals( empty($deleted_at), true );
	}



 }
<?php
namespace Xpmse;

require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/Que.php');
require_once( __DIR__ . '/Model.php');

use \Xpmse\Model as Model;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;


/**
 * 
 * XpmSE 通用服务表
 * XpmSE 1.8.1 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Service
 *
 * USEAGE: 
 *
 */
class Service extends Model {

	private $cache = null;

	/**
	 * 通用配置表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'] , $driver );
		$this->table('service');

		$app = 'xpmse/xpmse';
		if ( is_string($param) && !empty($param) ) {
			$app = $param;
		}

        $this->app = !empty($param['app']) ?  $param['app'] : $app;
    }


    /**
     * 启动服务
     * @param string $service_id  服务ID
     */
    function start( $service_id ) {

        $se = $this->getByServiceId($service_id);
        // if ( $se["isrunning"] ) {
        //     throw new Excp("服务已启动({$service_id})", 402, ["inspect"=>$se["inspect"]]);
        // }

        // 队列服务启动
        if ( $se["type"] == "queue" ) {
            $name = $se["service_name"];
            $job = new Job(["name"=>$name]);
            $setting = $se["setting"];
            $setting["daemonize"] = 1;
            return $job->start( $setting );
        
        // WebSocket 服务器启动
        } else if ( $se["type"] == "websocket" ) {
            $name = $se["service_name"];
            $ws = new Websocket(["name"=>$name]);
            $setting = $se["setting"];
            $setting["daemonize"] = 1;
            return $ws->start( $setting );
        }
        
        throw new Excp("未知服务类型({$service_id})", 402, ["service"=>$se]);
    }

    /**
     * 平滑重启
     * @param string $service_id  服务ID
     */
    function reload( $service_id ) {

        $se = $this->getByServiceId($service_id);
        if ( !$se["isrunning"] ) {
            throw new Excp("服务尚未启动({$service_id})", 402, ["inspect"=>$se["inspect"]]);
        }

        // 队列服务重启
        if ( $se["type"] == "queue" ) {
            $name = $se["service_name"];
            $job = new Job(["name"=>$name]);
            return $job->reload();
        
        // WebSocket 服务器重启
        } else if ( $se["type"] == "websocket" ) {
            $name = $se["service_name"];
            $ws = new Websocket(["name"=>$name]);
            return $ws->reload();
        }


        throw new Excp("未知服务类型({$service_id})", 402, ["service"=>$se]);
    }

    /**
     * 重启服务
     * @param string $service_id  服务ID
     */
    function restart( $service_id ) {

        $se = $this->getByServiceId($service_id);
        if ( !$se["isrunning"] ) {
            throw new Excp("服务尚未启动({$service_id})", 402, ["inspect"=>$se["inspect"]]);
        }

        // 队列服务重启
        if ( $se["type"] == "queue" ) {
            $name = $se["service_name"];
            $job = new Job(["name"=>$name]);
            return $job->restart();
        
        // WebSocket服务重启
        } else if ( $se["type"] == "websocket" ) {
            $name = $se["service_name"];
            $ws = new Websocket(["name"=>$name]);
            return $ws->restart();
        }

        throw new Excp("未知服务类型({$service_id})", 402, ["service"=>$se]);
    }

    /**
     * 关闭服务
     * @param string $service_id  服务ID
     */
    function shutdown( $service_id ) {

        $se = $this->getByServiceId($service_id);
        if ( !$se["isrunning"] ) {
            throw new Excp("服务尚未启动({$service_id})", 402, ["inspect"=>$se["inspect"]]);
        }

        // 队列服务关闭
        if ( $se["type"] == "queue" ) {
            $name = $se["service_name"];
            $job = new Job(["name"=>$name]);
            return $job->shutdown();

        // WebSocket服务关闭
        } else if ( $se["type"] == "websocket" ) {
            $name = $se["service_name"];
            $ws = new Websocket(["name"=>$name]);
            return $ws->shutdown();
        }
        
        throw new Excp("未知服务类型({$service_id})", 402, ["service"=>$se]);
    }


    /**
     * 读取服务日志
     * @param string $service_id  服务ID
     * @param int $maxline 最多返回行数
     * @return 日志内容
     */
    function tailLog( $service_id, $maxline=500 ) {
        $se = $this->getByServiceId($service_id);
        if ( $se["type"] == "queue" ) {
            $name = $se["service_name"];
            $job = new Job(["name"=>$name]);
            return $job->tailLog( $maxline );
        } else if ( $se["type"] == "websocket" ) {
            $name = $se["service_name"];
            $ws = new Websocket(["name"=>$name]);
            return $ws->tailLog( $maxline );
        }
    }
    

    /**
     * 读取服务详情
     * @param string $service_id 服务ID
     * @return 服务数据结构
     */
    function getByServiceId( $service_id ) {
        $se = $this->getBy("service_id", $service_id);
        $this->format($se);
        return $se;
    }

    /**
     * 读取服务详情
     * @param string $name 服务名称
     * @param string $app 所属应用 :org_name/:app_name
     * @return 服务数据结构
     */
    function getByName( $name, $app ) {

        $app = strtolower( trim($app) );
        $slug = "{$app}_{$name}";
        $se = $this->getBy("slug", $slug);
        $this->format($se);
        return $se;
        
    }

    

	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// 服务 ID
			$this->putColumn( 'service_id', $this->type('string', ['length'=>128,'unique'=>1] ) )

			// 服务名称
            ->putColumn( 'name', $this->type('string', ['length'=>128] ) )
            
            // 服务别名 (/:org_name/:app_name/:name )
            ->putColumn( 'slug', $this->type('string', ['unique'=>1, 'length'=>128]) )
            
            // 服务类型: 许可值  queue 队列服务, websocket WEB socket, http HTTP服务  socket socket 服务器
            ->putColumn( 'type', $this->type('string', ['length'=>32, "default"=>"queue"]) )

			// 中文名称
            ->putColumn( 'cname', $this->type('string', [ "null"=>false, 'length'=>64, 'index'=>1] ) )

            // 配置项
            ->putColumn( 'setting', $this->type('text', [ "json"=>true ] ) )

            // 是否开机启动
            ->putColumn( 'autostart', $this->type('integer', ['length'=>1, 'default'=>0 ] ) )

			// 所属应用 :org_name/:app_name
			->putColumn( 'app', $this->type('string', [ "null"=>false, 'length'=>64, 'index'=>1] ) )
         
			// 启动优先级
            ->putColumn( 'priority', $this->type('integer', ['length'=>1, 'default'=>99, 'index'=>1 ] ) )

            // 任务状态 许可值 on 开启  off 关闭
            ->putColumn( 'status', $this->type('string', [ "default"=>"on", "null"=>false, 'length'=>32, 'index'=>1] ) )
            
			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
    }

    /**
     * 系统服务初始化( 注册行为/注册任务/设置默认值等... )
     */
    public function __defaults() {
        // 注册服务器
        $services = [
            [
                "app" => "xpmse/xpmse",
                "name" => "Default",
                "type" => "websocket",
                "cname" => "默认WebSocket服务器",
                "autostart" => 1,
                "status" => 'on',
                "priority" => 10,
                "setting" => [
					"host" => "127.0.0.1",
                    "port" =>10086,
                    "home" => Utils::getHome(),
                    "user" => 0
				]
            ],[
                "app" => "xpmse/xpmse",
                "name" => "App",
                "type" => "queue",
                "cname" => "应用管理队列服务",
                "autostart" => 1,
                "status" => 'on',
                "priority" => 20,
                "setting" => [
                    "host" => "127.0.0.1",
                    "home" => Utils::getHome(),
                    "user" => 0,
                    "worker_num" =>1
                ]
            ],[
                "app" => "xpmse/xpmse",
                "name" => "Search",
                "type" => "queue",
                "cname" => "搜索引擎数据推送队列服务",
                "autostart" => 1,
                "status" => 'on',
                "priority" => 20,
                "setting" => [
                    "host" => "127.0.0.1",
                    "home" => Utils::getHome(),
                    "user" => 0,
                    "worker_num" =>10
                ]
            ]
        ];
        foreach( $services as $service ) {
            try { $this->create($service); } catch( Excp $e) { $e->log(); }
        }
    }

    /**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"service_id",  // 服务 ID
			"name",  // 服务名称
			"slug",  // 服务别名 (/:org_name/:app_name/:name )
			"type",  // 服务类型: 许可值  queue 队列服务, websocket WEB socket, http HTTP服务  socket socket 服务器
			"cname",  // 中文名称
			"autostart",  // 是否开机启动
			"app",  // 所属应用 :org_name/:app_name
            "priority",  // 启动优先级
            "status", // 任务状态 许可值 on 开启  off 关闭
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
    }
    

    public function formatSelect( & $select ) {
        return [];
    }


    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        
        if ( !empty($data["app"]) ) {
            $data["app"] = strtolower(trim($data["app"]));
        }

        if ( !empty($data["name"]) &&  !empty($data["app"]) ) {
            $data["slug"] = "DB::RAW(CONCAT(`app`,'_', `name`))";
        }
        return parent::saveBy( $uniqueKey,  $data,  $keys , $select );
    }
    


	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		
		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"slug"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `slug`))"
			]);

			if ( $row['deleted_at'] == $time ) {
				return true;
			}
			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
    }


    /**
     * 重载创建服务
     */
	function create( $data ) {
        if ( !empty($data["app"]) ) {
            $data["app"] = strtolower(trim($data["app"]));
        }
        // 生成服务ID 
        if ( empty($data["service_id"]) ) { 
			$data["service_id"] = $this->genId();
        }

        if ( !empty($data["name"]) &&  !empty($data["app"]) ) {
            $data["slug"] = "DB::RAW(CONCAT(`app`,'_', `name`))";
        }
        
		return parent::create($data);
	}
    
    
    /**
     * 读取有服务的应用清单
     * @param string $current 当前激活的服务
     */
    public function getApps( $current = null ) {
        $qb = $this->query()
                   ->leftJoin("app as app", "app.slug", "=", "service.app")
                   ->where("app.status", "=", "installed")
                   ->orWhereNull("app.status")
                   ->groupBy("app")
                   ->orderBy("app.created_at", "asc")
                   ->select("app.org", "app.name", "app.cname", "app.status", "app.image", "app.icontype", "app.icon", "service.app as slug")
                   ->selectRaw("count(*) as cnt")
                   ;
        $data = $qb->get()->toArray();

        if ( empty($data) ) {
            return [];
        }

        $i = 0;
        foreach( $data as & $app ) {
            if ( $app["slug"] == "xpmse/xpmse" ) {
                $app["org"]="xpmse";
                $app["name"]="xpmse";
                $app["cname"]="全局";
                $app["icontype"]="iconfont";
                $app["icon"] = "icon-jm";
            }

            $app["active"] = ($current == $app["slug"]);
            if ( $current == null && $i==0 ) {
                $app["active"] = true;
            }
            $i++;
        }

        return $data;
    }


	/**
	 * 读取所有配置
	 * @param  string $app 命名空间 (应用名称， 默认为空，使用创建对象时选用的命名空间)
	 * @return array  ["map"=>..., "data"=>...]
	 */
	public function getAll( $app = null ) {
		$app = empty($app) ? $this->app : $app;
		$rows = $this->query()
		             ->where('app', '=' , $app )
		             ->orderBy('priority', 'asc')
		             ->limit(100)
		             ->select("service.*")
                     ->get()->toArray();
        
        foreach( $rows  as & $rs ) {
            $this->format( $rs );
        }
		return $rows;
    }


    /**
     * 处理数据
     */
    public function format( & $rs ) {

        // 服务类型 (queue 队列服务, websocket WEB socket, http HTTP服务  socket socket 服务器)
        $type = $rs["type"];

        if ( $type === "queue" ) { // 载入队列服务

            $app = explode("/", $rs["app"]);
            $org = ucfirst($app[0]); 
            $app = ucfirst($app[1]);
            $name = ucfirst( $rs["name"] );

            $name = "{$org}{$app}{$name}";
            if ( empty($rs["setting"]["port"]) ) {
                $rs["setting"]["port"] = 0;
            }
            

            $job = new Job(["name"=>$name]);
            $inspect = $job->inspect();
            $rs["inspect"] = $inspect;
            $rs["service_name"] = $name;

            // shutdown / hangup / running / pending 
            $rs["service_status"] = $rs["inspect"]["status"];
            $rs["isrunning"] = ($rs["inspect"]["status"] == 'running');

        } else if ( $type === "websocket" ) { // 载入队列服务

            $app = explode("/", $rs["app"]);
            $org = ucfirst($app[0]); 
            $app = ucfirst($app[1]);
            $name = ucfirst( $rs["name"] );

            $name = "{$org}{$app}{$name}";
            if ( empty($rs["setting"]["port"]) ) {
                $rs["setting"]["port"] = 0;
            }
            

            $ws = new Websocket(["name"=>$name]);
            $inspect = $ws->inspect();
            $rs["inspect"] = $inspect;
            $rs["service_name"] = $name;

            // shutdown / hangup / running / pending 
            $rs["service_status"] = $rs["inspect"]["status"];
            $rs["isrunning"] = ($rs["inspect"]["status"] == 'running');
        }

    }


    /**
     * 查询服务
     */
    public function search( $query ) {
        $qb = $this->query();
        if ( isset( $query["autostart"]) ) {
            $qb->where("autostart", $query["autostart"]);
        }
        $qb->orderBy("priority", "asc");

        $rows = $qb->get()->toArray();
        foreach( $rows  as & $rs ) {
            $this->format( $rs );
        }

        return $rows;
    }
    
}


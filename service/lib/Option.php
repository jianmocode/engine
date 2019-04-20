<?php
namespace Xpmse;

require_once( __DIR__ . '/Inc.php');
require_once( __DIR__ . '/Conf.php');
require_once( __DIR__ . '/Err.php');
require_once( __DIR__ . '/Excp.php');
require_once( __DIR__ . '/Utils.php');
require_once( __DIR__ . '/Que.php');
require_once( __DIR__ . '/Model.php');


/**
 * 
 * XpmSE通用配置表
 * XpmSE 1.4.8 以上
 *
 * CLASS 
 * 		\Xpmse\Model
 * 		      |
 *    \Xpmse\Option
 *
 * USEAGE: 
 *
 */

use \Xpmse\Model as Model;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Utils as Utils;


class Option extends Model {

	private $cache = null;

	/**
	 * 通用配置表
	 * @param integer $company_id [description]
	 */
	function __construct( $param=[] ) {

		$driver = empty( Conf::G('data/driver') ) ? 'Database' : Conf::G('data/driver');
		parent::__construct(['prefix'=>'core_'] , $driver );
		$this->table('option');

		$app = 'xpmse/xpmse';
		if ( is_string($param) && !empty($param) ) {
			$app = $param;
		}

		$this->app = !empty($param['app']) ?  $param['app'] : $app;
	}


	/**
	 * 数据表结构
	 * @return [type] [description]
	 */
	function __schema() {
		// 数据结构
		try {
			
			// 配置 ID
			$this->putColumn( 'option_id', $this->type('string', ['length'=>128,'unique'=>1] ) )

			// 中文名称
			->putColumn( 'name', $this->type('string', ['length'=>128] ) )

			// 配置 KEY
			->putColumn( 'key', $this->type('string', [ "null"=>false, 'length'=>64, 'index'=>1] ) )

			// 配置 数值
			->putColumn( 'value', $this->type('longText', ["json"=>true] ) )

			// 任务SLUG 
			->putColumn( 'slug', $this->type('string', ['unique'=>1, 'length'=>128]) )

			// 所属应用 org/app
			->putColumn( 'app', $this->type('string', [ "null"=>false, 'length'=>64, 'index'=>1] ) )

			// 重要程度排序
			->putColumn( 'order', $this->type('integer', [ 'default'=>99 , 'index'=>1] ) )

			;

		} catch( Exception $e ) {
			Excp::elog($e);
			throw $e;
		}
    }
    
    /**
     * 读取有配置信息的资料
     */
    public function getApps( $current = "xpmse/xpmse" ) {
        $qb = $this->query()
                   ->leftJoin("app as app", "app.slug", "=", "option.app")
                   ->where("app.status", "=", "installed")
                   ->orWhereNull("app.status")
                   ->groupBy("app")
                   ->orderBy("app.created_at", "asc")
                   ->select("app.org", "app.name", "app.cname", "app.status", "app.image", "app.icontype", "app.icon", "option.app as slug")
                   ->selectRaw("count(*) as cnt")
                   ;
        $data = $qb->get()->toArray();

        if ( empty($data) ) {
            return [];
        }

        foreach( $data as & $app ) {
            if ( $app["slug"] == "xpmse/xpmse" ) {
                $app["org"]="xpmse";
                $app["name"]="xpmse";
                $app["cname"]="全局";
                $app["icontype"]="iconfont";
                $app["icon"] = "icon-jm";
            }

            $app["active"] = ($current == $app["slug"]);
        }

        return $data;
    }


	/**
	 * 注册配置项 (一般在安装应用时调用)
	 * @param  string $name  配置项中文名称
	 * @param  string $key   配置项键 
	 * @param  mix  $value   配置项值 (支持数组)
	 * @param  int $order    自定义排序，默认为 99 
	 * @param  string $app   命名空间( 应用名称， 默认为空使用创建对象时选用的命名空间)
	 * @return $this
	 */
	public function register( $name, $key, $value=null, $order=99, $app=null  ) {

		$id = $this->genId();
		$app = empty($app) ? $this->app : $app;
		$this->create([
			// 'option_id'=>$id,
			'name'=>$name,
			'key'=>$key, 
			'value'=>$value, 
			'order' => $order,
			'app'=>$app
		]);

		return $this;
	}


	/**
	 * 设定配置项排序
	 * @param string $key   配置项键 
	 * @param int $order    自定义排序数值
	 * @param string $app   命名空间( 应用名称， 默认为空使用创建对象时选用的命名空间)
	 */
	public function setOrder( $key, $order, $app=null ) {
		$app = empty($app) ? $this->app : $app; 
		$this->update(['key'=>$key, 'order'=>$order, 'app'=>$app]);
		return $this;
	}


	/**
	 * 注销配置 (只能注销应用的配置, 一般在卸载应用时调用)
	 * @param  string $app 命名空间( 应用名称 )
	 * @return $this
	 */
	public function unregister( $app=null ) {
		$app = empty($app) ? $this->app : $app;
		if ( empty($app) || $app == 'xpmse/xpmse' ) {
			throw new Excp("非法注销请求", 403, ['app'=>$app]);
		}

		$resp = $this->getAll( $app );
		foreach ($resp['data'] as $rs ) {
			$this->remove($rs['option_id'], 'option_id', false );
		}
		return $this;
	}


	/**
	 * 读取配置项值 
	 * @param  string $key 配置项键
	 * @param  string $app 命名空间 (应用名称， 默认为空，使用创建对象时选用的命名空间)
	 * @return mix 配置项值
	 */
	public function get( $key, $app=null ) {

		if ( is_numeric($key) ) {
			return parent::get( $key );
		}

		$app = empty($app) ? $this->app : $app;

		$rows = $this->query()
			       ->where("key", '=', $key) 
			       ->where( "app", '=', $app)
			       ->limit(1)
			       ->select('value', 'key')
			       ->get()
                   ->toArray();
        
		if ( empty($rows) ) {
			return null;
		}
		$rs = current($rows);
		
		return $rs['value'];
		// Utils::out( $rows );
		// return $this->getVar('value', 'WHERE `key`=? AND `app`=?', [$key, $app]);

	}



	/**
	 * 设定配置项值
	 * @param  string $key 配置项键
	 * @param  mix  $value 配置项值 (支持数组)
	 * @param  string $app 命名空间 (应用名称， 默认为空，使用创建对象时选用的命名空间)
	 */
	public function set( $key, $value, $app=null ) {
	
		$app = empty($app) ? $this->app : $app;
		$slug = $key . '_' . $app;
		$this->updateBy('slug',['key'=>$key, 'value'=>$value, 'app'=>$app, 'slug'=>$slug]);

		return $this;
	}



	/**
	 * 读取所有配置
	 * @param  string $app 命名空间 (应用名称， 默认为空，使用创建对象时选用的命名空间)
	 * @return array  ["map"=>..., "data"=>...]
	 */
	public function getAll( $app = null ) {
		$app = empty($app) ? $this->app : $app;
		$map = [];
		$rows = $this->query()
		             ->where('app', '=' , $app )
		             ->orderBy('order', 'asc')
		             ->limit(100)
		             ->select('option_id','key','value', "name as cname")
		             ->get()->toArray();
		foreach ($rows as $rs ) {
			$map[$rs['key']] = $rs['value'];
		}

		return ['map'=>$map, 'data'=>$rows];
	}


	function create( $data ) {
		if ( !empty($data['key']) ) {
			$data['app'] = empty($data['app']) ? $this->app : $data['app'];
			$data['slug'] = $data['key'] . '_' . $data['app'];
		}

		if ( empty($data['option_id']) ) {
			$data['option_id'] = $this->genId();
		}

		return parent::create($data);
	}


	function genId() {
		return time() . rand(100000,999999);
	}



	// public function createOrUpdate( $data,  $updateColumns = null ) { 

	// 	if ( empty($data['option_id']) ) {
	// 		$data['option_id'] = $this->nextid();
	// 	}

	// 	if ( !empty($data['key']) ) {
	// 		$data['app'] = empty($data['app']) ? $this->app : $data['app'];
	// 		$data['slug'] = $data['key'] . '_' . $data['app'];
	// 	}


	// 	return parent::createOrUpdate($data, $updateColumns);
	// }

}


<?php
// 即将废弃
namespace Xpmse;
require_once(__DIR__ . '/Inc.php');
require_once(__DIR__ . '/Conf.php');
require_once(__DIR__ . '/Err.php');
require_once(__DIR__ . '/Excp.php');
require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/Mem.php');
require_once(__DIR__ . '/wechat-encoder/WXBizMsgCrypt.php');


use \Exception as Exception;
use \Ratchet\MessageComponentInterface;
use \Ratchet\ConnectionInterface;


use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Mem as Mem;
use \Xpmse\Utils as Utils;


use \Wechat\Encoder\WXBizMsgCrypt as WXBizMsgCrypt;
use \Wechat\Encoder\ErrorCode as ErrorCode;


class Session {
    public static function unserialize($session_data) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}



/**
 * XpmSE小程序 Ws 服务器
 */
class Wxappserver implements MessageComponentInterface {

    protected $clients; // ConnectionID > ConnectionInterface Conn
    protected $users;   // ConnectionID > UserInfo
    protected $idmap;   // UserID >  ConnectionID

    public function __construct() {

        $this->clients = [];   
        $this->users = [];     
        $this->idmap = [];    
    }

    public function onOpen(ConnectionInterface $conn ) {
        
        $req = $this->getQuery( $conn->WebSocket->request->getQuery(true) );

        // Utils::out( 'Request is:', $req , "\n");

        // 载入用户会话
        $sid = $req['_sid'];
        $table = $req['_table'];
        $user_table = $req['_user'];
        $prefix = $req['_prefix'];

        if ( empty($table) || empty($prefix) ) {
            $conn->close();
        }

        // $mtab = $this->mtab( $table, $prefix  );
        
        $ss = $this->loadSession( $sid );
        
        $cid = Utils::sid();
        $conn->sid = $sid;  // Session id
        $conn->cid = $cid;  // Connection id
        $conn->acl = $this->aclname( $prefix );  // Acl Table Name
        $conn->user_table = $user_table;
        $conn->prefix = $prefix;
            
        // 读取用户资料
        if ( isset($ss['_user']) ) {

            $olduid = $ss['_user'];
            if ( !empty($this->idmap[$olduid]) ) {  // 断开原有链接
                $oldCid = $this->idmap[$olduid];
                $oldConn = $this->clients[$oldCid];
                echo "The user has another connection: uid:$olduid cid:$oldCid ";

                if ( !empty( $oldConn) ) {
                    $oldConn->send( json_encode( ['data'=>['request' => ['c'=>'_close', 'b'=>[]], 'response'=>$oldConn, 'error'=>'Another connection created'], 'code'=>401] ) );
                    $oldConn->close();
                     echo " Closed ";
                }
                
                echo " END \n";
            }

            $conn->uid = $ss['_user'];
            $this->idmap[$conn->uid]  =  $conn->cid;
            $utab = $this->utab( $user_table, $prefix );
            $user = $utab->getLine("WHERE _id=?", ['_id', 'nickName', 'gender', 'avatarUrl', 'language', 'isadmin', 'group'],  [$conn->uid]);

        } else {
            $conn->uid = null;
            $user = [];
        }

        // Utils::out( 'user is:', $user , "\n" );
        

        $user['id'] = $cid;
        $this->clients[$cid] = $conn; 
        $this->users[$cid] = $user;

        if ( !$this->checkAccess($conn, 'connection', 'r') && !$this->checkAccess($conn, 'connection', 'w')   ) {
            $conn->send( json_encode( ['data'=>['request' => ['c'=>'_open', 'b'=>[]], 'response'=>$conn, 'error'=>'no permission'], 'code'=>403] ) );
            $conn->close();
            echo "Connection Connect refused: cid={$conn->cid} uid={$conn->uid} rid={$conn->resourceId} no permission \n";
            return;
        };

        $conn->send( json_encode( ['data'=>['request' => ['c'=>'_open', 'b'=>[]], 'response'=>$this->users[$cid]], 'code'=>0] ) );
        echo "Connection Connected: cid={$conn->cid} uid={$conn->uid} rid={$conn->resourceId} \n";
        
    }

    public function onMessage( ConnectionInterface $from, $msg) {

        // 更新用户资料
        $ss = $this->loadSession( $from->sid );
        if ( isset($ss['_user']) &&  $from->uid == null ) {

            $utab = $this->utab( $from->user_table, $from->prefix );
            $uid = $ss['_user']; 
            $this->idmap[$uid]  =  $from->cid;
            $user = $utab->getLine("WHERE _id=?", ['_id', 'nickName', 'gender', 'avatarUrl', 'language', 'isadmin', 'group'],  [$uid]);
            $user['id'] = $from->cid;
            $from->uid = $uid;
            $this->users[$from->cid] = $user;
            $this->clients[$from->cid] = $from;
        }

        $m = json_decode( $msg, true );

        echo "Message Forward: cid={$from->cid} uid={$from->uid} rid={$from->resourceId} Message={$msg} \n";

        if ( $m === false ) {
            $from->send( (new Excp("错误的请求指令", 500, ['from'=>$from->cid, 'msg'=>$msg]))->toJSON() );
            return ;
        }

        if ( $m['c'] === 'getConnections' || $m['c'] === 'getClients' ) {

            $fromUser = $this->users[$from->cid];
            if ( !$this->checkAccess($from, 'clients', 'r') && !$this->checkAccess($from, 'clients', 'w')   ) {
                $from->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser, 'error'=>'no permission'], 'code'=>403] ) );
                return;
            }

            $from->send( json_encode( [ 'data'=>['request' => $m, 'response'=>$this->users], 'code'=>0] ) );
            return;

        } else if ( $m['c'] === 'ping' ) {

            $t = $m['t'] ;  $b = $m['b'];
            if ( empty($t) ) {
                $from->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );    
            
            } else if ( $t == 'all' ) {
                foreach ($this->clients as $conn ) {
                    $conn->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );    
                }
            } else if ( isset($this->clients[$t]) ) {
                $this->clients[$t]->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );

                if ($from->cid != $this->clients[$t]->cid ) { // 通知自己成功
                    $from->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );  
                }

            } else if ( isset($this->idmap[$t]) ) {

                $t = $this->idmap[$t];
                if ( isset($this->clients[$t]) ) { 
                    $this->clients[$t]->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );
                    
                    if ($from->cid != $this->clients[$t]->cid ) { // 通知自己成功
                        $from->send( json_encode( ['data'=>['request' => $m, 'response'=>['resp'=>'pong', 'params'=>$b]], 'code'=>0] ) );
                    }
                }
            } else { // Not Online

                $from->send( json_encode( [
                    'data'=>['request' => $m, 
                    'response'=>['resp'=>'offline', 'params'=>$b]], 
                    'code'=>0] ));
            }

        } else {

            $c =$m['c'];  $t = $m['t'] ;  $b = $m['b']; $fromUser = $this->users[$from->cid];

            $fromUser = $this->users[$from->cid];
            if ( !$this->checkAccess($from, 'clients', 'w')   ) {
                $from->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser, 'error'=>'from user no permission'], 'code'=>403] ) );
                return;
            }


            if ( empty($t) ) {
                $from->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser], 'code'=>0] ) );    
            } else if ( $t == 'all' ) {
                foreach ($this->clients as $conn ) {

                    if ( !$this->checkAccess($conn, 'clients', 'r')   ) {
                        $from->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser, 'error'=>$conn->cid . ' no permission'], 'code'=>403] ) );
                    } else {
                        $conn->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser], 'code'=>0] ) );    
                    }

                }
            } else if ( isset($this->clients[$t]) ) {
                $this->clients[$t]->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser], 'code'=>0] ) );
            } else if ( isset($this->idmap[$t]) ) {
                $t = $this->idmap[$t];
                if ( isset($this->clients[$t]) ) { 
                    $this->clients[$t]->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser], 'code'=>0] ) );
                }
            } else {
                $from->send( json_encode( ['data'=>['request' => $m, 'response'=>$fromUser, 'error'=>'user not found or offline'], 'code'=>404] ) );
            }
        }

    }

    public function onClose(ConnectionInterface $conn) {
        
        unset( $this->clients[$conn->cid]);
        unset( $this->users[$conn->cid]);
        if ( $conn->uid != null ) {
            unset( $this->idmap[$conn->uid]);
        }

        echo "Connection Disconnected: cid={$conn->cid} uid={$conn->uid} rid={$conn->resourceId} \n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {

        echo $e;
        
        echo "Error Occurred: cid={$conn->cid} uid={$conn->uid} rid={$conn->resourceId}  Error:{$e->getMessage()}\n";
        @$conn->close();
    }




    /**
     * 载入用户会话
     * @param  [type] $sid [description]
     * @return [type]      [description]
     */
    private function loadSession( $sid ) {

        if ( empty($sid) ) {
            $sid = Utils::sid();
        } else if ( strpos($sid, 'APP-') !== 0 ) {
            $sid = 'BaaS-' . $sid;
        }

        $mem = new Mem;
        $redis = $mem->redis();
        $redis->select(0);
        $name = "PHPREDIS_SESSION:$sid";
        $string = $redis->get( $name );
        // Utils::out( $name, ' get=?', $string, 'ping', $redis->ping(), "\n" );

        if ( $string === false || $string == null ) {
            return [];
        }

        $session = Session::unserialize( $string );

        // Utils::out( 'session:', $sid, '\n', $session );

        if ( $session == null ) return [];
        return $session;

        
        // @session_write_close();
        // @session_id( $sid );
        // @session_start();

    }



    private function utab( $table, $prefix ) {

        $prefix  =  empty($prefix) ? '_baas_' : '_baas_' . $prefix . '_';
        return M( 'Table', $table, ['prefix'=>$prefix]);
    }

    private function mtab( $table, $prefix ) {

        if ( $table == null ) return;

        $prefix  =  empty($prefix) ? '_baas_' : '_baas_' . $prefix . '_';
        $tab =  M( 'Table', $table, ['prefix'=>$prefix]);

        $table_name = $prefix . $table;

        if ( !$tab->tableExists() ) {
            $schema =[
                ["name"=>"from",  "type"=>"integer", "option"=>["length"=>10, "index"=>true], "acl"=>"rw:-:-" ],
                ["name"=>"to",    "type"=>"string", "option"=>["length"=>10, "index"=>true], "acl"=>"rw:-:-" ],
                ["name"=>"message",  "type"=>"text", "option"=>["json"=>true], "acl"=>"rw:-:-" ],
                ["name"=>"status",   "type"=>"string", "option"=>["length"=>20, 'default'=>'sent', "index"=>true], "acl"=>"rw:-:-" ],
                ["name"=>"_user",  "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
                ["name"=>"_group", "type"=>"string", "option"=>["length"=>128, "index"=>true] ],
                ["name"=>"_acl", "type"=>"text", "option"=>["json"=>true]]
            ];

            $this->data['acl'] = ( !empty( $this->data['acl'] ) ) ? $this->data['acl'] : [
                "fields" =>[ "{default}"=>"rw:-:-" ],
                "record"=>"-:-:-",
                "table" =>"-:-:-",
                "user" => 'admin',
                "group" => 'member'
            ];

            $this->data['acl']['field'] = empty($this->data['acl']['field']) ? "rwd:r:-" : $this->data['acl']['field'];
            $this->data['acl']['fields'] = is_array($this->data['acl']['fields']) ?  $this->data['acl']['fields'] : [] ;
            $this->data['acl']['fields']["{default}"] = empty($this->data['acl']['fields']["{default}"]) ? $this->data['acl']['field'] : "rwd:r:-";
            $this->data['acl']['record'] = !empty($this->data['acl']['record']) ?  $this->data['acl']['record'] : "rwd:rw:-" ;
            $this->data['acl']['table'] = !empty($this->data['acl']['table']) ?  $this->data['acl']['table'] : "rwd:-:-" ;
            $this->data['acl']['user'] = !empty($this->data['acl']['user']) ?  $this->data['acl']['user'] : "admin" ;
            $this->data['acl']['group'] = !empty($this->data['acl']['group']) ?  $this->data['acl']['group'] : "login" ;

            // 保存数据权限信息
            foreach ($schema as $sc ) {
                if ( isset( $sc['acl']) ) {
                    $field = $sc['name'];
                    $this->data['acl']['fields'][$field] = $sc['acl'];
                }
            }

            $acl = M('Tabacl');
            $resp = $acl->save( $table_name, $this->data['acl']);
            $resp = $acl->save( $prefix. 'ws:wxapp', [
                "fields" =>[ 
                    "{default}"=>"rw:rw:rw",
                    "connection" => "rw:rw:rw",  // 任何人都可以连接
                    "clients" => "rw:rw:rw", // 任何人都可以读取在线用户
                    "ping" => "rw:rw:rw", // 任何人都可以读取在线用户
                    "message" => "rw:rw:rw", // 任何人都可以读取在线用户
                ],
                "record"=>"-:-:-",
                "table" =>"-:-:-",
                "user" => 'admin',
                "group" => 'member'
            ]);
            $resp = $tab->__schema( $schema );
        }

        return $tab;

    }


    private function aclname( $prefix ) {
        $prefix  =  empty($prefix) ? '_baas_' : '_baas_' . $prefix . '_';
        return  $prefix. 'ws:wxapp';
    }


    private function checkAccess( ConnectionInterface $conn ,  $method, $m='r' ) {
        
        $cid = $conn->cid; $table_name = $conn->acl;

        if ( $cid == null ) return false;
        if ( $table_name == null ) return false;

        if ( isset($this->users[$cid]) ) {

            if ( $this->users[$cid]['isadmin'] == 1 ) {
                return true;
            }

            $user = [
                "_user" => $this->users[$cid]['_id'],
                "_group" => $this->users[$cid]['group']
            ];
        } else {
            $user = [
                "_user" => $cid,
                "_group" => 'guest'
            ];
        }

        $tabacl =  M('Tabacl');
        $acl = $tabacl->read($table_name, true);
        if ( $acl == null ) {
            return true;
        }
        $owner = ["_user"=>$acl['user'], "_group"=>$acl['group'] ];
        $default = empty($acl['fields']['{default}']) ? 'rw:rw:rw' : $acl['fields']['{default}'];
        $av = empty($acl['fields'][$method]) ? $default : $acl['fields'][$method];

        return $tabacl->checkAccess($m ,$av, $owner, $user);
    }


    private function getQuery( $queryString  ) {
        $res = [];
        $arr = explode('&', $queryString );
        foreach ($arr as $qs ) {
            $qa = explode('=', $qs);
            $name = $qa[0];
            $value = $qa[1];
            if ( !empty($name) ) {
                $res[$name] = $value;
            }
        }

        return $res;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\WebSocket;

use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;

use EasySwoole\FastCache\Cache;
use \swoole_server;
use \swoole_websocket_server;
use \swoole_http_request;
use \Exception;

/**
 * WebSocket Events
 * Class WebSocketEvents
 * @package App\WebSocket
 */
class WebSocketEvents
{


    /**
     * 打开了一个链接
     * @param swoole_websocket_server $server
     * @param swoole_http_request $request
     */
    static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $token = $request->get["token"];

        if(!isset($token)){
            $data = [
                "type" => "token expire"
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        $db = MysqlPool::defer();
        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_'.$token);
        $user = json_decode($user,true);
        if($user == null){
            $data = [
                "type" => "token expire"
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        //绑定fd变更状态
        Cache::getInstance()->set('uid'.$user['id'], ["value"=>$request->fd],3600);
        Cache::getInstance()->set('fd'.$request->fd, ["value"=>$user['id']],3600);
        $db->where('id', $user['id'])->update('user',['status' => 'online']);//标记为在线
        //给好友发送上线通知，用来标记头像去除置灰
        $friend_list = $db->where('user_id',$user['id'])->get('friend');
        $data = [
            "type"  => "friendStatus",
            "uid"   => $user['id'],
            "status"=> 'online'
        ];
        foreach ($friend_list as $k => $v) {
            $fd = Cache::getInstance()->get('uid'.$v['friend_id']);//获取接受者fd
            if ($fd){
                $server->push($fd['value'], json_encode($data));//发送消息
            }
        }
        //获取未读消息盒子数量
        $count = $db->where('user_id',$user['id'])->where('`read`',0)->count('system_message');
        $data = [
            "type"      => "msgBox",
            "count"     => $count
        ];
        //检查离线消息
        $offline_messgae = $db->where('user_id', $user['id'])->where('`status`', 0)->get('offline_message');
        foreach ($offline_messgae as $k=>$v) {

            $fd = Cache::getInstance()->get('uid'.$user['id']);//获取接受者fd
            if ($fd){
                $server->push($fd['value'], $v['data']);//发送消息
                $db->where('id', $v['id'])->update('offline_message',['status' => 1]);
            }
        }
        $server->push($request->fd, json_encode($data));
    }

    /**
     * 链接被关闭时
     * @param swoole_server $server
     * @param int $fd
     * @param int $reactorId
     * @throws Exception
     */
    static function onClose(\swoole_server $server, int $fd, int $reactorId)
    {
        $uid = Cache::getInstance()->get('fd'.$fd);
        $db = MysqlPool::defer();

        $friend_list = $db->where('user_id',$uid['value'])->get('friend');
        $data = [
            "type"  => "friendStatus",
            "uid"   => $uid['value'],
            "status"=> 'offline'
        ];

        foreach ($friend_list as $k => $v) {
            $result = Cache::getInstance()->get('uid'.$v['friend_id']);//获取接受者fd
            if ($result){
                $server->push($result['value'], json_encode($data));//发送消息
            }
        }
        if ($uid !== false) {
            Cache::getInstance()->unset('uid'.$uid['value']);// 解绑uid映射
        }
        Cache::getInstance()->unset('fd' . $fd);// 解绑fd映射
        $db->where('id',$uid['value'])->update('user',['status' => 'offline']);
    }



}

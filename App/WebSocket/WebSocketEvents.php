<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\WebSocket;

use App\Model\FriendModel;
use App\Model\OfflineMessageModel;
use App\Model\SystemMessageModel;
use App\Model\UserModel;
use EasySwoole\FastCache\Cache;
use EasySwoole\RedisPool\RedisPool;
use \Exception;

/**
 * WebSocket Events
 * Class WebSocketEvents
 * @package App\WebSocket
 */
class WebSocketEvents
{

    /**
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\Http\Request $request
     */
    static function onOpen(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request)
    {
        $token = $request->get["token"];

        if (!isset($token)) {
            $data = [
                "type" => "token expire"
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_' . $token);
        $user = json_decode($user, true);
        if ($user == null) {
            $data = [
                "type" => "token expire"
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        //绑定fd变更状态
        Cache::getInstance()->set('uid' . $user['id'], ["value" => $request->fd], 3600);
        Cache::getInstance()->set('fd' . $request->fd, ["value" => $user['id']], 3600);
        UserModel::create()->where('id', $user['id'])->update(['status' => 'online']);//标记为在线
        //给好友发送上线通知，用来标记头像去除置灰
        $friend_list = FriendModel::create()->where('user_id', $user['id'])->all();
        $friend_list = $friend_list ? $friend_list->toArray() : [];
        $data = [
            "type" => "friendStatus",
            "uid" => $user['id'],
            "status" => 'online'
        ];
        foreach ($friend_list as $k => $v) {
            $fd = Cache::getInstance()->get('uid' . $v['friend_id']);//获取接受者fd
            if ($fd) {
                $server->push($fd['value'], json_encode($data));//发送消息
            }
        }
        //获取未读消息盒子数量
        $count = SystemMessageModel::create()->where('user_id', $user['id'])->where('read', 0)->count();
        $data = [
            "type" => "msgBox",
            "count" => $count
        ];
        //检查离线消息
        $offline_message = OfflineMessageModel::create()->where('user_id', $user['id'])->where('status', 0)->all();
        $offline_message = $offline_message ? $offline_message->toArray() : [];
        if ($offline_message) {
            foreach ($offline_message as $k => $v) {

                $fd = Cache::getInstance()->get('uid' . $user['id']);//获取接受者fd
                if ($fd) {
                    $server->push($fd['value'], $v['data']);//发送消息
                    OfflineMessageModel::create()->where('id', $v['id'])->update(['status' => 1]);
                }
            }
        }
        $server->push($request->fd, json_encode($data));
    }

    /**
     * 链接被关闭时
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @throws Exception
     */
    static function onClose(\Swoole\Server $server, int $fd, int $reactorId)
    {
        $uid = Cache::getInstance()->get('fd' . $fd);

        $friend_list = FriendModel::create()->where('user_id', $uid['value'])->all();
        $friend_list = $friend_list ? $friend_list->toArray() : [];
        $data = [
            "type" => "friendStatus",
            "uid" => $uid['value'],
            "status" => 'offline'
        ];

        if ($friend_list) {
            foreach ($friend_list as $k => $v) {
                $result = Cache::getInstance()->get('uid' . $v['friend_id']);//获取接受者fd
                if ($result) {
                    $server->push($result['value'], json_encode($data));//发送消息
                }
            }
        }
        if ($uid !== false) {
            Cache::getInstance()->unset('uid' . $uid['value']);// 解绑uid映射
        }
        Cache::getInstance()->unset('fd' . $fd);// 解绑fd映射
        UserModel::create()->where('id', $uid['value'])->update(['status' => 'offline']);
    }
}

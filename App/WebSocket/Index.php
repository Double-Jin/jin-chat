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
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\FastCache\Cache;
use EasySwoole\Socket\AbstractInterface\Controller;

/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Controller
{

    public function chatMessage()
    {
        $info = $this->caller()->getArgs();
        $info = $info['data'];

        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_' . $info['token']);
        $user = json_decode($user, true);
        if ($user == null) {
            $data = [
                "type" => "token expire"
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db = MysqlPool::defer();

        //获取swooleServer

        if ($info['to']['type'] == "friend") {
            //好友消息
            $data = [
                'username' => $info['mine']['username'],
                'avatar' => $info['mine']['avatar'],
                'id' => $info['mine']['id'],
                'type' => $info['to']['type'],
                'content' => $info['mine']['content'],
                'cid' => 0,
                'mine' => $user['id'] == $info['to']['id'] ? true : false,//要通过判断是否是我自己发的
                'fromid' => $info['mine']['id'],
                'timestamp' => time() * 1000
            ];
            if ($user['id'] == $info['to']['id']) {
                return;
            }
            //获取swooleServer
            $server = ServerManager::getInstance()->getSwooleServer();

            $fd = Cache::getInstance()->get('uid' . $info['to']['id']);//获取接受者fd
            if ($fd == false) {
                //这里说明该用户已下线，日后做离线消息用
                $offline_message = [
                    'user_id' => $info['to']['id'],
                    'data' => json_encode($data),
                ];
                //插入离线消息
                $db->insert('offline_message', $offline_message);

            } else {
                $server->push($fd['value'], json_encode($data));//发送消息
            }

            //记录聊天记录
            $record_data = [
                'user_id' => $info['mine']['id'],
                'friend_id' => $info['to']['id'],
                'group_id' => 0,
                'content' => $info['mine']['content'],
                'time' => time()
            ];
            $db->insert('chat_record', $record_data);
        } elseif ($info['to']['type'] == "group") {
            //群消息
            $data = [
                'username' => $info['mine']['username'],
                'avatar' => $info['mine']['avatar'],
                'id' => $info['to']['id'],
                'type' => $info['to']['type'],
                'content' => $info['mine']['content'],
                'cid' => 0,
                'mine' => false,//要通过判断是否是我自己发的
                'fromid' => $info['mine']['id'],
                'timestamp' => time() * 1000
            ];
            $list = $db->join('user as u', 'u.id = gm.user_id')
                ->where('group_id', $info['to']['id'])
                ->get('group_member as gm', null, 'u.id');

            // 异步推送
            TaskManager::async(function () use ($list, $user, $data) {
                $server = ServerManager::getInstance()->getSwooleServer();
                $db = MysqlPool::defer();

                foreach ($list as $k => $v) {
                    if ($v['id'] == $user['id']) {
                        continue;
                    }
                    $fd = Cache::getInstance()->get('uid' . $v['id']);//获取接受者fd
                    if ($fd == false) {
                        //这里说明该用户已下线，日后做离线消息用
                        $offline_message = [
                            'user_id' => $v['id'],
                            'data' => json_encode($data),
                        ];
                        //插入离线消息
                        $db->insert('offline_message', $offline_message);

                    } else {
                        $server->push($fd['value'], json_encode($data));//发送消息
                    }
                }

            });

            //记录聊天记录
            $record_data = [
                'user_id' => $info['mine']['id'],
                'friend_id' => 0,
                'group_id' => $info['to']['id'],
                'content' => $info['mine']['content'],
                'time' => time()
            ];
            $db->insert('chat_record', $record_data);
        }
    }

    public function addFriend()
    {

        $info = $this->caller()->getArgs();
        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_' . $info['token']);
        $user = json_decode($user, true);
        if ($user == null) {
            $data = [
                "type" => "token expire"
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db = MysqlPool::defer();

        $friend_id = $info['to_user_id'];
        $system_message_data = [
            'user_id' => $friend_id,//接受者
            'from_id' => $user['id'],//来源者
            'remark' => $info['remark'],
            'type' => 0,
            'group_id' => $info['to_friend_group_id'],
            'time' => time()
        ];
        $isFriend = $db->where('friend_id', $friend_id)->where('user_id', $user['id'])->getOne('friend');
        if ($isFriend) {
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg' => '对方已经是你的好友，不可重复添加'
            ];
            $this->response()->setMessage(json_encode($data));

            return;
        }
        if ($friend_id == $user['id']) {
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg' => '不能添加自己为好友'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db->insert('system_message', $system_message_data);
        //获取该接受者未读消息数量
        $count = $db->where('user_id', $friend_id)->where('`read`', 0)->count('system_message');
        $data = [
            "type" => "msgBox",
            "count" => $count
        ];

        //获取swooleServer
        $server = ServerManager::getInstance()->getSwooleServer();

        $fd = Cache::getInstance()->get('uid' . $friend_id);//获取接受者fd
        if ($fd == false) {
            //这里说明该用户已下线，日后做离线消息用
            $offline_message = [
                'user_id' => $friend_id,
                'data' => json_encode($data),
            ];
            //插入离线消息
            $db->insert('offline_message', $offline_message);

        } else {
            $server->push($fd['value'], json_encode($data));//发送消息
        }
    }


    public function addList()
    {


        $info = $this->caller()->getArgs();
        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_' . $info['token']);
        $user = json_decode($user, true);
        if ($user == null) {
            $data = [
                "type" => "token expire"
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db = MysqlPool::defer();

        $data = [
            "type" => "addList",
            "data" => [
                "type" => "friend",
                "avatar" => $user['avatar'],
                "username" => $user['nickname'],
                "groupid" => $info['fromgroup'],
                "id" => $user['id'],
                "sign" => $user['sign']
            ]
        ];
        //获取未读消息盒子数量
        $count = $db->where('user_id', $info['id'])->where('`read`', 0)->count('system_message');

        $data1 = [
            "type" => "msgBox",
            "count" => $count
        ];

        //获取swooleServer
        $server = ServerManager::getInstance()->getSwooleServer();

        $fd = Cache::getInstance()->get('uid' . $info['id']);//获取接受者fd

        if ($fd == false) {
            //这里说明该用户已下线，日后做离线消息用
            $offline_message = [
                'user_id' => $info['id'],
                'data' => json_encode($data1),
            ];
            //插入离线消息
            $db->insert('offline_message', $offline_message);

        } else {
            $server->push($fd['value'], json_encode($data));//发送消息
            $server->push($fd['value'], json_encode($data1));//发送消息
        }
    }

    public function refuseFriend()
    {
        $info = $this->caller()->getArgs();

        $db = MysqlPool::defer();
        $server = ServerManager::getInstance()->getSwooleServer();

        $id = $info['id'];//消息id
        $system_message = $db->where('id', $id)->getOne('system_message');
        //获取该接受者未读消息数量
        $count = $db->where('user_id', $system_message['from_id'])->where('`read`', 0)->count('system_message');
        $data = [
            "type" => "msgBox",
            "count" => $count
        ];

        $fd = Cache::getInstance()->get('uid' . $system_message['from_id']);//获取接受者fd

        if ($fd) {

            $server->push($fd['value'], json_encode($data));//发送消息

        }

    }

    public function joinNotify(){
        $info = $this->caller()->getArgs();

        $RedisPool = RedisPool::defer();

        $user = $RedisPool->get('User_token_' . $info['token']);
        $user = json_decode($user, true);
        if ($user == null) {
            $data = [
                "type" => "token expire"
            ];
            $this->response()->setMessage(json_encode($data));
        }

        $db = MysqlPool::defer();

        $groupid = $info['groupid'];
        $list = $db->where('group_id',$groupid)->get('group_member');
        $data = [
            "type" => "joinNotify",
            "data"  => [
                "system"    => true,
                "id"        => $groupid,
                "type"      => "group",
                "content"   => $user['nickname']."加入了群聊，欢迎下新人吧～"
            ]
        ];

        // 异步推送
        TaskManager::async(function () use ($list, $user, $data) {
            $server = ServerManager::getInstance()->getSwooleServer();

            foreach ($list as $k => $v) {
                $fd = Cache::getInstance()->get('uid' . $v['user_id']);//获取接受者fd
                if ($fd) {
                    $server->push($fd['value'], json_encode($data));//发送消息
                }

            }

        });
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\HttpController;


use App\Model\ChatRecordModel;
use App\Model\FriendGroupModel;
use App\Model\FriendModel;
use App\Model\GroupMemberModel;
use App\Model\GroupModel;
use App\Model\SystemMessageModel;
use App\Model\UserModel;
use EasySwoole\HttpAnnotation\AnnotationTag\Method;
use EasySwoole\HttpAnnotation\AnnotationTag\Param;
use EasySwoole\Redis\Redis;
use EasySwoole\RedisPool\RedisPool;

class User extends Base
{

    /**
     * @Param(name="username",required="",notEmpty="",alias="用户名")
     * @Param(name="password",required="",notEmpty="",alias="密码")
     * @Method(allow={POST})
     */
    public function login()
    {
        $params = $this->request()->getRequestParam();

        $UserModel = new UserModel();
        $user = $UserModel->getUserByUsername($params['username']);
        if (!$user) {
            return $this->writeJson(10001, '用户不存在');
        }

        if (!password_verify($params['password'], $user['password'])) {
            return $this->writeJson(10001, '密码输入不正确!');
        };

        $token = uniqid() . uniqid() . $user['id'];

        RedisPool::invoke(function (Redis $redis) use ($token, $user) {
            $redis->set('User_token_' . $token, json_encode($user), 36000);
        });

        $this->writeJson(200, '登录成功', ['token' => $token]);
    }

    /**
     * @Param(name="username",required="",notEmpty="",alias="用户名")
     * @Param(name="password",required="",notEmpty="",alias="密码")
     * @Param(name="nickname",required="",notEmpty="",alias="昵称")
     * @Param(name="code",required="",notEmpty="",alias="验证码")
     * @Method(allow={POST})
     */
    public function register()
    {
        $params = $this->request()->getRequestParam();

        $RedisPool = RedisPool::defer();
        $codeCache = $RedisPool->get('Code' . $params['key']);

        if ($codeCache != $params['code']) {
            return $this->writeJson(10001, '验证码错误', $codeCache);
        }

        $UserModel = new UserModel();
        $user = $UserModel->getUserByUsername($params['username']);
        if ($user) {
            return $this->writeJson(10001, '用户名已存在');
        }

        $data = [
            'avatar' => $params['avatar'],
            'nickname' => $params['nickname'],
            'username' => $params['username'],
            'password' => password_hash($params['password'], PASSWORD_DEFAULT),
            'sign' => $params['sign'],
        ];

        $user_id = $UserModel->insertUser($data);
        if (!$user_id) {
            return $this->json(10001, '注册失败');
        }

        $FriendGroupModel = new FriendGroupModel();
        $FriendGroupModel->insertFriendGroup([
            'user_id' => $user_id,
            'groupname' => '默认分组'
        ]);

        $GroupMemberModel = new GroupMemberModel();
        $GroupMemberModel->insertGroupMember([
            'user_id' => $user_id,
            'group_id' => 10001
        ]);

        return $this->writeJson(200, '注册成功');
    }

    /**
     *  初始化用户信息
     */
    public function userinfo()
    {
        $token = $this->request()->getRequestParam('token');

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_' . $token);

        if (!$user) {
            return $this->writeJson(10001, "获取用户信息失败");
        }

        $user = json_decode($user, true);

        $groups = GroupMemberModel::create()->alias('gm')->field('g.id,g.groupname,g.avatar')->join('`group` as g', 'g.id = gm.group_id')->where('gm.user_id', $user['id'])->all();
        $groups = $groups ? $groups->toArray(false, false) : [];
        foreach ($groups as $k => $v) {
            $groups[$k]['groupname'] = $v['groupname'] . '(' . $v['id'] . ')';
        }

        $friend_groups = FriendGroupModel::create()->field('id,groupname')->where('user_id', $user['id'])->all();
        $friend_groups = $friend_groups ? $friend_groups->toArray(false, false) : [];
        foreach ($friend_groups as $k => $v) {
            $list = FriendModel::create()->alias('f')
                ->field('u.nickname as username,u.id,u.avatar,u.sign,u.status')
                ->join('user as u', 'u.id = f.friend_id')
                ->where('f.user_id', $user['id'])
                ->where('f.friend_group_id', $v['id'])
                ->order('status', 'DESC')
                ->all();
            $friend_groups[$k]['list'] = $list ? $list->toArray(false, false) : [];
        }

        $data = [
            'mine' => [
                'username' => $user['nickname'] . '(' . $user['id'] . ')',
                'id' => $user['id'],
                'status' => $user['status'],
                'sign' => $user['sign'],
                'avatar' => $user['avatar']
            ],
            "friend" => $friend_groups,
            "group" => $groups
        ];

        return $this->writeJson(0, 'success', $data);

    }

    /**
     *  查找页面
     */
    public function find()
    {
        $params = $this->request()->getRequestParam();

        $type = isset($params['type']) ? $params['type'] : '';
        $wd = isset($params['wd']) ? $params['wd'] : '';
        $user_list = [];
        $group_list = [];

        $key = '%' . $wd . '%';

        switch ($type) {
            case "user" :
                $user_list = UserModel::create()->field('id,nickname,avatar')->where('id', $key, 'like', 'or')->where('nickname', $key, 'like', 'or')->where('username', $key, 'like', 'or')->all();
                $user_list = $user_list ? $user_list->toArray() : [];
                break;
            case "group" :
                $group_list = GroupModel::create()->field('id,groupname,avatar')->where('id', $key, 'like', 'or')->where('groupname', $key, 'like', 'or')->all();
                $group_list = $group_list ? $group_list->toArray() : [];
                break;
            default :
                break;
        }

        $this->render('find', ['user_list' => $user_list, 'group_list' => $group_list, 'type' => $type, 'wd' => $wd]);
    }

    /**
     *  加入群
     */
    public function joinGroup()
    {
        $params = $this->request()->getRequestParam();
        $token = $params['token'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_' . $token);

        if (!$user) {
            return $this->writeJson(10001, "获取用户信息失败");
        }
        $user = json_decode($user, true);


        $id = $params['groupid'];
        $isIn = GroupMemberModel::create()->where('group_id', $id)->where('user_id', $user['id'])->get();

        if ($isIn) {
            return $this->writeJson(10001, "您已经是该群成员");
        }
        $group = GroupModel::create()->where('id', $id)->get();
        $res = GroupMemberModel::create()->data(['group_id' => $id, 'user_id' => $user['id']])->save();
        if (!$res) {
            return $this->writeJson(10001, "加入群失败");
        }
        $data = [
            "type" => "group",
            "avatar" => $group['avatar'],
            "groupname" => $group['groupname'],
            "id" => $group['id']
        ];
        return $this->writeJson(200, "加入成功", $data);
    }


    /**
     * 创建群
     */
    public function createGroup()
    {
        if ($this->request()->getMethod() == 'POST') {
            $params = $this->request()->getRequestParam();
            $token = $params['token'];

            $RedisPool = RedisPool::defer();
            $user = $RedisPool->get('User_token_' . $token);

            if (!$user) {
                return $this->writeJson(10001, "获取用户信息失败");
            }

            $user = json_decode($user, true);

            $data = [
                'groupname' => $params['groupname'],
                'user_id' => $user['id'],
                'avatar' => $params['avatar']
            ];


            $group_id = GroupModel::create()->data($data)->save();

            $res_join = GroupMemberModel::create()->data(['group_id' => $group_id, 'user_id' => $user['id']])->save();
            if ($group_id && $res_join) {
                $data = [
                    "type" => "group",
                    "avatar" => $params['avatar'],
                    "groupname" => $params['groupname'],
                    "id" => $group_id
                ];
                return $this->writeJson(200, "创建成功！", $data);
            } else {
                return $this->writeJson(10001, "创建失败！");
            }
        } else {
            $this->render('create_group');

        }
    }

    /**
     *   获取群成员
     */
    public function groupMembers()
    {
        $params = $this->request()->getRequestParam();


        $id = $params['id'];
        $list = GroupMemberModel::create()->alias('gm')
            ->field('u.username,u.id,u.avatar,u.sign')
            ->join('user as u', 'u.id=gm.user_id')
            ->where('group_id', $id)
            ->all();
        if (!count($list)) {
            return $this->writeJson(10001, "获取群成员失败");
        }
        return $this->writeJson(0, "", ['list' => $list->toArray(false, false)]);
    }

    /**
     * 聊天记录
     */
    public function chatLog()
    {
        if ($this->request()->getMethod() == 'POST') {
            $params = $this->request()->getRequestParam();
            $token = $params['token'];

            $RedisPool = RedisPool::defer();
            $user = $RedisPool->get('User_token_' . $token);

            if (!$user) {
                return $this->writeJson(10001, "获取用户信息失败");
            }

            $user = json_decode($user, true);

            $id = $params['id'];
            $type = $params['type'];
            $page = $params['page'];


            if ($type == 'group') {
                $count = ChatRecordModel::create()->alias('cr')->field('u.nickname as username,u.id,u.avatar,time as timestamp,cr.content')->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.group_id', $id)->count();

                $list = ChatRecordModel::create()->alias('cr')->field('u.nickname as username,u.id,u.avatar,time as timestamp,cr.content')
                    ->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.group_id', $id)
                    ->order('time', 'DESC')
                    ->limit(($page - 1) * 20, 20)
                    ->all();
                $list = $list ? $list->toArray(false, false) : [];
            } else {
                $list = ChatRecordModel::create()->alias('cr')->field('u.nickname as username,u.id,u.avatar,time as timestamp,cr.content')
                    ->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.user_id', $user['id'])
                    ->where('cr.friend_id', $id)
                    ->where('cr.user_id', $id, '=', 'or')
                    ->where('cr.friend_id', $user['id'])
                    ->order('time', 'DESC')
                    ->limit(($page - 1) * 20, 20)
                    ->all();
                $list = $list ? $list->toArray(false, false) : [];

            }
            foreach ($list as $k => $v) {
                $list[$k]['timestamp'] = $v['timestamp'] * 1000;
            }
            $list['data'] = $list;
            $list['last_page'] = $count;
            return $this->writeJson(0, '', $list);
        } else {
            $params = $this->request()->getRequestParam();

            $id = $params['id'];
            $type = $params['type'];
            $this->render('chat_log', ['id' => $id, 'type' => $type]);

        }
    }

    /**
     * 退出登录
     */
    public function loginout()
    {
        $token = $this->request()->getRequestParam('token');

        $RedisPool = RedisPool::defer();
        $RedisPool->del('User_token_' . $token);

        $this->response()->redirect("/login");

    }

    /**
     * 消息盒子
     */
    public function messageBox()
    {
        $params = $this->request()->getRequestParam();
        $token = $params['token'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_' . $token);

        if (!$user) {
            return $this->writeJson(10001, "获取用户信息失败");
        }


        $user = json_decode($user, true);

        SystemMessageModel::create()->where('user_id', $user['id'])->update(['read' => 1]);

        $list = SystemMessageModel::create()->alias('sm')->field('sm.id,f.id as uid,f.avatar,f.nickname,sm.remark,sm.time,sm.type,sm.group_id,sm.status')->join('user as f', 'f.id = sm.from_id')
            ->where('user_id', $user['id'])
            ->order('id', 'DESC')
            ->all();

        $list = $list ? $list->toArray(false, false) : [];

        foreach ($list as $k => $v) {
            $list[$k]['time'] = $this->__time_tranx($v['time']);
        }

        $this->render('message_box', ['list' => $list]);
    }

    private function __time_tranx($the_time)
    {
        $now_time = time();
        $dur = $now_time - $the_time;
        if ($dur <= 0) {
            $mas = '刚刚';
        } else {
            if ($dur < 60) {
                $mas = $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    $mas = floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        $mas = floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) { //3天内
                            $mas = floor($dur / 86400) . '天前';
                        } else {
                            $mas = date("Y-m-d H:i:s", $the_time);
                        }
                    }
                }
            }
        }
        return $mas;
    }

    /**
     * 添加好友
     */
    public function addFriend()
    {
        $params = $this->request()->getRequestParam();
        $token = $params['token'];
        $id = $params['id'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_' . $token);

        if (!$user) {
            return $this->writeJson(10001, "获取用户信息失败");
        }


        $system_message = SystemMessageModel::create()->where('id', $id)->get();
        $isFriend = FriendModel::create()->where('user_id', $system_message['user_id'])->where('friend_id', $system_message['from_id'])->get();

        if ($isFriend) {
            return $this->writeJson(10001, '已经是好友了');
        }

        $data = [
            [
                'user_id' => $system_message['user_id'],
                'friend_id' => $system_message['from_id'],
                'friend_group_id' => $params['groupid']
            ],
            [
                'user_id' => $system_message['from_id'],
                'friend_id' => $system_message['user_id'],
                'friend_group_id' => $system_message['group_id']
            ]
        ];
        $res = FriendModel::create()->saveAll($data);
        if (!$res) {
            return $this->writeJson(10001, '添加失败');
        }

        SystemMessageModel::create()->where('id', $id)->update(['status' => 1]);
        $user = UserModel::create()->where('id', $system_message['from_id'])->get();

        $data = [
            "type" => "friend",
            "avatar" => $user['avatar'],
            "username" => $user['nickname'],
            "groupid" => $params['groupid'],
            "id" => $user['id'],
            "sign" => $user['sign']
        ];

        $system_message_data = [
            'user_id' => $system_message['from_id'],
            'from_id' => $system_message['user_id'],
            'type' => 1,
            'status' => 1,
            'time' => time()
        ];

        SystemMessageModel::create()->data($system_message_data)->save();

        return $this->writeJson(200, '添加成功', $data);
    }

    /**
     * 拒绝添加好友
     */
    public function refuseFriend()
    {
        $params = $this->request()->getRequestParam();

        $id = $params['id'];
        $system_message = SystemMessageModel::create()->where('id', $id)->get();

        $res = SystemMessageModel::create()->where('id', $id)->update(['status' => 2]);

        $data = [
            'user_id' => $system_message['from_id'],
            'from_id' => $system_message['user_id'],
            'type' => 1,
            'status' => 2,
            'time' => time()
        ];
        $res1 = SystemMessageModel::create()->data($data)->save();

        if ($res && $res1) {
            return $this->writeJson(200, "已拒绝");
        } else {
            return $this->writeJson(10001, "操作失败");
        }
    }
}

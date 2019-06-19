<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */

namespace App\HttpController;

use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;

/**
 * Class Index
 * @package App\HttpController
 */
class User extends Base
{

    /**
     *  初始化用户信息
     */
    public function userinfo()
    {
        $token =  $this->request()->getRequestParam('token');

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_'.$token);

        if (!$user) {
            return $this->writeJson(10001,"获取用户信息失败");
        }

        $user = json_decode($user,true);

        $db = MysqlPool::defer();

        $groups = $db->join('`group` as g','g.id = gm.group_id')->where('gm.user_id', $user['id'])->get('`group_member` as gm',null,'g.id,g.groupname,g.avatar');
        foreach ($groups as $k=>$v) {
            $groups[$k]['groupname'] = $v['groupname'].'('.$v['id'].')';
        }

        $friend_groups = $db->where('user_id', $user['id'])->get('friend_group',null,'id,groupname');
        foreach ($friend_groups as $k => $v) {
            $friend_groups[$k]['list'] = $db
                ->join('user as u','u.id = f.friend_id')
                ->where('f.user_id',$user['id'])
                ->where('f.friend_group_id',$v['id'])
                ->orderBy('status','DESC')
                ->get('friend as f',null,'u.nickname as username,u.id,u.avatar,u.sign,u.status');
        }

        $data = [
            'mine'      => [
                'username'  => $user['nickname'].'('.$user['id'].')',
                'id'        => $user['id'],
                'status'    => $user['status'],
                'sign'      => $user['sign'],
                'avatar'    => $user['avatar']
            ],
            "friend"    => $friend_groups,
            "group"     => $groups
        ];

        return $this->writeJson(0,'success',$data);

   }

    /**
     *  查找页面
     */
    public function find()
    {
        $params =  $this->request()->getRequestParam();

        $type = isset($params['type']) ?$params['type'] :'';
        $wd = isset($params['wd'])? $params['wd']:'';
        $user_list = [];
        $group_list = [];
        $db = MysqlPool::defer();


        $key = '%'.$wd.'%';

        switch ($type) {
            case "user" :
                $user_list = $db->whereOr('id',$key,'like')->whereOr('nickname',$key,'like')->whereOr('username',$key,'like')->get('`user`',null,'id,nickname,avatar');
                break;
            case "group" :
                $group_list = $db->whereOr('id',$key,'like')->whereOr('groupname',$key,'like')->get('`group`',null,'id,groupname,avatar');
                break;
            default :
                break;
        }

        $this->render('find', ['user_list' => $user_list,'group_list' => $group_list,'type' => $type,'wd' => $wd]);
    }

    /**
     *  加入群
     */
    public function joinGroup()
    {
        $params =  $this->request()->getRequestParam();
        $token =  $params['token'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_'.$token);

        if (!$user) {
            return $this->writeJson(10001,"获取用户信息失败");
        }
        $user = json_decode($user,true);

        $db = MysqlPool::defer();

        $id = $params['groupid'];
        $isIn = $db->where('group_id',$id)->where('user_id', $user['id'])->get('group_member');

        if ($isIn) {
            return $this->writeJson(10001,"您已经是该群成员");
        }
        $group = $db->where('id',$id)->get('`group`');
        $res = $db->insert('group_member',['group_id' => $id,'user_id' => $user['id']]);
        if (!$res) {
            return $this->writeJson(10001,"加入群失败");
        }
        $data = [
            "type" => "group",
            "avatar"    => $group['avatar'],
            "groupname" =>$group['groupname'],
            "id"        =>$group['id']
        ];
        return $this->writeJson(200,"加入成功",$data);
    }


    /**
     * 创建群
     */
    public function createGroup()
    {
        if($this->request()->getMethod() == 'POST'){
            $params =  $this->request()->getRequestParam();
            $token =  $params['token'];

            $RedisPool = RedisPool::defer();
            $user = $RedisPool->get('User_token_'.$token);

            if (!$user) {
                return $this->writeJson(10001,"获取用户信息失败");
            }

            $user = json_decode($user,true);

            $data = [
                'groupname' => $params['groupname'],
                'user_id'   => $user['id'],
                'avatar'    => $params['avatar']
            ];

            $db = MysqlPool::defer();

            $group_id = $db->insert('`group`',$data);

            if ($group_id){
                $group_id = $db->getInsertId();
            }

            $res_join = $db->insert('group_member',['group_id' => $group_id,'user_id' => $user['id']]);
            if ($group_id && $res_join) {
                $data = [
                    "type" => "group",
                    "avatar"    => $params['avatar'],
                    "groupname" => $params['groupname'],
                    "id"        => $group_id
                ];
                return $this->writeJson(200,"创建成功！",$data);
            } else {
                return $this->writeJson(10001,"创建失败！");
            }
        }else{
            $this->render('create_group');

        }
    }

    /**
     *   获取群成员
     */
    public function groupMembers()
    {
        $params =  $this->request()->getRequestParam();

        $db = MysqlPool::defer();

        $id =  $params['id'];
        $list = $db
            ->join('user as u','u.id=gm.user_id')
            ->where('group_id', $id)
            ->get('group_member as gm',null,'u.username,u.id,u.avatar,u.sign');
        if (!count($list)) {
            return $this->writeJson(10001,"获取群成员失败");
        }
        return $this->writeJson(0,"",['list' => $list]);
    }

    /**
     * 聊天记录
     */
    public function chatLog()
    {
        if($this->request()->getMethod() == 'POST'){
            $params =  $this->request()->getRequestParam();
            $token =  $params['token'];

            $RedisPool = RedisPool::defer();
            $user = $RedisPool->get('User_token_'.$token);

            if (!$user) {
                return $this->writeJson(10001,"获取用户信息失败");
            }

            $user = json_decode($user,true);

            $id = $params['id'];
            $type = $params['type'];
            $page = $params['page'];

            $db = MysqlPool::defer();

            if ($type == 'group') {
                $count = $db->join('user as u','u.id = cr.user_id')
                    ->where('cr.group_id',$id)->count('chat_record as cr',null,'u.nickname as username,u.id,u.avatar,time as timestamp,cr.content');

                $list = $db
                    ->join('user as u','u.id = cr.user_id')
                    ->where('cr.group_id',$id)
                    ->orderBy('time','DESC')
                    ->get('chat_record as cr',[($page-1)*20,20],'u.nickname as username,u.id,u.avatar,time as timestamp,cr.content');
            } else {
                $list = $db
                    ->join('user as u','u.id = cr.user_id')
                    ->where('cr.user_id',$user['id'])
                    ->where('cr.friend_id',$id)
                    ->whereOr('cr.user_id',$id)
                    ->where('cr.friend_id',$user['id'])
                    ->orderBy('time','DESC')
                    ->get('chat_record as cr',[($page-1)*20,20],'u.nickname as username,u.id,u.avatar,time as timestamp,cr.content');

            }
            foreach ($list as $k=>$v){
                $list[$k]['timestamp'] = $v['timestamp'] * 1000;
            }
            $list['data'] = $list;
            $list['last_page'] = $count;
            return $this->writeJson(0,'',$list);
        }else{
            $params =  $this->request()->getRequestParam();

            $id = $params['id'];
            $type = $params['type'];
            $this->render('chat_log',['id' => $id,'type' => $type]);

        }
    }

    /**
     * 退出登录
     */
    public function loginout()
    {
        $token =  $this->request()->getRequestParam('token');

        $RedisPool = RedisPool::defer();
        $RedisPool->del('User_token_'.$token);

        $this->response()->redirect("/login");

    }

    /**
     * 消息盒子
     */
    public function messageBox()
    {
        $params =  $this->request()->getRequestParam();
        $token =  $params['token'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_'.$token);

        if (!$user) {
            return $this->writeJson(10001,"获取用户信息失败");
        }

        $db = MysqlPool::defer();

        $user = json_decode($user,true);

        $db->where('user_id',$user['id'])->update('system_message',['read' => 1]);

        $list = $db->join('user as f','f.id = sm.from_id')
            ->where('user_id',$user['id'])
            ->orderBy('id', 'DESC')
            ->get('system_message as sm',50,'sm.id,f.id as uid,f.avatar,f.nickname,sm.remark,sm.time,sm.type,sm.group_id,sm.status');

        foreach ($list as $k => $v) {
            $list[$k]['time'] = $this->__time_tranx($v['time']);
        }

        $this->render('message_box',['list' => $list]);
    }

    private function  __time_tranx($the_time)
    {
        $now_time = time();
        $dur = $now_time - $the_time;
        if ($dur <= 0) {
            $mas =  '刚刚';
        } else {
            if ($dur < 60) {
                $mas =  $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    $mas =  floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        $mas =  floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) { //3天内
                            $mas =  floor($dur / 86400) . '天前';
                        } else {
                            $mas =  date("Y-m-d H:i:s",$the_time);
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
        $params =  $this->request()->getRequestParam();
        $token =  $params['token'];
        $id = $params['id'];

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_'.$token);

        if (!$user) {
            return $this->writeJson(10001,"获取用户信息失败");
        }

        $db = MysqlPool::defer();

        $system_message = $db->where('id',$id)->getOne('system_message');
        $isFriend = $db->where('user_id',$system_message['user_id'])->where('friend_id',$system_message['from_id'])->get('friend');

        if ($isFriend) {
            return $this->writeJson(10001,'已经是好友了');
        }

        $data = [
            [
                'user_id' => $system_message['user_id'],
                'friend_id' =>$system_message['from_id'],
                'friend_group_id' => $params['groupid']
            ],
            [
                'user_id' =>$system_message['from_id'],
                'friend_id' => $system_message['user_id'],
                'friend_group_id' => $system_message['group_id']
            ]
        ];
        $res = $db->insertMulti('friend',$data);
        if (!$res) {
            return $this->writeJson(10001,'添加失败');
        }

        $db->where('id',$id)->update('system_message',['status' => 1]);
        $user = $db->where('id',$system_message['from_id'])->getOne('user');

        $data = [
            "type"  => "friend",
            "avatar"    => $user['avatar'],
            "username" => $user['nickname'],
            "groupid" => $params['groupid'],
            "id"        => $user['id'],
            "sign"    => $user['sign']
        ];

        $system_message_data = [
            'user_id'   => $system_message['from_id'],
            'from_id'   => $system_message['user_id'],
            'type'      => 1,
            'status'    => 1,
            'time'      => time()
        ];

        $db->insert('system_message',$system_message_data);

        return $this->writeJson(200,'添加成功',$data);
    }

    /**
     * 拒绝添加好友
     */
    public function refuseFriend()
    {
        $params =  $this->request()->getRequestParam();

        $id = $params['id'];
        $db = MysqlPool::defer();
        $system_message = $db->where('id',$id)->getOne('system_message');

        $res =  $db->where('id',$id)->update('system_message',['status' => 2]);

        $data = [
            'user_id'   => $system_message['from_id'],
            'from_id'   => $system_message['user_id'],
            'type'      => 1,
            'status'    => 2,
            'time'      => time()
        ];
        $res1 = $db->insert('system_message',$data);

        if ($res && $res1){
            return $this->writeJson(200,"已拒绝");
        } else {
            return $this->writeJson(10001,"操作失败");
        }
    }
}

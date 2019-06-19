<?php
/**
 * Created by PhpStorm.
 * User: Double-jin
 * Date: 2019/6/19
 * Email: 605932013@qq.com
 */


namespace App\HttpController;

use App\Model\FriendGroupModel;
use App\Model\GroupMemberModel;
use App\Model\UserModel;
use App\Utility\Pool\RedisObject;
use App\Utility\Pool\RedisPool;

use EasySwoole\Validate\Validate;
use EasySwoole\VerifyCode\Conf;


/**
 * Class Index
 * @package App\HttpController
 */
class Index extends Base
{

    public function test(){
        $this->render('websocket');
    }

    public function index()
    {
        $token =  $this->request()->getRequestParam('token');

        $RedisPool = RedisPool::defer();
        $user = $RedisPool->get('User_token_'.$token);

        if (!$user) {
            $this->response()->redirect("/login");
        }
        $user = json_decode($user,true);
        $hostName = 'ws://es-chat.cc:9501';
        $this->render('index', [
            'server' => $hostName,'token'=>$token,'user'=>$user
        ]);
    }

    /**
     * 登录
     */
    public function login()
    {
        if ($this->request()->getMethod() == 'POST') {
            $validate = new Validate();
            $validate->addColumn('username')->required('用户名必填');
            $validate->addColumn('password')->required('密码必填');

            if ($this->validate($validate)) {
                $params = $this->request()->getRequestParam();

                $UserModel = new UserModel();
                $user = $UserModel->getUserByUsername($params['username']);
                if (!$user) {
                    return $this->writeJson(10001,'用户不存在');
                }

                if(!password_verify ( $params['password'] , $user['password'])){
                    return $this->writeJson(10001,'密码输入不正确!');
                };

                $token = uniqid().uniqid().$user['id'];

                RedisPool::invoke(function (RedisObject $redis)use ($token,$user){
                    $redis->set('User_token_'.$token,json_encode($user),36000);
                });

                return $this->writeJson(200, '登录成功',['token'=>$token]);
            } else {
                return $this->writeJson(10001, $validate->getError()->__toString(), 'fail');
            }


        } else {
            $this->render('login');
        }
    }

    /**
     * 注册
     */
    public function register()
    {
        if ($this->request()->getMethod() == 'POST') {

            $validate = new Validate();
            $validate->addColumn('username')->required('用户名必填');
            $validate->addColumn('password')->required('密码必填');
            $validate->addColumn('nickname')->required('昵称必填');
            $validate->addColumn('code')->required('验证码必填');

            if ($this->validate($validate)) {
                $params = $this->request()->getRequestParam();

                $RedisPool = RedisPool::defer();
                $codeCache = $RedisPool->get('Code'.$params['key']);

                if ($codeCache != $params['code']){
                    return $this->writeJson(10001, '验证码错误',$codeCache);
                }

                $UserModel = new UserModel();
                $user = $UserModel->getUserByUsername($params['username']);
                if ($user) {
                    return $this->writeJson(10001,'用户名已存在');
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
                    return $this->json(10001,'注册失败');
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
            } else {
                return $this->writeJson(10001, $validate->getError()->__toString(), 'fail');
            }
        } else {
            $code_hash = uniqid().uniqid();
            $this->render('register',
                ['code_hash'=>$code_hash]
                );
        }
    }

    /**
     * 验证码
     */
    public function getCode(){
        $params = $this->request()->getRequestParam();
        $key = $params['key'];

        $config = new Conf();
        $code = new \EasySwoole\VerifyCode\VerifyCode($config);
        $num = mt_rand(000,999);

        RedisPool::invoke(function (RedisObject $redis)use ($key,$num){
            $redis->set('Code'.$key,$num,1000);
        });

        $this->response()->withHeader('Content-Type','image/png');
        $this->response()->write($code->DrawCode($num)->getImageByte());
    }

    /**
     * 上传图片
     */
    public function upload()
    {

        $request = $this->request();
        $img_file = $request->getUploadedFile('file');

        if (!$img_file) {
            $this->writeJson(500, '请选择上传的文件');
        }

        if ($img_file->getSize() > 1024 * 1024 * 5) {
            $this->writeJson(500, '图片不能大于5M！');
        }

        $MediaType = explode("/", $img_file->getClientMediaType());
        $MediaType = $MediaType[1] ?? "";
        if (!in_array($MediaType, ['png', 'jpg', 'gif', 'jpeg', 'pem', 'ico'])) {
            $this->writeJson(500, '文件类型不正确！');
        }

        $path =  '/Static/upload/';
        $dir =  EASYSWOOLE_ROOT.'/Static/upload/';
        $fileName = uniqid().$img_file->getClientFileName();

        if(!is_dir($dir)) {
            mkdir($dir, 0777 , true);
        }

        $flag = $img_file->moveTo($dir.$fileName);

        $data = [
            'name' => $fileName,
            'src' => $path.$fileName,
        ];

        if($flag) {
            $this->writeJson(0, '上传成功', $data);
        } else {
            $this->writeJson(500, '上传失败');
        }

    }
}

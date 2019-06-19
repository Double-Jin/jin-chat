<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>查找</title>
    <link rel="stylesheet" href="/Static/asset/layuiv2/css/layui.css" media="all">
</head>
<body>
<div class="layui-row">
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            <li <?php if ($type == 'user' || $type == ''):?>class="layui-this"<?php endif;?> >找人</li>
            <li <?php if ($type == 'group'):?>class="layui-this"<?php endif;?> >找群</li>
        </ul>
        <div class="layui-tab-content">
            <div class="layui-tab-item  <?php if ($type == 'user' || $type == ''):?>layui-show<?php endif;?>   ">
                <div>
                    <input  style="float: left;width: 90%;" type="text" id="user-wd" required lay-verify="required" placeholder="请输入ID/昵称" autocomplete="off" class="layui-input"
                            <?php if ($type == 'user'):?>value="<?=$this->e($wd);?>"<?php endif;?>
                    >
                    <button onclick="findUser()" style="float: right;width: 10%"  class="layui-btn">
                        <i class="layui-icon">&#xe615;</i> 查找
                    </button>
                </div>
                <div class="layui-row">
                    <?php foreach($user_list as $k=>$v): ?>
                        <div class="layui-col-md4" style="border-bottom: 1px solid #f6f6f6">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <?=$this->e($v['nickname'])?>(<?=$this->e($v['id'])?>)
                            </div>
                            <div class="layui-card-body">
                                <img style="width: 75px;height: 75px;object-fit: cover;" src="<?=$this->e($v['avatar'])?>" alt="">
                                <button onclick="addFriend(<?=$this->e($v['id'])?>,'<?=$this->e($v['nickname'])?>','<?=$this->e($v['avatar'])?>')" style="float: right" class="layui-btn layui-btn-normal layui-btn-sm">
                                    <i class="layui-icon">&#xe654;</i> 添加
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="layui-tab-item  <?php if ($type == 'group'):?>layui-show<?php endif;?>  ">
                <div>
                    <input  style="float: left;width: 80%;" type="text" id="group-wd" required lay-verify="required" placeholder="请输入群Id/群名称" autocomplete="off" class="layui-input"
                            <?php if ($type == 'group'):?>value="<?=$this->e($wd);?>"<?php endif;?>

                    >
                    <button onclick="createGroup()" style="float: right;width: 10%"  class="layui-btn layui-btn-warm">
                        <i class="layui-icon">&#xe654;</i> 创建群
                    </button>
                    <button onclick="findGroup()" style="float: left;width: 10%;margin-left: 0"  class="layui-btn">
                        <i class="layui-icon">&#xe615;</i> 查找群
                    </button>
                </div>

                <?php foreach($group_list as $k=>$v): ?>

                    <div class="layui-col-md4" style="border-bottom: 1px solid #f6f6f6">
                        <div class="layui-card">
                            <div class="layui-card-header"> <?=$this->e($v['groupname'])?>(<?=$this->e($v['id'])?>)</div>
                            <div class="layui-card-body">
                                <img style="width: 75px;height: 75px;object-fit: cover;" src="<?=$this->e($v['avatar'])?>" alt="">
                                <button onclick="joinGroup(<?=$this->e($v['id'])?>)" style="float: right" class="layui-btn layui-btn-normal layui-btn-sm">
                                    <i class="layui-icon">&#xe654;</i> 加入
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>

            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="/Static/asset/layui/layui.js"></script>
<script>
    var layer;
    var storage=window.localStorage;

    layui.use('layer', function(){
        layer = layui.layer;
    });
    layui.use('element', function(){
        var element = layui.element;
    });
    function findUser() {
        wd = $('#user-wd').val();
        window.location.href="/User/find?type=user&wd="+wd
    }
    function findGroup() {
        wd = $('#group-wd').val();
        window.location.href="/User/find?type=group&wd="+wd
    }

    function addFriend(id,nickname,avatar) {
        layui.use('layim', function(layim){
            layim.add({
                type: 'friend' //friend：申请加好友、group：申请加群
                ,username: nickname //好友昵称，若申请加群，参数为：groupname
                ,avatar: avatar //头像
                ,submit: function(group, remark, index){ //一般在此执行Ajax和WS，以通知对方
                    var data = {type:"addFriend",to_user_id:id,to_friend_group_id:group,remark:remark,token:storage.getItem('token')}
                    parent.sendMessage(parent.socket,JSON.stringify(data))
                    console.log(group); //获取选择的好友分组ID，若为添加群，则不返回值
                    console.log(remark); //获取附加信息
                    layer.close(index); //关闭改面板
                }
            });
        });
    }

    function joinGroup(id) {
        $.ajax({
            url:"/User/joinGroup",
            type:"post",
            data:{groupid:id,token:storage.getItem('token')},
            dataType:"json",
            success:function (res) {
                console.log(res)
                if(res.code == 200){
                    layer.msg(res.msg)
                    parent.layui.layim.addList(res.data)
                    //加入群成功，给群内所有在线用户发送入群通知
                    var joinNotify = {type:"joinNotify","groupid":id,token:storage.getItem('token')}
                    parent.sendMessage(parent.socket,JSON.stringify(joinNotify));
                }else{
                    layer.msg(res.msg,function () {})
                }
            },
            error:function () {
                layer.msg("网络繁忙",function(){});
            }
        })
    }

    function createGroup() {
        layer.open({
            type: 2,
            title: '创建群',
            shadeClose: true,
            shade: 0.8,
            area: ['40%', '70%'],
            content: '/User/createGroup' //iframe的url
        });
    }
</script>
</body>
</html>
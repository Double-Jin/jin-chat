<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>消息盒子</title>
    <link rel="stylesheet" href="/Static/asset/layuiv2/css/layui.css" media="all">
    <link rel="stylesheet" href="/Static/asset/bootstrap-3.3.7/css/bootstrap.css">
    <style>
        .layim-msgbox{margin: 15px;}
        .layim-msgbox li{position: relative; margin-bottom: 10px; padding: 0 130px 10px 60px; padding-bottom: 10px; line-height: 22px; border-bottom: 1px dotted #e2e2e2;}
        .layim-msgbox .layim-msgbox-tips{margin: 0; padding: 10px 0; border: none; text-align: center; color: #999;}
        .layim-msgbox .layim-msgbox-system{padding: 0 10px 10px 10px;}
        .layim-msgbox li p span{padding-left: 5px; color: #999;}
        .layim-msgbox li p em{font-style: normal; color: #FF5722;}

        .layim-msgbox-avatar{position: absolute; left: 0; top: 0; width: 50px; height: 50px;}
        .layim-msgbox-user{padding-top: 5px;}
        .layim-msgbox-content{margin-top: 3px;}
        .layim-msgbox .layui-btn-small{padding: 0 15px; margin-left: 5px;}
        .layim-msgbox-btn{position: absolute; right: 0; top: 12px; color: #999;}
    </style>
</head>
<body>
<ul class="layim-msgbox" id="LAY_view">
    <?php foreach($list as $k=>$v): ?>
        <?php if ($v['type'] == 0):?>
            <li data-uid="<?=$this->e($v['uid'])?>" data-fromgroup="<?=$this->e($v['group_id'])?>">
                <a href="javascript:;">
                    <img style="width: 40px;height: 40px" src="/<?=$this->e($v['avatar'])?>" class="layui-circle layim-msgbox-avatar"></a>
                <p class="layim-msgbox-user">
                    <a href="javascript:;" ><?=$this->e($v['nickname'])?></a>
                    <span><?=$this->e($v['time'])?></span></p>
                <p class="layim-msgbox-content">申请添加你为好友
                    <span>附言: <?=$this->e($v['remark'])?></span></p>
                <p class="layim-msgbox-btn">
                    <?php if ($v['status'] == 0):?>
                    <button class="layui-btn layui-btn-small" onclick='agree(<?=$this->e($v['id'])?>,$(this),"<?=$this->e($v['avatar'])?>","<?=$this->e($v['nickname'])?>")'>同意</button>
                    <button class="layui-btn layui-btn-small layui-btn-primary" onclick="refuse(<?=$this->e($v['id'])?>,$(this))">拒绝</button>
                    <?php else:?>
                    <span>已<?php echo $v['status'] == 1 ? '同意' : '拒绝'?></span>
                    <?php endif;?>
                </p>
            </li>
        <?php else:?>
        <li class="layim-msgbox-system">
                <p>
                    <em>系统：</em><?=$this->e($v['nickname'])?> 已经<?php echo $v['status'] == 1 ? '同意' : '拒绝'?>你的好友申请
                    <span><?=$this->e($v['time'])?></span></p>
            </li>
        <?php endif;?>
    <?php endforeach ?>

</ul>
<!--<div style="text-align:center">-->
<!--    {!! $list->links() !!}-->
<!--</div>-->
<script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="/Static/asset/layui/layui.js"></script>
<script>
    var layer;
    layui.use('layer', function(){
        layer = layui.layer;
    });
    function refuse(id,obj) {
        $.ajax({
            url : "/User/refuseFriend",
            type: "post",
            data: {id:id},
            dataType:"json",
            success:function (res) {
                if (res.code == 200){
                    layer.msg(res.msg)
                    //如果成功了，发出socket消息，通知被拒绝者
                    obj.parent().html('<span>已拒绝</span>');
                    parent.sendMessage(parent.socket,JSON.stringify({type:"refuseFriend",id:id}))
                }else{
                    layer.msg(res.msg,function(){})
                }
            },
            error: function () {
                layer.msg("网络繁忙",function(){})
            }
        });
    }
    function agree(id,obj,avatar,nickname){
        var storage=window.localStorage;

        parent.layui.layim.setFriendGroup({
            type: 'friend'
            ,username: nickname //好友昵称，若申请加群，参数为：groupname
            ,avatar: avatar //头像
            ,group: parent.layui.layim.cache().friend //获取好友列表数据
            ,submit: function(group, index){
                parent.layer.close(index); //关闭改面板
                $.ajax({
                    url:"/User/addFriend",
                    type:"post",
                    data:{id:id,groupid:group,token:storage.getItem('token')},
                    dataType:"json",
                    success:function (res) {
                        console.log(res)
                        //执行添加好友操作
                        if (res.code == 200){
                            uid = obj.parents('li').attr('data-uid');
                            fromgroup = obj.parents('li').attr('data-fromgroup');
                            console.log(uid)
                            console.log(fromgroup)
                            parent.sendMessage(parent.socket, JSON.stringify({type:"addList",id:uid,fromgroup:fromgroup,token:storage.getItem('token')}))//通知对方，我已同意，把我加入到对方好友列表并添加消息提醒
                            parent.layui.layim.addList(res.data); //将刚通过的好友追加到好友列表
                            obj.parent().html('<span>已同意</span>');
                        } else {
                            layer.msg(res.msg,function(){});
                        }
                    },
                    error:function () {
                        layer.msg("网络繁忙",function(){});
                    }
                })
            }
        });
    }
</script>
</body>
</html>
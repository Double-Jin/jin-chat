<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>注册</title>
    <link rel="stylesheet" href="/Static/asset/layuiv2/css/layui.css" media="all">
</head>
<body>
<div class="layui-row">
    <div class="layui-col-xs12 layui-col-sm12 layui-col-md12 layui-col-lg6" style="padding-top: 50px;padding-right: 20px;">
        <form class="layui-form " action="">

            <div class="layui-form-item">
                <label class="layui-form-label">用户名</label>
                <div class="layui-input-block">
                    <input type="text" name="username" required  lay-verify="required" placeholder="请输入用户名" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">密码</label>
                <div class="layui-input-inline">
                    <input type="password" name="password" required lay-verify="required" placeholder="请输入密码" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">昵称</label>
                <div class="layui-input-block">
                    <input type="text" name="nickname" required  lay-verify="required" placeholder="请输入昵称" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">头像</label>
                <div class="layui-input-block">
                    <button type="button" class="layui-btn" id="avatar">
                        <i class="layui-icon">&#xe62f;</i>  上传头像
                    </button><br>
                    <img id="yl" src="" style="display: none;width: 100px;">
                    <input type="hidden" name="avatar">
                </div>
            </div>
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">个性签名</label>
                <div class="layui-input-block">
                    <textarea name="sign" placeholder="请输入个性签名" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">验证码</label>
                <div class="layui-input-block">
                    <input type="text" style="width: 200px;float: left;" name="code" required  lay-verify="required" placeholder="请输入验证码" autocomplete="off" class="layui-input">
                    <img style="width: 150px;height: 40px;margin-left: 10px" src="http://127.0.0.1:9501/getCode?key=<?php echo $code_hash;?>" alt="">
                    <input type="hidden" name="key" value="<?php echo $code_hash;?>">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit lay-filter="formDemo">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>

        </form>
    </div>
</div>
<script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="/Static/asset/layuiv2/layui.js"></script>
<script>
    url = window.location.host;
    // url = url.split(':')[0];
    url = 'http://' + url;

    layui.use('upload', function(){
        var upload = layui.upload;
        //执行实例
        upload.render({
            elem: '#avatar'
            ,url: '/upload'
            ,before: function(obj){ //obj参数包含的信息，跟 choose回调完全一致，可参见上文。
                layer.load(); //上传loading
            }
            ,done: function(res, index, upload){
                layer.closeAll('loading'); //关闭loading
                if (res.code == 0){
                    $('#yl').attr('src',url + res.data.src).show();
                    $('input[name="avatar"]').val(res.data.src);
                }else{
                    layer.msg(res.msg,function(){});
                }

            }
            ,error: function(index, upload){
                layer.closeAll('loading'); //关闭loading
                layer.msg("网络繁忙",function(){});
            }
        });
    });
    //Demo
    layui.use('form', function(){
        var form = layui.form;
        //监听提交
        form.on('submit(formDemo)', function(data){
            $.ajax({
                url:"/user/register",
                data:data.field,
                type:"post",
                dataType:"json",
                success:function(res){
                    if (res.code == 200) {
                        layer.msg(res.msg);
                        setTimeout(function () {
                            parent.location.reload();
                        },1500);
                    }else{
                        layer.msg(res.msg,function(){});
                    }
                },
                error:function(){
                    layer.msg('网络繁忙',function(){});
                }
            });
            return false;
        });
    });
</script>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>聊天记录</title>
    <link rel="stylesheet" href="/Static/asset/layui/css/layui.css" media="all">
    <style>
        body .layim-chat-main{height: auto;}
    </style>
</head>
<body>
<div class="layim-chat-main">
    <ul id="LAY_view">
    </ul>
</div>
<div id="LAY_page" style="margin: 0 10px;"></div>
<textarea title="消息模版" id="LAY_tpl" style="display:none;"><%# layui.each(d.data, function(index, item){
  if(item.id == parent.layui.layim.cache().mine.id){ %>
    &lt;li class="layim-chat-mine"&gt;&lt;div class="layim-chat-user"&gt;&lt;img src="<% item.avatar %>"&gt;&lt;cite&gt;&lt;i&gt;<% layui.data.date(item.timestamp) %>&lt;/i&gt;<% item.username %>&lt;/cite&gt;&lt;/div&gt;&lt;div class="layim-chat-text"&gt;<% layui.layim.content(item.content) %>&lt;/div&gt;&lt;/li&gt;
    <%# } else { %>
    &lt;li&gt;&lt;div class="layim-chat-user"&gt;&lt;img src="<% item.avatar %>"&gt;&lt;cite&gt;<% item.username %>&lt;i&gt;<% layui.data.date(item.timestamp) %>&lt;/i&gt;&lt;/cite&gt;&lt;/div&gt;&lt;div class="layim-chat-text"&gt;<% layui.layim.content(item.content) %>&lt;/div&gt;&lt;/li&gt;
    <%# }
  }); %>
</textarea>
<script type="text/javascript" src="http://apps.bdimg.com/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="/Static/asset/layui/layui.js"></script>
<script>
    layui.use(['layim', 'laytpl','laypage'], function(){
        var layim = layui.layim
            ,layer = layui.layer
            ,laytpl = layui.laytpl
            ,$ = layui.jquery
            ,laypage = layui.laypage
            ,mark = 0;

        function getData(page = 1){
            var storage=window.localStorage;

            //实际使用时，下述的res一般是通过Ajax获得，而此处仅仅只是演示数据格式
            $.ajax({
                url:"/User/chatLog",
                type:"post",
                data:{id:"<?=$this->e($id)?>",type:"<?=$this->e($type)?>",page:page,token:storage.getItem('token')},
                dataType:"json",
                success:function(res){
                    console.log(res)
                    if (mark == 0){
                        laypage({
                            cont:$('#LAY_page'),
                            pages:res.data.last_page,//总页数
                            curr:1,//当前页
                            groups:5,//连续分页数
                            jump: function(obj, first){
                                //得到了当前页，用于向服务端请求对应数据
                                var curr = obj.curr;
                                console.log(curr)
                                getData(curr)
                            }
                        })
                    }
                    mark = 1;
                    laytpl.config({
                        open: '<%',
                        close: '%>'
                    });
                    var html = laytpl(LAY_tpl.value).render({
                        data: res.data.data
                    });
                    $('#LAY_view').html(html);

                },
                error:function(){

                }
            })
        }
        getData(1);


        //开始请求聊天记录
        var param =  location.search; //获得URL参数。该窗口url会携带会话id和type，他们是你请求聊天记录的重要凭据



    });
</script>
</body>
</html>
# Template

## 实现原理
注册N个自定义进程做渲染进程，进程内关闭协程环境，并监听UNIXSOCK，客户端调用携程的client客户端发送数据给进程渲染，进程再返回结果给客户端，用来解决PHP模板引擎协程安全问题。可以实现渲染接口，根据自己的喜好引入smarty或者是blade或者是其他引擎.

## 引入模板引擎类

下面列举一些常用的模板引擎包方便引入使用:

### [smarty/smarty](https://github.com/smarty-php/smarty)

Smarty是一个使用PHP写出来的模板引擎,是目前业界最著名的PHP模板引擎之一

> composer require smarty/smarty=~3.1


### [league/plates](https://github.com/thephpleague/plates)

使用原生PHP语法的非编译型模板引擎，更低的学习成本和更高的自由度

> composer require league/plates=3.*

### [duncan3dc/blade](https://github.com/duncan3dc/blade)

Laravel框架使用的模板引擎

> composer require duncan3dc/blade=^4.5

### [topthink/think-template](https://github.com/top-think/think-template)

ThinkPHP框架使用的模板引擎

> composer require topthink/think-template


## 渲染模板

选择一个心仪的模板引擎，并实现RenderInterface接口，当进程收到一条渲染指令时，会调用该实现类的render方法进行渲染，渲染结束后调用afterRender方法，可在此处进行变量释放清理等操作，以Smarty引擎为例，创建一个渲染器， 并渲染的最小例子如下

### 用Smarty作为渲染器

```php
<?php

use EasySwoole\Template\Config;
use EasySwoole\Template\Render;
use EasySwoole\Template\RenderInterface;
use \SmartyException;
use \Throwable;

class SmartyRender implements RenderInterface
{
    private $engine;

    function __construct($viewsDir, $cacheDir = '')
    {
        if ($cacheDir == '') {
            $cacheDir = sys_get_temp_dir();
        }
        $this->engine = new \Smarty();
        $this->engine->setTemplateDir($viewsDir);
        $this->engine->setCacheDir($cacheDir);
        $this->engine->setCompileDir($cacheDir);
    }

    /**
     * 模板渲染
     * @param string $template
     * @param array $data
     * @param array $options
     * @return string|null
     * @throws SmartyException
     */
    public function render(string $template, array $data = [], array $options = []): ?string
    {
        foreach ($data as $key => $item) {
            $this->engine->assign($key, $item);
        }
        return $this->engine->fetch($template);
    }

    /**
     * 每次渲染完成都会执行清理
     * @param string|null $result
     * @param string $template
     * @param array $data
     * @param array $options
     */
    public function afterRender(?string $result, string $template, array $data = [], array $options = [])
    {

    }

    /**
     * 异常处理
     * @param Throwable $throwable
     * @return string
     * @throws Throwable
     */
    public function onException(Throwable $throwable): string
    {
        throw $throwable;
    }
}

// 配置渲染器
Render::getInstance()->getConfig()->setRender(new SmartyRender());
// 启动Swoole(在框架中使用时可以省略)
$http = new swoole_http_server("0.0.0.0", 9501);
$http->on("request", function (\Swoole\Http\Request $request, \Swoole\Http\Response $response){
    $content = Render::getInstance()->render('smarty.tpl',['time'=>time(),'engine'=>'smarty']);  // 调用渲染器进行渲染
    $response->end($content);
});
$render->attachServer($http);
$http->start();
```

### 启动测试服务器

代码包内置了一个小型的测试服务器，在tests目录下，支持smarty、plates、blade、think四种引擎的快速测试，使用下方代码启动，访问 http://127.0.0.1:9501 即可查看输出，对应的模板文件在test/TemplateViews目录下，可以修改这些模板来测试功能

```bash
cd tests
php testServer.php -e 模板引擎名称
```
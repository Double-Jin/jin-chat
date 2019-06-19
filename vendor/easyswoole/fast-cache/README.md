# FastCache
EasySwoole FastCache组件通过新开进程,使用SplArray存储,unix sock 高速通信方式,实现了多进程共享数据.

## 示例代码

### 服务端-SwooleServer
```
use EasySwoole\FastCache\Cache;

$http = new swoole_http_server("127.0.0.1", 9501);

Cache::getInstance()->attachToServer($http);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$http->on("request", function ($request, $response) {
    var_dump(Cache::getInstance()->keys());
    $response->header("Content-Type", "text/plain");
    $response->end("Hello World\n");
});

$http->start();
```
### 服务端-SwooleProcess
```
use EasySwoole\FastCache\Cache;

$processes = Cache::getInstance()->initProcess();

foreach ($processes as $process){
    $process->getProcess()->start();
}

while($ret = \Swoole\Process::wait()) {
    echo "PID={$ret['pid']}\n";
}
```

### 客户端
```
use EasySwoole\FastCache\Cache;
go(function (){
    Cache::getInstance()->set('a',time());
    var_dump(Cache::getInstance()->get('a'));
});
```

## 内存问题

Actor数据分散在进程内，一个进程可能需要占用很大的内存，因此请根据实际业务量配置内存大小。
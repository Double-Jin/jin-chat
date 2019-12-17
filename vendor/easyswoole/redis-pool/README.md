# Redis-Pool
## Pool Register
Register connection config before worker start ( master process);

```php
use EasySwoole\RedisPool\Redis;
use EasySwoole\RedisPool\Config;
$config = new Config();
Redis::getInstance()->register('redis1',$config1);

```

## Pool Usage

```
use EasySwoole\RedisPool\Redis;
use EasySwoole\RedisPool\Config;

$config1 = new Config([
    'host'          => '127.0.0.1',
    'port'          => 6379,
    'db'            => 0,
    'options'          => [],
    'auth'      => ''
]);


//注册的时候会返回对应的poolConf
Redis::getInstance()->register('redis1',$config1);


go(function (){
    $conn = Redis::defer('redis1');
    $conn->set('a',time());
    var_dump($conn->get('a'));
});

//清除pool中的定时器
swoole_event_exit();
```
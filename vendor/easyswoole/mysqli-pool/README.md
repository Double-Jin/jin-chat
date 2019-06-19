# Mysqli-Pool
## Pool Register
Register connection config before worker start ( master process);

```php
use EasySwoole\Mysqli\Config;
use EasySwoole\MysqliPool;

$config1 = new Config();
$config2 = new Config;

Mysql::getInstance()->register('mysql1',$config1);
Mysql::getInstance()->register('mysql2',$config2);


```

## Pool Usage

```
use EasySwoole\MysqliPool\Mysql;
use EasySwoole\MysqliPool\Connection;
use EasySwoole\Mysqli\Config;

$config1 = new Config([
    'host'          => '',
    'port'          => 3306,
    'user'          => '',
    'password'      => '',
    'database'      => '',
    'timeout'       => 5,
    'charset'       => 'utf8mb4',
]);

$config2 = new Config([
    'host' => '',
    'port' => 3306,
    'user' => '',
    'password' => '',
    'database' => '',
    'timeout' => 5,
    'charset' => 'utf8mb4',
]);

//注册的时候会返回对应的poolConf
Mysql::getInstance()->register('my1',$config1);

Mysql::getInstance()->register('my2',$config2);

go(function (){
    $conn = Mysql::defer('my1');
    var_dump(count($conn->rawQuery('show tables')));

    $ret =  Mysql::invoker('my1',function (Connection $conn){
        return $conn->rawQuery('show tables');
    });
    var_dump(count($ret));
});

go(function (){
    $conn = Mysql::defer('my2');
    var_dump(count($conn->rawQuery('show tables')));

    $ret =  Mysql::invoker('my2',function (Connection $conn){
        return $conn->rawQuery('show tables');
    });
    var_dump(count($ret));
});
```
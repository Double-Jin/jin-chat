## CoroutineRunner

```
use EasySwoole\Component\CoroutineRunner\Runner;
use Swoole\Coroutine\Scheduler;
use EasySwoole\Component\CoroutineRunner\Task;
$scheduler = new Scheduler;
$scheduler->add(function () {
    $runner = new Runner(4);
    $i = 10;
    while ($i){
        $runner->addTask(new Task(function ()use($runner,$i){
            var_dump("now is num.{$i} at time ".time());
            \co::sleep(1);

            if($i == 5){
                $runner->addTask(new Task(function (){
                    var_dump('this is task add in running');
                }));
            }

        }));
        $i--;
    }
    $runner->start();
    var_dump('task finish');
});
$scheduler->start();

```

## PoolInterface Example1

```
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\Component\Pool\TraitObjectInvoker;
use EasySwoole\Utility\Random;
use EasySwoole\Component\Pool\AbstractPoolObject;
use EasySwoole\Component\Pool\PoolObjectInterface;
use EasySwoole\Component\Pool\AbstractPool;
class test
{
    public $id;

    function __construct()
    {
        $this->id = Random::character(8);
    }

    function fuck(){
        var_dump('this is fuck at class:'.static::class.'@id:'.$this->id);
    }
}

class test2 extends test implements PoolObjectInterface
{
    function objectRestore()
    {
        var_dump('this is objectRestore at class:'.static::class.'@id:'.$this->id);
    }

    function gc()
    {
        // TODO: Implement gc() method.
    }

    function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}

class testPool extends AbstractPool
{

    protected function createObject()
    {
        // TODO: Implement createObject() method.
        return new test();
    }
}

class testPool2 extends AbstractPool
{

    protected function createObject()
    {
        // TODO: Implement createObject() method.
        return new test2();
    }
}



class test3 extends test
{
    use TraitObjectInvoker;
}

class test4 extends AbstractPoolObject
{
    function finalFuck()
    {
        var_dump('final fuck');
    }

    function objectRestore()
    {
        var_dump('final objectRestore');
    }
}

//cli下关闭pool的自动定时检查
PoolManager::getInstance()->getDefaultConfig()->setIntervalCheckTime(0);

go(function (){
    go(function (){
        $object = PoolManager::getInstance()->getPool(test::class)->getObj();
        $object->fuck();
        PoolManager::getInstance()->getPool(test::class)->recycleObj($object);
    });

    go(function (){
        testPool::invoke(function (test $test){
            $test->fuck();
        });
    });

    go(function (){
        testPool2::invoke(function (test2 $test){
            $test->fuck();
        });
    });

    go(function (){
        test3::invoke(function (test3 $test3){
            $test3->fuck();
        });
    });

    go(function (){
        $object = PoolManager::getInstance()->getPool(test4::class)->getObj();
        $object->finalFuck();
        PoolManager::getInstance()->getPool(test4::class)->recycleObj($object);
    });
});

```
## PoolInterface Example2
```
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\Component\Pool\TraitInvoker;

class TestPool extends AbstractPool{
    function __construct(\EasySwoole\Component\Pool\PoolConf $conf)
    {
        var_dump('new TestPool');
        parent::__construct($conf);
    }

    protected function createObject()
    {
        // TODO: Implement createObject() method.
        return new \stdClass();
    }

}

class TestPool2
{
    use TraitInvoker;
    function __construct()
    {
        var_dump('new TestPool2');
    }

    function fuck()
    {
        var_dump('fuck');
    }

}

go(function (){
    PoolManager::getInstance()->registerAnonymous('test',function (){
        return new SplFixedArray();
    });
    $pool = PoolManager::getInstance()->getPool(stdClass::class);
    $pool2 = PoolManager::getInstance()->getPool(\Redis::class);
    $pool3 = PoolManager::getInstance()->getPool('test');
    $pool::invoke(function (stdClass $class){
        var_dump($class);
    });
    $pool2::invoke(function (\Redis $class){
        var_dump($class);
    });
    $pool3::invoke(function (SplFixedArray $array){
        var_dump($array);
    });

    TestPool::invoke(function (\stdClass $class){
//        var_dump($class);
    });

    $pool4 = PoolManager::getInstance()->getPool(TestPool::class);
    $pool4::invoke(function (\stdClass $class){
//        var_dump($class);
    });

    TestPool2::invoke(function ($class){
        $class->fuck();
    });
    $pool5 = PoolManager::getInstance()->getPool(TestPool2::class);
    $pool5::invoke(function (\TestPool2 $class){
//        $class->fuck();
    });

});
```
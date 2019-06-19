<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/26
 * Time: 上午12:54
 */

namespace EasySwoole\Component\Pool;


use EasySwoole\Component\Pool\Exception\PoolException;
use EasySwoole\Component\Singleton;
use EasySwoole\Utility\Random;

class PoolManager
{
    use Singleton;

    private $pool = [];
    private $defaultConfig;
    private $anonymousMap = [];


    function __construct()
    {
        $this->defaultConfig = new PoolConf();
    }

    function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    function register(string $className, $maxNum = 20):PoolConf
    {
        $ref = new \ReflectionClass($className);
        if($ref->isSubclassOf(AbstractPool::class)){
            $conf = clone $this->defaultConfig;
            $conf->setMaxObjectNum($maxNum);
            $this->pool[$className] = [
                'class'=>$className,
                'config'=>$conf
            ];
            return $conf;
        }else{
            throw new PoolException("class {$className} not a sub class of AbstractPool class");
        }
    }

    function registerAnonymous(string $name,?callable $createCall = null)
    {
        /*
         * 绕过去实现动态class
         */
        $class = 'C'.Random::character(16);
        $classContent = '<?php
        class '.$class.' extends \EasySwoole\Component\Pool\AbstractPool {
            private $call;
            function __construct($conf,$call)
            {
                $this->call = $call;
                parent::__construct($conf);
            }

            protected function createObject()
            {
                // TODO: Implement createObject() method.
                return call_user_func($this->call,$this->getConfig());
            }
        }';
        $file = sys_get_temp_dir()."/{$class}.php";
        file_put_contents($file,$classContent);
        require_once $file;
        unlink($file);
        if(!is_callable($createCall)){
            if(class_exists($name)){
                $createCall = function ()use($name){
                    return new $name;
                };
            }else{
                return false;
            }
        }
        $this->pool[$name] = [
            'class'=>$class,
            'call'=>$createCall,
        ];
        return true;
    }

    /*
     * 请在进程克隆后，也就是worker start后，每个进程中独立使用
     */
    function getPool(string $key):?AbstractPool
    {
        if(isset($this->anonymousMap[$key])){
            $key = $this->anonymousMap[$key];
        }
        if(isset($this->pool[$key])){
            $item = $this->pool[$key];
            if($item instanceof AbstractPool){
                return $item;
            }else{
                $class = $item['class'];
                if(isset($item['config'])){
                    $obj = new $class($item['config']);
                    $this->pool[$key] = $obj;
                }else{
                    $config = clone $this->defaultConfig;
                    $createCall = $item['call'];
                    $obj = new $class($config,$createCall);
                    $this->pool[$key] = $obj;
                    $this->anonymousMap[get_class($obj)] = $key;
                }
                return $this->getPool($key);
            }
        }else{
            //先尝试动态注册
            $ret = false;
            try{
                $ret = $this->register($key);
            }catch (\Throwable $throwable){
                //此处异常不向上抛。
            }
            if($ret){
                return $this->getPool($key);
            }else if(class_exists($key) && $this->registerAnonymous($key)){
                return $this->getPool($key);
            }
            return null;
        }
    }
}
<?php


namespace EasySwoole\MysqliPool;


use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\Component\Pool\PoolConf;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Config;
use EasySwoole\Utility\Random;

class Mysql
{
    use Singleton;

    private $list = [];

    function register(string $name, Config $config): PoolConf
    {
        if (isset($this->list[$name])) {
            //已经注册，则抛出异常
            throw new MysqlPoolException("mysqlpool:{$name} is already been register");
        }
        /*
           * 绕过去实现动态class
           */
        $class = 'C' . Random::character(16);
        $classContent = '<?php
                
                namespace EasySwoole\MysqliPool;
                use EasySwoole\Component\Pool\AbstractPool;
                
                class ' . $class . ' extends AbstractPool {
                    protected function createObject()
                    {
                        return new Connection($this->getConfig()->getExtraConf());
                    }
                }';
        $file = sys_get_temp_dir() . "/{$class}.php";
        file_put_contents($file, $classContent);
        require_once $file;
        unlink($file);
        $class = "EasySwoole\\MysqliPool\\{$class}";
        $poolConfig = PoolManager::getInstance()->register($class);
        $poolConfig->setExtraConf($config);
        $this->list[$name] = [
            'class'  => $class,
            'config' => $config
        ];
        return $poolConfig;
    }

    static function defer(string $name, $timeout = null): ?Connection
    {
        $pool = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::defer($timeout);
        } else {
            return null;
        }
    }

    static function invoker(string $name, callable $call, float $timeout = null)
    {
        $pool = static::getInstance()->pool($name);
        if ($pool) {
            return $pool::invoke($call, $timeout);
        } else {
            return null;
        }
    }

    public function pool(string $name): ?AbstractPool
    {
        if (isset($this->list[$name])) {
            $item = $this->list[$name];
            if ($item instanceof AbstractPool) {
                return $item;
            } else {

                $class = $item['class'];
                $pool = PoolManager::getInstance()->getPool($class);
                $this->list[$name] = $pool;
                return $this->pool($name);
            }
        } else {
            return null;
        }
    }
}

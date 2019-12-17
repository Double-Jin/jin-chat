<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 11:39
 */

namespace EasySwoole\Component;
use Swoole\Timer as SWTimer;

class Timer
{
    use Singleton;

    protected $timerMap = [];

    function loop(int $ms, callable $callback, $name = null, ...$params): int
    {
        $id = SWTimer::tick($ms, $callback, ...$params);
        if ($name !== null) {
            $this->timerMap[md5($name)] = $id;
        }
        return $id;
    }

    function clear($timerIdOrName): bool
    {
        $tid = null;
        if(is_numeric($timerIdOrName)){
            $tid = $timerIdOrName;
        }else if(isset($this->timerMap[md5($timerIdOrName)])){
            $tid = $this->timerMap[md5($timerIdOrName)];
            unset($this->timerMap[md5($timerIdOrName)]);
        }
        if($tid && SWTimer::info($tid)){
            SWTimer::clear($tid);
            return true;
        }
        return false;
    }

    function clearAll(): bool
    {
        $this->timerMap = [];
        SWTimer::clearAll();
        return true;
    }

    function after(int $ms, callable $callback, ...$params): int
    {
        return SWTimer::after($ms, $callback, ...$params);
    }

    function list():array 
    {
        return SWTimer::list();
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 11:39
 */

namespace EasySwoole\Component;


class Timer
{
    use Singleton;

    protected $timerList = [];
    protected $timerMap = [];

    function loop(int $ms, callable $callback, $name = null): int
    {
        $id = swoole_timer_tick($ms, $callback);
        $this->timerList[$id] = $id;
        if ($name !== null) {
            $this->timerMap[md5($name)] = $id;
        }
        return $id;
    }

    function clear($timerIdOrName): bool
    {
        if (!isset($this->timerMap[md5($timerIdOrName)]) && !isset($this->timerList[$timerIdOrName])) {
            return false;
        }
        if (is_numeric($timerIdOrName)) {
            if (isset($this->timerList[$timerIdOrName])) {
                swoole_timer_clear($timerIdOrName);
                $key = array_search($timerIdOrName, $this->timerMap);
                if ($key !== null) {
                    unset($this->timerMap[$key]);
                }
                return true;
            }
        }
        $timerIdOrName = md5($timerIdOrName);
        if (!isset($this->timerMap[$timerIdOrName])) {
            return false;
        }
        $id = $this->timerMap[$timerIdOrName];
        swoole_timer_clear($id);
        unset($this->timerList[$id]);
        unset($this->timerMap[$timerIdOrName]);
        return true;
    }

    function clearAll(): bool
    {
        foreach ($this->timerList as $id) {
            swoole_timer_clear($id);
        }
        $this->timerList = [];
        $this->timerMap = [];
        return true;
    }

    function after(int $ms, callable $callback): int
    {
        return swoole_timer_after($ms, $callback);
    }
}

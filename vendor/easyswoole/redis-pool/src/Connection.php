<?php


namespace EasySwoole\RedisPool;


use EasySwoole\Component\Pool\PoolObjectInterface;

class Connection extends \Redis implements PoolObjectInterface
{
    function gc()
    {
        $this->close();
    }

    function objectRestore()
    {

    }

    function beforeUse(): bool
    {
        return true;
    }
}
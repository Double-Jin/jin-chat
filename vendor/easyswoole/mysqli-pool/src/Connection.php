<?php


namespace EasySwoole\MysqliPool;


use EasySwoole\Component\Pool\PoolObjectInterface;
use EasySwoole\Mysqli\Mysqli;

class Connection extends Mysqli implements PoolObjectInterface
{
    function gc()
    {
        try{
            $this->rollback();
        }catch (\Throwable $throwable){
            trigger_error($throwable->getMessage());
        }
        $this->resetDbStatus();
        $this->getMysqlClient()->close();
    }

    function objectRestore()
    {
        try{
            $this->rollback();
        }catch (\Throwable $throwable){
            trigger_error($throwable->getMessage());
        }
        $this->resetDbStatus();
    }

    function beforeUse(): bool
    {
        return $this->getMysqlClient()->connected;
    }
}
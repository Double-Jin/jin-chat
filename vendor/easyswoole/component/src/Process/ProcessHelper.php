<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 11:36
 */

namespace EasySwoole\Component\Process;


class ProcessHelper
{
    static function register(\swoole_server $server,AbstractProcess $process):bool
    {
        return $server->addProcess($process->getProcess());
    }
}
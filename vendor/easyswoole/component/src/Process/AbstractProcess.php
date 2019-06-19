<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 01:41
 */

namespace EasySwoole\Component\Process;
use Co\Channel;
use EasySwoole\Component\Timer;
use Swoole\Process;

abstract class AbstractProcess
{
    private $swooleProcess;
    /** @var Config */
    private $config;


    /**
     * AbstractProcess constructor.
     * @param string $processName
     * @param null $arg
     * @param bool $redirectStdinStdout
     * @param int $pipeType
     * @param bool $enableCoroutine
     */
    function __construct(...$args)
    {
        $arg1 = array_shift($args);
        if($arg1 instanceof Config){
            $this->config = $arg1;
        }else{
            $this->config = new Config();
            $this->config->setProcessName($arg1);
            $arg = array_shift($args);
            $this->config->setArg($arg);
            $redirectStdinStdout = (bool)array_shift($args) ?: false;
            $this->config->setRedirectStdinStdout($redirectStdinStdout);
            $pipeType = array_shift($args);
            $pipeType = $pipeType === null ? Config::PIPE_TYPE_SOCK_DGRAM : $pipeType;
            $this->config->setPipeType($pipeType);
            $enableCoroutine = (bool)array_shift($args) ?: false;
            $this->config->setEnableCoroutine($enableCoroutine);
        }
        $this->swooleProcess = new \swoole_process([$this,'__start'],$this->config->isRedirectStdinStdout(),$this->config->getPipeType(),$this->config->isEnableCoroutine());
    }

    public function getProcess():Process
    {
        return $this->swooleProcess;
    }

    public function addTick($ms,callable $call):?int
    {
        return Timer::getInstance()->loop(
            $ms,$call
        );
    }

    public function clearTick(int $timerId):?int
    {
        return Timer::getInstance()->clear($timerId);
    }

    public function delay($ms,callable $call):?int
    {
        return Timer::getInstance()->after($ms,$call);
    }

    /*
     * 服务启动后才能获得到pid
     */
    public function getPid():?int
    {
        if(isset($this->swooleProcess->pid)){
            return $this->swooleProcess->pid;
        }else{
            return null;
        }
    }

    function __start(Process $process)
    {
        if(PHP_OS != 'Darwin' && !empty($this->getProcessName())){
            $process->name($this->getProcessName());
        }
        Process::signal(SIGTERM,function ()use($process){
            go(function ()use($process){
                swoole_event_del($process->pipe);
                $channel = new Channel(8);
                go(function ()use($channel){
                    try{
                        $channel->push($this->onShutDown());
                    }catch (\Throwable $throwable){
                        $this->onException($throwable);
                    }
                });
                $channel->pop($this->config->getMaxExitWaitTime());
                swoole_event_exit();
                Process::signal(SIGTERM,null);
                $this->getProcess()->exit(0);
            });
        });
        swoole_event_add($this->swooleProcess->pipe, function(){
            try{
                $this->onPipeReadable($this->swooleProcess);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        });
        try{
            $this->run($this->config->getArg());
        }catch (\Throwable $throwable){
            $this->onException($throwable);
        }
    }

    public function getArg()
    {
        return $this->config->getArg();
    }

    public function getProcessName()
    {
        return $this->config->getProcessName();
    }

    protected function getConfig():Config
    {
        return $this->config;
    }

    protected function onException(\Throwable $throwable,...$args){
        throw $throwable;
    }

    protected abstract function run($arg);

    protected function onShutDown()
    {

    }

    protected function onPipeReadable(Process $process)
    {
        /*
         * 由于Swoole底层使用了epoll的LT模式，因此swoole_event_add添加的事件监听，
         * 在事件发生后回调函数中必须调用read方法读取socket中的数据，否则底层会持续触发事件回调。
         */
        $process->read();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:05
 */

namespace EasySwoole\FastCache;


use EasySwoole\Component\Singleton;
use EasySwoole\FastCache\Exception\RuntimeError;
use Swoole\Coroutine\Channel;

class Cache
{
    use Singleton;

    private $tempDir;
    private $serverName = 'EasySwoole';
    private $onTick;
    private $tickInterval = 5*1000;
    private $onStart;
    private $onShutdown;
    private $processNum = 3;
    private $run = false;
    private $backlog = 256;

    function __construct()
    {
        $this->tempDir = getcwd();
    }

    public function setTempDir(string $tempDir): Cache
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    public function setProcessNum(int $num):Cache
    {
        $this->modifyCheck();
        $this->processNum = $num;
        return $this;
    }

    public function setBacklog(?int $backlog = null)
    {
        $this->modifyCheck();
        if($backlog != null){
            $this->backlog = $backlog;
        }
        return $this;
    }

    public function setServerName(string $serverName): Cache
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    public function setOnTick($onTick): Cache
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }


    public function setTickInterval($tickInterval): Cache
    {
        $this->modifyCheck();
        $this->tickInterval = $tickInterval;
        return $this;
    }


    public function setOnStart($onStart): Cache
    {
        $this->modifyCheck();
        $this->onStart = $onStart;
        return $this;
    }


    public function setOnShutdown(callable $onShutdown): Cache
    {
        $this->modifyCheck();
        $this->onShutdown = $onShutdown;
        return $this;
    }

    function set($key,$value,float $timeout = 1.0)
    {
        if($this->processNum <= 0){
            return false;
        }

        $com = new Package();
        $com->setCommand('set');
        $com->setValue($value);
        $com->setKey($key);

        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    function get($key,float $timeout = 1.0)
    {
        if($this->processNum <= 0){
            return null;
        }
        $com = new Package();
        $com->setCommand('get');
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    function unset($key,float $timeout = 1.0)
    {
        if($this->processNum <= 0){
            return false;
        }
        $com = new Package();
        $com->setCommand('unset');
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    function keys($key = null,float $timeout = 1.0):?array
    {
        if($this->processNum <= 0){
            return [];
        }
        $com = new Package();
        $com->setCommand('keys');
        $com->setKey($key);
        $info =  $this->broadcast($com,$timeout);
        if(is_array($info)){
            $ret = [];
            foreach ($info as $item){
                if(is_array($item)){
                    foreach ($item as $sub){
                        $ret[] = $sub;
                    }
                }
            }
            return $ret;
        }else{
            return null;
        }
    }

    function flush(float $timeout = 1.0)
    {
        if($this->processNum <= 0){
            return false;
        }
        $com = new Package();
        $com->setCommand('flush');
        $this->broadcast($com,$timeout);
        return true;
    }

    public function enQueue($key,$value,$timeout = 1.0)
    {
        if($this->processNum <= 0){
            return false;
        }
        $com = new Package();
        $com->setCommand('enQueue');
        $com->setValue($value);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    public function deQueue($key,$timeout = 1.0)
    {
        if($this->processNum <= 0){
            return null;
        }
        $com = new Package();
        $com->setCommand('deQueue');
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    public function queueSize($key,$timeout = 1.0)
    {
        if($this->processNum <= 0){
            return null;
        }
        $com = new Package();
        $com->setCommand('queueSize');
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    public function unsetQueue($key,$timeout = 1.0):?bool
    {
        if($this->processNum <= 0){
            return false;
        }
        $com = new Package();
        $com->setCommand('unsetQueue');
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key),$com,$timeout);
    }

    /*
     * 返回当前队列的全部key名称
     */
    public function queueList($timeout = 1.0):?array
    {
        if($this->processNum <= 0){
            return [];
        }
        $com = new Package();
        $com->setCommand('queueList');
        $info =  $this->broadcast($com,$timeout);
        if(is_array($info)){
            $ret = [];
            foreach ($info as $item){
                if(is_array($item)){
                    foreach ($item as $sub){
                        $ret[] = $sub;
                    }
                }
            }
            return $ret;
        }else{
            return null;
        }
    }

    function flushQueue(float $timeout = 1.0):bool
    {
        if($this->processNum <= 0){
            return false;
        }
        $com = new Package();
        $com->setCommand('flushQueue');
        $this->broadcast($com,$timeout);
        return true;
    }

    function attachToServer(\swoole_server $server)
    {
        $list = $this->initProcess();
        foreach ($list as $process){
            /** @var $proces CacheProcess */
            $server->addProcess($process->getProcess());
        }
    }

    public function initProcess():array
    {
        $this->run = true;
        $ret = [];
        $name = "{$this->serverName}.FastCacheProcess";
        for($i = 0;$i < $this->processNum;$i++){
            $config = new ProcessConfig();
            $config->setProcessName("{$name}.{$i}");
            $config->setOnStart($this->onStart);
            $config->setOnShutdown($this->onShutdown);
            $config->setOnTick($this->onTick);
            $config->setTickInterval($this->tickInterval);
            $config->setTempDir($this->tempDir);
            $config->setBacklog($this->backlog);
            $ret[] = new CacheProcess($config->getProcessName(),$config);
        }
        return $ret;
    }

    private function generateSocket($key):string
    {
        //当以多维路径作为key的时候，以第一个路径为主。
        $list = explode('.',$key);
        $key = array_shift($list);
        $index = base_convert( substr(md5( $key),0,2), 16, 10 )%$this->processNum;
        return $this->generateSocketByIndex($index);
    }

    private function generateSocketByIndex($index)
    {
        return $this->tempDir."/{$this->serverName}.FastCacheProcess.{$index}.sock";
    }

    private function sendAndRecv($socketFile,Package $package,$timeout)
    {
        $client = new UnixClient($socketFile);
        $client->send(serialize($package));
        $ret =  $client->recv($timeout);

        if(!empty($ret)){
            $ret = unserialize($ret);

            if($ret instanceof Package){
                return $ret->getValue();
            }
        }
        return null;
    }

    private function broadcast(Package $command,$timeout = 0.1)
    {
        $info = [];
        $channel = new Channel($this->processNum+1);
        for ($i = 0;$i < $this->processNum;$i++){
            go(function ()use($command,$channel,$i,$timeout){
                $ret = $this->sendAndRecv($this->generateSocketByIndex($i),$command,$timeout);
                $channel->push([
                    $i => $ret
                ]);
            });
        }
        $start = microtime(true);
        while (1){
            if(microtime(true) - $start > $timeout){
                break;
            }
            $temp = $channel->pop($timeout);
            if(is_array($temp)){
                $info += $temp;
                if(count($info) == $this->processNum){
                    break;
                }
            }
        }
        return $info;
    }

    private function modifyCheck()
    {
        if($this->run){
            throw new RuntimeError('you can not modify configure after init process check');
        }
    }
}
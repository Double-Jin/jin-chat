<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:06
 */

namespace EasySwoole\FastCache;


use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Spl\SplArray;
use Swoole\Coroutine\Socket;

class CacheProcess extends AbstractProcess
{
    /** @var $config ProcessConfig */
    private $config;
    /*
     * @var $splArray SplArray
     */
    protected $splArray;
    protected $queueArray = [];

    /**
     * @return mixed
     */
    public function getSplArray()
    {
        return $this->splArray;
    }

    /**
     * @param mixed $splArray
     */
    public function setSplArray($splArray): void
    {
        $this->splArray = $splArray;
    }

    /**
     * @return array
     */
    public function getQueueArray(): array
    {
        return $this->queueArray;
    }

    /**
     * @param array $queueArray
     */
    public function setQueueArray(array $queueArray): void
    {
        $this->queueArray = $queueArray;
    }

    public function run($processConfig)
    {
        // TODO: Implement run() method.
        /** @var $processConfig ProcessConfig */
        $this->config = $processConfig;

        $this->splArray = new SplArray();
        if(is_callable($processConfig->getOnStart())){
            try{
                call_user_func($processConfig->getOnStart(),$this);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        }
        if(is_callable($processConfig->getOnTick())){
            $this->addTick($processConfig->getTickInterval(),function ()use($processConfig){
                try{
                    call_user_func($processConfig->getOnTick(),$this);
                }catch (\Throwable $throwable){
                    $this->onException($throwable);
                }
            });
        }

        \Swoole\Runtime::enableCoroutine(true);
        // TODO: Implement run() method.
        go(function ()use($processConfig){
            $sockFile = $processConfig->getTempDir()."/{$this->getProcessName()}.sock";
            if (file_exists($sockFile))
            {
                unlink($sockFile);
            }
            $socketServer = new Socket(AF_UNIX,SOCK_STREAM,0);
            $socketServer->bind($sockFile);
            if(!$socketServer->listen($processConfig->getBacklog())){
                trigger_error('listen '.$sockFile. ' fail');
                return;
            }
            while (1){
                $conn = $socketServer->accept(-1);
                if($conn){
                    go(function ()use($conn){
                        $com = new Package();
                        //先取4个字节的头
                        $header = $conn->recv(4,1);
                        if(strlen($header) == 4){
                            $allLength = Protocol::packDataLength($header);
                            $recvLeft = $allLength;
                            $data = '';
                            $tryTimes = 10;
                            while ($recvLeft > 0 && $tryTimes > 0){
                                $temp = $conn->recv($allLength,1);
                                if($temp === false){
                                    break;
                                }
                                $data = $data.$temp;
                                $recvLeft = $recvLeft - strlen($temp);
                                $tryTimes--;
                            }
                            if(strlen($data) == $allLength){
                                //开始数据包+命令处理，并返回数据
                                $fromPackage = unserialize($data);
                                if($fromPackage instanceof Package){
                                    switch ($fromPackage->getCommand())
                                    {
                                        case 'set':{
                                            $com->setValue(true);
                                            $this->splArray->set($fromPackage->getKey(),$fromPackage->getValue());
                                            break;
                                        }
                                        case 'get':{
                                            $com->setValue($this->splArray->get($fromPackage->getKey()));
                                            break;
                                        }
                                        case 'unset':{
                                            $com->setValue(true);
                                            $this->splArray->unset($fromPackage->getKey());
                                            break;
                                        }
                                        case 'keys':{
                                            $key = $fromPackage->getKey();
                                            $com->setValue($this->splArray->keys($key));
                                            break;
                                        }
                                        case 'flush':{
                                            $com->setValue(true);
                                            $this->splArray = new SplArray();
                                            break;
                                        }
                                        case 'enQueue':{
                                            $que = $this->initQueue($fromPackage->getKey());
                                            $data = $fromPackage->getValue();
                                            if($data !== null){
                                                $que->enqueue($fromPackage->getValue());
                                                $com->setValue(true);
                                            }else{
                                                $com->setValue(false);
                                            }
                                            break;
                                        }
                                        case 'deQueue':{
                                            $que = $this->initQueue($fromPackage->getKey());
                                            if($que->isEmpty()){
                                                $com->setValue(null);
                                            }else{
                                                $com->setValue($que->dequeue());
                                            }
                                            break;
                                        }
                                        case 'queueSize':{
                                            $que = $this->initQueue($fromPackage->getKey());
                                            $com->setValue($que->count());
                                            break;
                                        }
                                        case 'unsetQueue':{
                                            if(isset($this->queueArray[$fromPackage->getKey()])){
                                                unset($this->queueArray[$fromPackage->getKey()]);
                                                $com->setValue(true);
                                            }else{
                                                $com->setValue(false);
                                            }
                                            break;
                                        }
                                        case 'queueList':{
                                            $com->setValue(array_keys($this->queueArray));
                                            break;
                                        }
                                        case 'flushQueue':{
                                            $this->queueArray = [];
                                            $com->setValue(true);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        $string = Protocol::pack(serialize($com));
                        for ($written = 0; $written < strlen($string); $written += $fwrite) {
                            $fwrite = $conn->send(substr($string, $written));
                            if ($fwrite === false) {
                                break;
                            }
                        }
                        $conn->close();
                    });
                }
            }
        });
    }

    private function initQueue($key):\SplQueue
    {
        if(!isset($this->queueArray[$key])){
            $this->queueArray[$key] = new \SplQueue();
        }
        return $this->queueArray[$key];
    }

    public function onShutDown()
    {
        // TODO: Implement onShutDown() method.
        $onShutdown = $this->config->getOnShutdown();
        if(is_callable($onShutdown)){
            try{
                call_user_func($onShutdown,$this);
            }catch (\Throwable $throwable){
                $this->onException($throwable);
            }
        }
    }

    public function onReceive(string $str)
    {
        // TODO: Implement onReceive() method.
    }
}
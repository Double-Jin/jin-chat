<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-06
 * Time: 22:58
 */

namespace EasySwoole\Component\Context;


use EasySwoole\Component\Context\Exception\ModifyError;
use EasySwoole\Component\Singleton;
use Swoole\Coroutine;

class ContextManager
{
    use Singleton;

    private $contextHandler = [];

    private $context = [];

    private $deferList = [];

    public function registerItemHandler($key, ContextItemHandlerInterface $handler):ContextManager
    {
        $this->contextHandler[$key] = $handler;
        return $this;
    }

    public function set($key,$value,$cid = null):ContextManager
    {
        if(isset($this->contextHandler[$key])){
            throw new ModifyError('key is already been register for context item handler');
        }
        $cid = $this->getCid($cid);
        $this->context[$cid][$key] = $value;
        return $this;
    }

    public function get($key,$cid = null)
    {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid][$key])){
            return $this->context[$cid][$key];
        }
        if(isset($this->contextHandler[$key])){
            /** @var ContextItemHandlerInterface $handler */
            $handler = $this->contextHandler[$key];
            $this->context[$cid][$key] = $handler->onContextCreate();
            return $this->context[$cid][$key];
        }
        return null;
    }

    public function unset($key,$cid = null)
    {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid][$key])){
            if(isset($this->contextHandler[$key])){
                /** @var ContextItemHandlerInterface $handler */
                $handler = $this->contextHandler[$key];
                $item = $this->context[$cid][$key];
                unset($this->context[$cid][$key]);
                return $handler->onDestroy($item);
            }
            unset($this->context[$cid][$key]);
            return true;
        }else{
            return false;
        }
    }

    public function destroy($cid = null)
    {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid])){
            $data = $this->context[$cid];
            foreach ($data as $key => $val){
                $this->unset($key,$cid);
            }
        }
        unset($this->context[$cid]);
    }

    public function getCid($cid = null):int
    {
        if($cid === null){
            $cid = Coroutine::getUid();
            if(!isset($this->deferList[$cid]) && $cid > 0){
                $this->deferList[$cid] = true;
                Coroutine::defer(function ()use($cid){
                    unset($this->deferList[$cid]);
                    $this->destroy($cid);
                });
            }
            return $cid;
        }
        return $cid;
    }

    public function destroyAll($force = false)
    {
        if($force){
            $this->context = [];
        }else{
            foreach ($this->context as $cid => $data){
                $this->destroy($cid);
            }
        }
    }

    public function getContextArray($cid = null):?array
    {
        $cid = $this->getCid($cid);
        if(isset($this->context[$cid])){
            return $this->context[$cid];
        }else{
            return null;
        }
    }
}
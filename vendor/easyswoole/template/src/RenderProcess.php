<?php


namespace EasySwoole\Template;


use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use Swoole\Coroutine\Socket;
use Swoole\Process;

class RenderProcess extends AbstractUnixProcess
{
    function onAccept(Socket $socket)
    {
        /** @var RenderInterface $render */
        $render = $this->getConfig()->getRender();
        $header = $socket->recvAll(4,1);
        if(strlen($header) != 4){
            $socket->close();
            return;
        }
        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength,1);
        if(strlen($data) == $allLength){
            $data = unserialize($data);
            try{
                $reply = $render->render($data['template'],$data['data'],$data['options']);
            }catch (\Throwable $throwable){
                $reply = $render->onException($throwable);
            }finally{
                $render->afterRender($reply,$data['template'],$data['data'],$data['options']);
            }
            $socket->sendAll(Protocol::pack(serialize($reply)));
            $socket->close();
        }else{
            $socket->close();
            return;
        }
    }

    protected function onException(\Throwable $throwable,...$arg)
    {
        trigger_error("{$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}");
    }

    protected function onPipeReadable(Process $process)
    {
        $msg = $process->read();
        if($msg == 'shutdown'){
            $process->exit(0);
        }
    }
}
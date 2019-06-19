<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/25
 * Time: 下午3:16
 */

namespace EasySwoole\Socket;


use EasySwoole\Socket\AbstractInterface\Controller;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;
use EasySwoole\Socket\Client\Tcp;
use EasySwoole\Socket\Client\Udp;
use EasySwoole\Socket\Client\WebSocket;
use EasySwoole\Socket\Exception\ControllerPoolEmpty;
use Swoole\Coroutine as Co;

class Dispatcher
{
    private $config;
    private $controllerPoolCreateNum = [];
    function __construct(Config $config)
    {
        $this->config = $config;
        if($config->getParser() == null){
            throw new \Exception('Package parser is required');
        }
    }

    /*
     * $args:
     *  Tcp  $fd，$reactorId
     *  Web Socket swoole_websocket_frame $frame
     *  Udp array $client_info;
     */
    function dispatch(\swoole_server $server ,string $data, ...$args):void
    {
        $clientIp = null;
        $type = $this->config->getType();
        switch ($type){
            case Config::TCP:{
                $client = new Tcp( ...$args);
                break;
            }
            case Config::WEB_SOCKET:{
                $client = new WebSocket( ...$args);
                break;
            }
            case Config::UDP:{
                $client = new Udp( ...$args);
                break;
            }
            default:{
                throw new \Exception('dispatcher type error : '.$type);
            }
        }
        $caller = null;
        $response = new Response();
        try{
            $caller = $this->config->getParser()->decode($data,$client);
        }catch (\Throwable $throwable){
            //注意，在解包出现异常的时候，则调用异常处理，默认是断开连接，服务端抛出异常
            $this->hookException($server,$throwable,$data,$client,$response);
            goto response;
        }
        //如果成功返回一个调用者，那么执行调用逻辑
        if($caller instanceof Caller){
            $caller->setClient($client);
            //解包正确
            $controllerClass = $caller->getControllerClass();
            try{
                $controller = $this->getController($controllerClass);
            }catch (\Throwable $throwable){
                $this->hookException($server,$throwable,$data,$client,$response);
                goto response;
            }
            if($controller instanceof Controller){
                try{
                    $controller->__hook( $server,$this->config, $caller, $response);
                }catch (\Throwable $throwable){
                    $this->hookException($server,$throwable,$data,$client,$response);
                }finally {
                    $this->recycleController($controllerClass,$controller);
                }
            }else{
                $throwable = new ControllerPoolEmpty('controller pool empty for '.$controllerClass);
                $this->hookException($server,$throwable,$data,$client,$response);
            }
        }
        response :{
            switch ($response->getStatus()){
                case Response::STATUS_OK:{
                    $this->response($server,$client,$response);
                    break;
                }
                case Response::STATUS_RESPONSE_AND_CLOSE:{
                    $this->response($server,$client,$response);
                    $this->close($server,$client);
                    break;
                }
                case Response::STATUS_CLOSE:{
                    $this->close($server,$client);
                    break;
                }
            }
        }
    }


    private function response(\swoole_server $server,$client,Response $response)
    {
        $data = $this->config->getParser()->encode($response,$client);
        if($data === null){
            return;
        }
        if($client instanceof WebSocket){
            if($server->exist($client->getFd())){
                $server->push($client->getFd(),$data,$response->getOpCode(),$response->isFinish());
            }
        }else if($client instanceof Tcp){
            if($server->exist($client->getFd())){
                $server->send($client->getFd(),$data);
            }
        }else if($client instanceof Udp){
            $server->sendto($client->getAddress(),$client->getPort(),$data,$client->getServerSocket());
        }
    }

    private function close(\swoole_server $server,$client)
    {
        if($client instanceof Tcp){
            if($server->exist($client->getFd())){
                $server->close($client->getFd());
            }
        }
    }

    private function hookException(\swoole_server $server,\Throwable $throwable,string $raw,$client,Response $response)
    {
        if(is_callable($this->config->getOnExceptionHandler())){
            call_user_func($this->config->getOnExceptionHandler(),$server,$throwable,$raw,$client,$response);
        }else{
            $this->close($server,$client);
            throw $throwable;
        }
    }

    private function generateClassKey(string $class):string
    {
        return substr(md5($class), 8, 16);
    }

    private function getController(string $class)
    {
        $classKey = $this->generateClassKey($class);
        if(!isset($this->$classKey)){
            $this->$classKey = new Co\Channel($this->config->getMaxPoolNum()+1);
            $this->controllerPoolCreateNum[$classKey] = 0;
        }
        $channel = $this->$classKey;
        //懒惰创建模式
        /** @var Co\Channel $channel */
        if($channel->isEmpty()){
            $createNum = $this->controllerPoolCreateNum[$classKey];
            if($createNum < $this->config->getMaxPoolNum()){
                $this->controllerPoolCreateNum[$classKey] = $createNum + 1;
                try{
                    //防止用户在控制器结构函数做了什么东西导致异常
                    return new $class();
                }catch (\Throwable $exception){
                    $this->controllerPoolCreateNum[$classKey] = $createNum;
                    //直接抛给上层
                    throw $exception;
                }
            }
            return $channel->pop($this->config->getControllerPoolWaitTime());
        }
        return $channel->pop($this->config->getControllerPoolWaitTime());
    }

    private function recycleController(string $class,Controller $obj)
    {
        $classKey = $this->generateClassKey($class);
        /** @var Co\Channel $channel */
        $channel = $this->$classKey;
        $channel->push($obj);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午3:43
 */

namespace EasySwoole\Http;

use EasySwoole\Http\Message\Response as MessageResponse;
use EasySwoole\Http\Message\Status;

class Response extends MessageResponse
{
    private $response;
    const STATUS_NOT_END = 0;
    const STATUS_LOGICAL_END = 1;
    const STATUS_REAL_END = 2;
    const STATUS_RESPONSE_DETACH = 3;

    private $sendFile = null;
    private $isEndResponse = self::STATUS_NOT_END;//1 逻辑end  2真实end 3分离响应
    private $isChunk = false;

    final public function __construct(\swoole_http_response $response = null)
    {
        $this->response = $response;
        parent::__construct();
        $this->withAddedHeader('Server','EasySwoole');
    }

    function end(){
        $this->isEndResponse = self::STATUS_LOGICAL_END;
    }

    function __response():bool
    {
        if($this->isEndResponse <= self::STATUS_REAL_END){
            $this->isEndResponse = self::STATUS_REAL_END;
            //结束处理
            $status = $this->getStatusCode();
            $this->response->status($status);
            $headers = $this->getHeaders();
            foreach ($headers as $header => $val){
                foreach ($val as $sub){
                    $this->response->header($header,$sub);
                }
            }
            $cookies = $this->getCookies();
            foreach ($cookies as $cookie){
                $this->response->cookie(...$cookie);
            }
            $write = $this->getBody()->__toString();
            if($write !== '' && $this->isChunk){
                $this->response->write($write);
                $write = null;
            }

            if($this->sendFile != null){
                $this->response->sendfile($this->sendFile);
            }else{
                $this->response->end($write);
            }
            return true;
        }else{
            return false;
        }
    }

    function isEndResponse()
    {
        return $this->isEndResponse;
    }

    function write(string $str){
        if(!$this->isEndResponse()){
            $this->getBody()->write($str);
            return true;
        }else{
            return false;
        }
    }

    function redirect($url,$status = Status::CODE_MOVED_TEMPORARILY)
    {
        if(!$this->isEndResponse()){
            //仅支持header重定向  不做meta定向
            $this->withStatus($status);
            $this->withHeader('Location',$url);
            return true;
        }else{
            return false;
        }
    }

    /*
     * 目前swoole不支持同键名的header   因此只能通过别的方式设置多个cookie
     */
    public function setCookie($name, $value = null, $expire = null, $path = '/', $domain = '', $secure = false, $httponly = false){
        if(!$this->isEndResponse()){
            $this->withAddedCookie([
                $name,$value,$expire,$path,$domain,$secure,$httponly
            ]);
            return true;
        }else{
            return false;
        }

    }

    function getSwooleResponse()
    {
        return $this->response;
    }


    function sendFile(string $sendFilePath)
    {
        $this->sendFile = $sendFilePath;
    }

    public function detach():?int
    {
        $fd = $this->response->fd;
        $this->isEndResponse = self::STATUS_RESPONSE_DETACH;
        $this->response->detach();
        return $fd;
    }

    /**
     * @param bool $isChunk
     */
    public function setIsChunk(bool $isChunk): void
    {
        $this->isChunk = $isChunk;
    }

    static function createFromFd(int $fd):Response
    {
        $resp = \Swoole\Http\Response::create($fd);
        return new Response($resp);
    }

    final public function __toString():string
    {
        // TODO: Implement __toString() method.
        return Utility::toString($this);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->getBody()->close();
    }
}
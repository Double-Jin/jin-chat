<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/8/8
 * Time: 下午12:13
 */

namespace EasySwoole\Socket;


use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Exception\Exception;

class Config
{
    const UDP = 'UDP';
    const TCP = 'TCP';
    const WEB_SOCKET = 'WEB_SOCKET';

    protected $type;
    protected $onExceptionHandler = null;
    protected $parser;
    protected $maxPoolNum = 200;
    protected $controllerPoolWaitTime = 1;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return null
     */
    public function getOnExceptionHandler()
    {
        return $this->onExceptionHandler;
    }

    /**
     * @param null $onExceptionHandler
     */
    public function setOnExceptionHandler($onExceptionHandler): void
    {
        $this->onExceptionHandler = $onExceptionHandler;
    }

    /**
     * @return mixed
     */
    public function getParser():?ParserInterface
    {
        if(is_string($this->parser)){
            $class = $this->parser;
            $this->parser = new $class();
        }
        return $this->parser;
    }

    public function setParser($parser): void
    {
        if(is_string($parser)){
            try{
                $ref = new \ReflectionClass($parser);
                if(!$ref->implementsInterface(ParserInterface::class)){
                    throw new Exception("parser not a instance of ParserInterface");
                }
            }catch (\Throwable $throwable){
                throw new Exception($throwable->getMessage());
            }
        }else if(!$parser instanceof ParserInterface){
            throw new Exception("parser not a instance of ParserInterface");
        }
        $this->parser = $parser;
    }

    /**
     * @return int
     */
    public function getMaxPoolNum(): int
    {
        return $this->maxPoolNum;
    }

    /**
     * @param int $maxPoolNum
     */
    public function setMaxPoolNum(int $maxPoolNum): void
    {
        $this->maxPoolNum = $maxPoolNum;
    }

    /**
     * @return int
     */
    public function getControllerPoolWaitTime(): int
    {
        return $this->controllerPoolWaitTime;
    }

    /**
     * @param int $controllerPoolWaitTime
     */
    public function setControllerPoolWaitTime(int $controllerPoolWaitTime): void
    {
        $this->controllerPoolWaitTime = $controllerPoolWaitTime;
    }
}
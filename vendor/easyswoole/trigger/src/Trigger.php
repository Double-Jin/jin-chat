<?php


namespace EasySwoole\Trigger;


use EasySwoole\Log\LoggerInterface;

class Trigger implements TriggerInterface
{

    protected $logger;

    function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function error($msg, int $errorCode = E_USER_ERROR, Location $location = null)
    {
        if($location == null){
            $location = new Location();
            $debugTrace = debug_backtrace();
            $caller = array_shift($debugTrace);
            $location->setLine($caller['line']);
            $location->setFile($caller['file']);
        }
        $this->logger->console("{$msg} at file:{$location->getFile()} line:{$location->getLine()}",$this->errorMapLogLevel($errorCode));
    }

    public function throwable(\Throwable $throwable)
    {
        $msg = "{$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}";
        $this->logger->console($msg,LoggerInterface::LOG_LEVEL_ERROR);
    }

    private function errorMapLogLevel(int $errorCode)
    {
        switch ($errorCode){
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return LoggerInterface::LOG_LEVEL_ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                return LoggerInterface::LOG_LEVEL_WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
                return LoggerInterface::LOG_LEVEL_NOTICE;
            case E_STRICT:
                return LoggerInterface::LOG_LEVEL_NOTICE;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return LoggerInterface::LOG_LEVEL_NOTICE;
            default :
                return LoggerInterface::LOG_LEVEL_INFO;
        }
    }
}
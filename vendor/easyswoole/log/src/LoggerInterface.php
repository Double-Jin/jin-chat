<?php


namespace EasySwoole\Log;


interface LoggerInterface
{
    const LOG_LEVEL_INFO = 1;
    const LOG_LEVEL_NOTICE = 2;
    const LOG_LEVEL_WARNING = 3;
    const LOG_LEVEL_ERROR = 4;

    function log(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,string $category = 'DEBUG'):string ;
    function console(?string $msg,int $logLevel = self::LOG_LEVEL_INFO,string $category = 'DEBUG');
}
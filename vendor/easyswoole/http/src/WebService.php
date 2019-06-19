<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午11:10
 */

namespace EasySwoole\Http;


class WebService
{
    private $dispatcher;
    final function __construct($controllerNameSpace = 'App\\HttpController\\',$depth = 5,$maxPoolNum = 200)
    {
        $this->dispatcher = new Dispatcher($controllerNameSpace,$depth,$maxPoolNum);
    }

    function setExceptionHandler(callable $handler)
    {
        $this->dispatcher->setHttpExceptionHandler($handler);
    }

    function onRequest(Request $request_psr,Response $response_psr):void
    {
        $this->dispatcher->dispatch($request_psr,$response_psr);
        $response_psr->__response();
    }
}
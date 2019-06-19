<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/17
 * Time: 23:47
 */

namespace EasySwoole\Http\AbstractInterface;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

interface SessionDriverInterface
{
    function __construct(Request $request,Response $response,\SessionHandlerInterface $sessionHandler = null);
    function savePath(string $path = null):?string ;
    function sid(string $sid = null):?string ;
    function name(string $sessionName = null):?string ;
    function set($key,$val):bool;
    function exist($key):bool ;
    function get($key);
    function destroy():bool;
    function writeClose():bool;
    function start():bool;
    function gc($maxLifeTime):bool;
}
<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/25
 * Time: 下午4:12
 */

namespace EasySwoole\Socket\AbstractInterface;


use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;

interface ParserInterface
{
    public function decode($raw,$client):?Caller;

    public function encode(Response $response,$client):?string ;
}
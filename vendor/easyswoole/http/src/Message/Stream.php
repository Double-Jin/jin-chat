<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午3:18
 */

namespace EasySwoole\Http\Message;


use EasySwoole\Spl\SplStream;
use Psr\Http\Message\StreamInterface;

class Stream extends SplStream implements StreamInterface
{

}
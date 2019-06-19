<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-06
 * Time: 23:02
 */

namespace EasySwoole\Component\Context;


interface ContextItemHandlerInterface
{
    function onContextCreate();
    function onDestroy($context);
}
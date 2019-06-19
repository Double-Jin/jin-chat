<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/6/22
 * Time: 下午1:33
 */

namespace EasySwoole\Component\Pool;


interface PoolObjectInterface
{
     //unset 的时候执行
     function gc();
     //使用后,free的时候会执行
     function objectRestore();
     //使用前调用,当返回true，表示该对象可用。返回false，该对象失效，需要回收
     function beforeUse():bool ;
}
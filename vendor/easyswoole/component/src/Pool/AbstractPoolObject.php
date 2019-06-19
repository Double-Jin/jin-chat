<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/9/11
 * Time: 下午11:31
 */

namespace EasySwoole\Component\Pool;


abstract class AbstractPoolObject implements PoolObjectInterface
{
    function gc()
    {
        // 自动恢复public 与protected属性默认值
        $list = get_class_vars(static::class);
        foreach ($list as $property => $value){
            $this->$property = $value;
        }
    }

    function beforeUse():bool
    {
        return true;
    }

    function objectRestore()
    {

    }
}
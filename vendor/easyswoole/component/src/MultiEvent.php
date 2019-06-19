<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/30
 * Time: 下午2:37
 */

namespace EasySwoole\Component;



class MultiEvent extends MultiContainer
{
    function set($key, $item)
    {
        if(is_callable($item)){
            return parent::set($key, $item);
        }else{
            return false;
        }
    }

    function add($key, $item)
    {
        if(is_callable($item)){
            return parent::add($key, $item);
        }else{
            return false;
        }
    }

    /**
     * @param $event
     * @param mixed ...$args
     * @return array
     * @throws \Throwable
     */
    public function hook($event, ...$args):array
    {
        $res = [];
        $calls = $this->get($event);
        if(is_array($calls)){
            foreach ($calls as $key => $call){
                $res[$key] =  call_user_func($call,...$args);
            }
        }
        return $res;
    }
}
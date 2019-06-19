<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19-4-17
 * Time: 下午4:44
 */

namespace EasySwoole\Spl\test;


use EasySwoole\Spl\SplBean;

class OrderBean extends SplBean
{
    protected $orderId;
    protected $good;
    protected $name;

    protected function setKeyMapping(): array
    {
        return ['name' => 'orderName'];
    }

    protected function setClassMapping(): array
    {
        return ['good' => 'EasySwoole\Spl\test\AppleBean'];
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return mixed
     */
    public function getGood(): AppleBean
    {
        return $this->good;
    }

    /**
     * @param mixed $good
     */
    public function setGood(AppleBean $good): void
    {
        $this->good = $good;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }
}
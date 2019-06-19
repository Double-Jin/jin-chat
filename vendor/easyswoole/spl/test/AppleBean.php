<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19-4-17
 * Time: 下午3:57
 */

namespace EasySwoole\Spl\test;


use EasySwoole\Spl\SplBean;

class AppleBean extends SplBean
{
    protected $goodId;
    protected $name;
    protected $price;
    protected $number;

    protected function setKeyMapping(): array
    {
        return ['name' => 'goodName'];
    }

    /**
     * @return mixed
     */
    public function getGoodId()
    {
        return $this->goodId;
    }

    /**
     * @param mixed $goodId
     */
    public function setGoodId($goodId): void
    {
        $this->goodId = $goodId;
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

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number): void
    {
        $this->number = $number;
    }
}
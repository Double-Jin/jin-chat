<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/22
 * Time: 下午2:54
 */

namespace EasySwoole\Spl;


class SplEnum
{
    private $val = null;
    private $name = null;

    final public function __construct($val)
    {
        $list = self::getConstants();
        //禁止重复值
        if (count($list) != count(array_unique($list))) {
            $class = static::class;
            throw new \Exception("class : {$class} define duplicate value");
        }
        $this->val = $val;
        $this->name = self::isValidValue($val);
        if($this->name === false){
            throw new \Exception("invalid value");
        }
    }

    final public function getName():string
    {
        return $this->name;
    }

    final public function getValue()
    {
        return $this->val;
    }

    final public static function isValidName(string $name):bool
    {
        $list = self::getConstants();
        if(isset($list[$name])){
            return true;
        }else{
            return false;
        }
    }

    final public static function isValidValue($val)
    {
        $list = self::getConstants();
        return array_search($val,$list);
    }

    final public static function getEnumList():array
    {
        return self::getConstants();
    }

    private final static function getConstants():array
    {
        try{
            return (new \ReflectionClass(static::class))->getConstants();
        }catch (\Throwable $throwable){
            return [];
        }
    }

    function __toString()
    {
        // TODO: Implement __toString() method.
        return (string)$this->getName();
    }
}
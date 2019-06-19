<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-06
 * Time: 22:47
 */

namespace EasySwoole\Component\Tests;


use EasySwoole\Component\Pool\PoolManager;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        //cli下关闭pool的自动定时检查
        PoolManager::getInstance()->getDefaultConfig()->setIntervalCheckTime(0);
        parent::__construct($name, $data, $dataName);
    }

    function testNormalClass()
    {
        $pool = PoolManager::getInstance()->getPool(PoolObject::class);
        /**
         * @var $obj PoolObject
         */
        $obj = $pool->getObj();
        $this->assertEquals(PoolObject::class,$obj->fuck());
    }

    function testNormalClass2()
    {
        PoolManager::getInstance()->registerAnonymous('test',function (){
            return new PoolObject();
        });
        $pool = PoolManager::getInstance()->getPool('test');

        $pool2 = PoolManager::getInstance()->getPool(\stdClass::class);
        $stdClass = $pool2->getObj();
        $this->assertEquals(\stdClass::class,get_class($stdClass));
        $this->assertEquals(true,$pool2->recycleObj($stdClass));
        /**
         * @var $obj PoolObject
         */
        $obj = $pool->getObj();
        $hash1 = $obj->__objHash;
        $this->assertEquals(PoolObject::class,$obj->fuck());
        $pool->recycleObj($obj);

        $obj = $pool->getObj();
        $hash2 = $obj->__objHash;
        $pool->recycleObj($obj);
        $this->assertEquals($pool->status()['created'],1);
        $this->assertEquals($hash1,$hash2);

        $pool::invoke(function (PoolObject $object){
            $this->assertEquals(PoolObject::class,$object->fuck());
        });


    }
}
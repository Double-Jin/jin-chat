<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-06
 * Time: 23:27
 */

namespace EasySwoole\Component\Tests;


use EasySwoole\Component\Context\ContextManager;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        ContextManager::getInstance()->registerItemHandler('handler',new ContextContextItemHandler());
        parent::__construct($name, $data, $dataName);
    }

    function testHandler()
    {
        $object = ContextManager::getInstance()->get('handler');
        $this->assertEquals('handler',$object->text);
        ContextManager::getInstance()->destroy();
        $this->assertEquals(true,$object->destroy);
    }

    function testSet()
    {
        ContextManager::getInstance()->set('key1','key1');
        $this->assertEquals('key1',ContextManager::getInstance()->get('key1'));
    }

    function testUnset()
    {
        ContextManager::getInstance()->set('key1','key1');
        ContextManager::getInstance()->unset('key1');
        $this->assertEquals(null,ContextManager::getInstance()->get('key1'));
    }

    function testDestroy()
    {
        ContextManager::getInstance()->destroy();
        $this->assertEquals(null,ContextManager::getInstance()->getContextArray());
    }
}
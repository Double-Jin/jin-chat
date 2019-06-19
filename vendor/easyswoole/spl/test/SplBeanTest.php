<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19-4-17
 * Time: 下午3:52
 */

namespace EasySwoole\Spl\test;

use PHPUnit\Framework\TestCase;
require_once 'AppleBean.php';
require_once 'OrderBean.php';

class SplBeanTest extends TestCase
{

    function testAllPropertyOne() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $bean = new AppleBean($data);
        $properties = $bean->allProperty();
        $fields = ['goodId', 'name', 'price', 'number'];
        $this->assertTrue(count(array_diff($properties, $fields)) === 0 && count(array_diff($fields, $properties)) === 0);
    }

    function testAllPropertyTwo() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data, true);
        $properties = $bean->allProperty();
        $fields = ['goodId', 'name', 'price', 'number', 'age'];
        $this->assertTrue(count(array_diff($properties, $fields)) === 0 && count(array_diff($fields, $properties)) === 0);
    }

    function testAllPropertyThree() {
        $good = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $order = ['orderId' => 1, 'name' => 'order'];
        $bean = new OrderBean($order);
        $bean->setGood(new AppleBean($good));
        $properties = $bean->allProperty();
        $fields = ['orderId', 'good', 'name'];
        $this->assertTrue(count(array_diff($properties, $fields)) === 0 && count(array_diff($fields, $properties)) === 0);
    }

    function testToArrayOne() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data);
        $result = $bean->toArray();
        $fields = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testToArrayTwo() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data, true);
        $result = $bean->toArray();
        $fields = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testToArrayThree() {
        $good = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $order = ['orderId' => 1, 'name' => 'order'];
        $bean = new OrderBean($order);
        $bean->setGood(new AppleBean($good));
        $result = $bean->toArray();
        $goodItem = $bean->getGood()->toArray();
        $this->assertTrue($result['orderId'] === 1 && $result['name'] === 'order' && count(array_diff($goodItem, $good)) === 0 && count(array_diff($good, $goodItem)) === 0);
    }

    function testToArrayWithMappingOne() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data);
        $result = $bean->toArray();
        $fields = ['goodId' => 12, 'goodName' => 'apple', 'price' => 12, 'number' => 4];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testToArrayWithMappingTwo() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data, true);
        $result = $bean->toArray();
        $fields = ['goodId' => 12, 'goodName' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testToArrayWithMappingThree() {
        $good = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $order = ['orderId' => 1, 'name' => 'order'];
        $bean = new OrderBean($order);
        $bean->setGood(new AppleBean($good));
        $result = $bean->toArrayWithMapping();
        $goodItem = $bean->getGood()->toArray();
        $this->assertTrue($result['orderId'] === 1 && $result['orderName'] === 'order' && count(array_diff($goodItem, $good)) === 0 && count(array_diff($good, $goodItem)) === 0);
    }

    function testRestoreOne() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4, 'age' => 12];
        $bean = new AppleBean($data, true);
        $bean->restore(['goodId' => 13, 'name' => 'blank', 'price' => 10, 'number' => 2]);
        $result = $bean->toArray();
        $fields = ['goodId' => 13, 'name' => 'blank', 'price' => 10, 'number' => 2];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testRestoreTwo() {
        $data = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $bean = new AppleBean($data);
        $bean->restore(['goodId' => 13, 'name' => 'blank', 'price' => 10, 'number' => 2, 'age' => 12], true);
        $result = $bean->toArray();
        $fields = ['goodId' => 13, 'name' => 'blank', 'price' => 10, 'number' => 2, 'age' => 12];
        $this->assertTrue(count(array_diff($result, $fields)) === 0 && count(array_diff($fields, $result)) === 0);
    }

    function testRestoreThree() {
        $good = ['goodId' => 12, 'name' => 'apple', 'price' => 12, 'number' => 4];
        $order = ['orderId' => 1, 'name' => 'order'];
        $bean = new OrderBean($order);
        $newOrder = ['orderId' => 2, 'name' => 'order2'];
        $bean->restore($newOrder);
        $bean->setGood(new AppleBean($good));
        $result = $bean->toArray();
        $goodItem = $bean->getGood()->toArray();
        $this->assertTrue($result['orderId'] === 2 && $result['name'] === 'order2' && count(array_diff($goodItem, $good)) === 0 && count(array_diff($good, $goodItem)) === 0);
    }

}
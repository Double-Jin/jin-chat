<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-19
 * Time: 下午12:32
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

class BetweenLenTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        $this->freeValidate();
        $this->validate->addColumn('name')->betweenLen(2, 6);
        $bool = $this->validate->validate(['name' => 'blank']);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        $this->freeValidate();
        $this->freeValidate();
        $this->validate->addColumn('name')->betweenLen(2, 4);
        $bool = $this->validate->validate(['name' => 'blank']);
        $this->assertFalse($bool);
        $this->assertEquals("name的长度只能在 2 - 4 之间", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->freeValidate();
        $this->validate->addColumn('name')->betweenLen(2, 4, '姓名的长度只能2-4位');
        $bool = $this->validate->validate(['name' => 'blank']);
        $this->assertFalse($bool);
        $this->assertEquals("姓名的长度只能2-4位", $this->validate->getError()->__toString());
    }
}
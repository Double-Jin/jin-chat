<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午2:31
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 长度测试用例
 * Class LengthTest
 * @package EasySwoole\Validate\test
 */
class LengthTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        /*
         * int
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->length(2);
        $bool = $this->validate->validate(['name' => 12]);
        $this->assertTrue($bool);

        /*
         * 字符串整数
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->length(2);
        $bool = $this->validate->validate(['name' => '12']);
        $this->assertTrue($bool);

        /*
         * 数组
         */
        $this->freeValidate();
        $this->validate->addColumn('fruit')->length(3);
        $bool = $this->validate->validate(['fruit' => ['apple', 'grape', 'orange']]);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        /*
         * int
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->length(3);
        $bool = $this->validate->validate(['name' => 12]);
        $this->assertFalse($bool);
        $this->assertEquals("name的长度必须是3", $this->validate->getError()->__toString());

        /*
         * 字符串整数
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->length(3);
        $bool = $this->validate->validate(['name' => '12']);
        $this->assertFalse($bool);
        $this->assertEquals("name的长度必须是3", $this->validate->getError()->__toString());

        /*
         * 数组
         */
        $this->freeValidate();
        $this->validate->addColumn('fruit')->length(4);
        $bool = $this->validate->validate(['fruit' => ['apple', 'grape', 'orange']]);
        $this->assertFalse($bool);
        $this->assertEquals("fruit的长度必须是4", $this->validate->getError()->__toString());

        /*
         * 对象
         */
        $this->freeValidate();
        $this->validate->addColumn('fruit')->length(3);
        $bool = $this->validate->validate(['fruit' => (object)['apple', 'grape', 'orange']]);
        $this->assertFalse($bool);
        $this->assertEquals("fruit的长度必须是3", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('name')->length(6, '名字长度必须是6位');
        $bool = $this->validate->validate(['name' => 'blank']);
        $this->assertFalse($bool);
        $this->assertEquals("名字长度必须是6位", $this->validate->getError()->__toString());

    }
}
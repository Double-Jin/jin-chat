<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 上午11:28
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 非空数据测试用例
 * Class NotEmptyTest
 * @package EasySwoole\Validate\test
 */
class NotEmptyTest extends BaseTestCase
{

    /*
     * 合法
     */
    function testValidCase() {

        /*
         * 不为空字符串
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->notEmpty();
        $bool = $this->validate->validate(['name' => 'blank']);
        $this->assertTrue($bool);

        /*
         * 数值0
         */
        $this->freeValidate();
        $this->validate->addColumn('value')->notEmpty();
        $bool = $this->validate->validate(['value' => 0]);
        $this->assertTrue($bool);

        /*
         * 字符0
         */
        $this->freeValidate();
        $this->validate->addColumn('value')->notEmpty();
        $bool = $this->validate->validate(['value' => '0']);
        $this->assertTrue($bool);
    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {
        /*
         * 空字符
         */
        $this->freeValidate();
        $this->validate->addColumn('name', '名字')->notEmpty();
        $bool = $this->validate->validate(['name' => '']);
        $this->assertFalse($bool);
        $this->assertEquals('名字不能为空', $this->validate->getError()->__toString());

        /*
         * null
         */
        $this->freeValidate();
        $this->validate->addColumn('name', '名字')->notEmpty();
        $bool = $this->validate->validate(['name' => null]);
        $this->assertFalse($bool);
        $this->assertEquals('名字不能为空', $this->validate->getError()->__toString());

    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {
        /*
         * 空字符
         */
        $this->freeValidate();
        $this->validate->addColumn('name')->notEmpty('名字必填');
        $bool = $this->validate->validate(['name' => '']);
        $this->assertFalse($bool);
        $this->assertEquals('名字必填', $this->validate->getError()->__toString());
    }
}
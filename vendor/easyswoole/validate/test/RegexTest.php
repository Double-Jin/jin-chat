<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午3:37
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 正则测试用例
 * Class RegexTest
 * @package EasySwoole\Validate\test
 */
class RegexTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->regex('/^1\d{10}$/');
        $bool = $this->validate->validate(['phone' => '18959261286']);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->regex('/^1\d{10}$/');
        $bool = $this->validate->validate(['phone' => '1895926128s']);
        $this->assertFalse($bool);
        $this->assertEquals("phone不符合指定规则", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->regex('/^1\d{10}$/', '手机号码格式不对');
        $bool = $this->validate->validate(['phone' => '1895926128s']);
        $this->assertFalse($bool);
        $this->assertEquals("手机号码格式不对", $this->validate->getError()->__toString());

    }
}
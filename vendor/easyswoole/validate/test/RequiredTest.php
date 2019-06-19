<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午3:46
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 必填测试用例
 * Class RequiredTest
 * @package EasySwoole\Validate\test
 */
class RequiredTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->required();
        $bool = $this->validate->validate(['phone' => '18959261286']);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->required();
        $bool = $this->validate->validate([]);
        $this->assertFalse($bool);
        $this->assertEquals("phone必须填写", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('phone')->required('手机号码必填');
        $bool = $this->validate->validate([]);
        $this->assertFalse($bool);
        $this->assertEquals("手机号码必填", $this->validate->getError()->__toString());
    }
}
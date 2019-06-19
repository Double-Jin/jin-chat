<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class EqualWithColumnTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 值相等，但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('password')->equalWithColumn('rePassword');
        $validateResult = $this->validate->validate([ 'password' => '123', 'rePassword' => 123 ]);
        $this->assertTrue($validateResult);

        // 值相等，类型一样
        $this->freeValidate();
        $this->validate->addColumn('password')->equalWithColumn('rePassword', true);
        $validateResult = $this->validate->validate([ 'password' => '123', 'rePassword' => '123' ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 值相等，但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('password')->equalWithColumn('rePassword', true);
        $validateResult = $this->validate->validate([ 'password' => '123', 'rePassword' => 123 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('password必须等于rePassword的值', $this->validate->getError()->__toString());

        // 值不相等
        $this->freeValidate();
        $this->validate->addColumn('password')->equalWithColumn('rePassword');
        $validateResult = $this->validate->validate([ 'password' => '123', 'rePassword' => 1234 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('password必须等于rePassword的值', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 值相等但类型不符
        $this->freeValidate();
        $this->validate->addColumn('password')->equalWithColumn('rePassword', false, '密码必须和确认密码一样');
        $validateResult = $this->validate->validate([ 'password' => '123', 'rePassword' => 1234 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('密码必须和确认密码一样', $this->validate->getError()->__toString());
    }
}
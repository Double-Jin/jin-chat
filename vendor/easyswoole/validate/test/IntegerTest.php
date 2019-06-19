<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class IntegerTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 正常的int类型
        $this->freeValidate();
        $this->validate->addColumn('integer')->integer();
        $validateResult = $this->validate->validate([ 'integer' => 1 ]);
        $this->assertTrue($validateResult);

        // 文本型int
        $this->freeValidate();
        $this->validate->addColumn('integer')->integer();
        $validateResult = $this->validate->validate([ 'integer' => '100' ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 不是一个数字
        $this->freeValidate();
        $this->validate->addColumn('integer')->integer();
        $validateResult = $this->validate->validate([ 'integer' => 'xxx' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('integer只能是整数', $this->validate->getError()->__toString());

        // 不是一个整数
        $this->freeValidate();
        $this->validate->addColumn('integer', '个数')->integer();
        $validateResult = $this->validate->validate([ 'integer' => 0.001 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('个数只能是整数', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        $this->freeValidate();
        $this->validate->addColumn('integer')->integer('请输入正确的数量');
        $validateResult = $this->validate->validate([ 'integer' => 0.001 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('请输入正确的数量', $this->validate->getError()->__toString());
    }
}
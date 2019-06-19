<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class FloatTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 小数位浮点数
        $this->freeValidate();
        $this->validate->addColumn('float')->float();
        $validateResult = $this->validate->validate([ 'float' => 0.001 ]);
        $this->assertTrue($validateResult);

        // 字符串表达
        $this->freeValidate();
        $this->validate->addColumn('float')->float();
        $validateResult = $this->validate->validate([ 'float' => '0.001' ]);
        $this->assertTrue($validateResult);

        // 整数作为浮点数
        $this->freeValidate();
        $this->validate->addColumn('float')->float();
        $validateResult = $this->validate->validate([ 'float' => 2 ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 不是合法的浮点值
        $this->freeValidate();
        $this->validate->addColumn('float')->float();
        $validateResult = $this->validate->validate([ 'float' => 'aaa' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('float只能是浮点数', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 0 不属于浮点数
        $this->freeValidate();
        $this->validate->addColumn('float')->float('请输入一个浮点数');
        $validateResult = $this->validate->validate([ 'float' => 0 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('请输入一个浮点数', $this->validate->getError()->__toString());
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class BoolTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 值为true
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => true ]);
        $this->assertTrue($validateResult);

        // 值为 1 等同于 true
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => 1 ]);
        $this->assertTrue($validateResult);

        // 值为false
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => false ]);
        $this->assertTrue($validateResult);

        // 值为 0 等同于 false
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => 0 ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言 TODO 需要确认修正
    function testDefaultErrorMsgCase()
    {
        // 值为文本值无法通过
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => 'true' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('boolean只能是布尔值', $this->validate->getError()->__toString());

        // 值为文本数字时无法通过
        $this->freeValidate();
        $this->validate->addColumn('boolean', '状态')->bool();
        $validateResult = $this->validate->validate([ 'boolean' => '1' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('状态只能是布尔值', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 异常值测试
        $this->freeValidate();
        $this->validate->addColumn('boolean')->bool('状态只能是开启或关闭');
        $validateResult = $this->validate->validate([ 'boolean' => null ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('状态只能是开启或关闭', $this->validate->getError()->__toString());
    }
}
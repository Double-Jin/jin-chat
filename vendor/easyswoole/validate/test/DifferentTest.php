<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class DifferentTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 值不相等
        $this->freeValidate();
        $this->validate->addColumn('equal')->different('true');
        $validateResult = $this->validate->validate([ 'equal' => 'false' ]);
        $this->assertTrue($validateResult);

        // 值相等,但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('age')->different(12, true);
        $validateResult = $this->validate->validate([ 'age' => '12' ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 值相等
        $this->freeValidate();
        $this->validate->addColumn('equal')->different('true',true);
        $validateResult = $this->validate->validate([ 'equal' => 'true' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('equal必须不等于true', $this->validate->getError()->__toString());

        // 值相等,但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('age')->different('12');
        $validateResult = $this->validate->validate([ 'age' => 12 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('age必须不等于12', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 值相等但类型不符
        $this->freeValidate();
        $this->validate->addColumn('equal', '参数')->different('0');
        $validateResult = $this->validate->validate([ 'equal' => 0 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('参数必须不等于0', $this->validate->getError()->__toString());
    }
}
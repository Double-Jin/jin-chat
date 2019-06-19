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
        $this->validate->addColumn('name')->differentWithColumn('realName', true);
        $validateResult = $this->validate->validate([ 'name' => 'test', 'realName' => 'blank' ]);
        $this->assertTrue($validateResult);

        // 值相等,但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('name')->differentWithColumn('realName', true);
        $validateResult = $this->validate->validate([ 'name' => '12', 'realName' => 12 ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 值相等
        $this->freeValidate();
        $this->validate->addColumn('name')->differentWithColumn('realName', true);
        $validateResult = $this->validate->validate([ 'name' => 'blank', 'realName' => 'blank' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('name必须不等于realName的值', $this->validate->getError()->__toString());

        // 值相等,但类型不一样
        $this->freeValidate();
        $this->validate->addColumn('name')->differentWithColumn('realName');
        $validateResult = $this->validate->validate([ 'name' => '123', 'realName' => 123 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('name必须不等于realName的值', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 值相等但类型不符
        $this->freeValidate();
        $this->validate->addColumn('name')->differentWithColumn('realName', true, '昵称和真实姓名不能一致');
        $validateResult = $this->validate->validate([ 'name' => 'blank', 'realName' => 'blank' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('昵称和真实姓名不能一致', $this->validate->getError()->__toString());
    }
}
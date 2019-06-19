<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午9:28
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

/**
 * 是否在两值之间(包含极端值)
 * Class ActiveUrlTest
 * @package EasySwoole\Validate\test
 */
class BetweenTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 整数表示
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 6 ]);
        $this->assertTrue($validateResult);

        // 小数表示
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 6.33333 ]);
        $this->assertTrue($validateResult);

        // 字符串表示
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => '6' ]);
        $this->assertTrue($validateResult);

        // 等于最小值
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 5 ]);
        $this->assertTrue($validateResult);

        // 等于最大值
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 10 ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 不在值之间
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 20 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('number只能在 5 - 10 之间', $this->validate->getError()->__toString());


        // 不是合法值
        $this->freeValidate();
        $this->validate->addColumn('number', '年龄')->between(5, 10);
        $validateResult = $this->validate->validate([ 'number' => 'aaa' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('年龄只能在 5 - 10 之间', $this->validate->getError()->__toString());

    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 不在值之间
        $this->freeValidate();
        $this->validate->addColumn('number')->between(5, 10, '您输入的年龄不符');
        $validateResult = $this->validate->validate([ 'number' => '!' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('您输入的年龄不符', $this->validate->getError()->__toString());
    }
}
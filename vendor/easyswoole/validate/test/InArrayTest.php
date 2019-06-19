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
 * 在给定的数组中
 * Class ActiveUrlTest
 * @package EasySwoole\Validate\test
 */
class InArrayTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 符合条件
        $this->freeValidate();
        $this->validate->addColumn('number')->inArray([ 1, 2, 3, 4, 5 ]);
        $validateResult = $this->validate->validate([ 'number' => 5 ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 条件不符
        $this->freeValidate();
        $this->validate->addColumn('number')->inArray([ 1, 2, 3, 4, 5 ]);
        $validateResult = $this->validate->validate([ 'number' => 6 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('number必须在 [1,2,3,4,5] 范围内', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 严格模式下类型不等也不通过
        $this->freeValidate();
        $this->validate->addColumn('number')->inArray([ 1, 2, 3, 4, 5 ], true, '您选择的选项不合法');
        $validateResult = $this->validate->validate([ 'number' => '1' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('您选择的选项不合法', $this->validate->getError()->__toString());
    }
}
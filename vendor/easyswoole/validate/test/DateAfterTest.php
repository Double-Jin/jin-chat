<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class DateAfterTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        $this->freeValidate();
        $this->validate->addColumn('data1')->dateAfter('2018-08-08');
        $this->validate->addColumn('data2')->dateAfter('20180808');
        $this->validate->addColumn('data3')->dateAfter('2018-08-07 23:59:59');
        $validateResult = $this->validate->validate([
            'data1' => '2018-08-09',
            'data2' => '20180809',
            'data3' => '2018-08-08 00:00:00'
        ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 日期不符
        $this->freeValidate();
        $this->validate->addColumn('data')->dateAfter('2018-08-08');
        $validateResult = $this->validate->validate([ 'data' => '2018-08-06' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('data必须在日期 2018-08-08 之后', $this->validate->getError()->__toString());

        // 非法参数
        $this->freeValidate();
        $this->validate->addColumn('data')->dateAfter('aaa');
        $validateResult = $this->validate->validate([ 'data' => '2018-08-06' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('data必须在日期 aaa 之后', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 日期相等
        $this->freeValidate();
        $this->validate->addColumn('data')->dateAfter('2018-08-08', '日期不合法');
        $validateResult = $this->validate->validate([ 'data' => '2018-08-08' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('日期不合法', $this->validate->getError()->__toString());
    }
}
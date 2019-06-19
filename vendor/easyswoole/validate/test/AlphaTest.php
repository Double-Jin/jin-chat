<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

/**
 * 给定的参数是否是字母 即[a-zA-Z]
 * Class AlphaTest
 * @package EasySwoole\Validate\test
 */
class AlphaTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        $this->freeValidate();
        $this->validate->addColumn('alpha')->alpha();
        $validateResult = $this->validate->validate([ 'alpha' => 'alpha' ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        $this->freeValidate();
        $this->validate->addColumn('alpha')->alpha();
        $validateResult = $this->validate->validate([ 'alpha' => 'alpha1' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals($this->validate->getError()->__toString(), 'alpha只能是字母');

        $this->freeValidate();
        $this->validate->addColumn('alpha', '用户名')->alpha();
        $validateResult = $this->validate->validate([ 'alpha' => 123 ]);
        $this->assertFalse($validateResult);
        $this->assertEquals($this->validate->getError()->__toString(), '用户名只能是字母');
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        $this->freeValidate();
        $this->validate->addColumn('alpha')->alpha('您输入的用户名不合法');
        $validateResult = $this->validate->validate([ 'alpha' => true ]);
        $this->assertFalse($validateResult);
        $this->assertEquals($this->validate->getError()->__toString(), '您输入的用户名不合法');
    }
}
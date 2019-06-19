<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午11:17
 */

namespace EasySwoole\Validate\test;

require_once 'BaseTestCase.php';

class IsIpTest extends BaseTestCase
{
    // 合法断言
    function testValidCase()
    {
        // 合法的IPv4
        $this->freeValidate();
        $this->validate->addColumn('address')->isIp();
        $validateResult = $this->validate->validate([ 'address' => '192.168.1.1' ]);
        $this->assertTrue($validateResult);

        // 合法的IPv6
        $this->freeValidate();
        $this->validate->addColumn('address')->isIp();
        $validateResult = $this->validate->validate([ 'address' => '2001:0db8:85a3:08d3:1319:8a2e:0370:7334' ]);
        $this->assertTrue($validateResult);
    }

    // 默认错误信息断言
    function testDefaultErrorMsgCase()
    {
        // 不是IP
        $this->freeValidate();
        $this->validate->addColumn('address', '回调入口')->isIp();
        $validateResult = $this->validate->validate([ 'address' => 'http://baidu.com' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('回调入口不是有效的IP地址', $this->validate->getError()->__toString());
    }

    // 自定义错误信息断言
    function testCustomErrorMsgCase()
    {
        // 范围不合法
        $this->freeValidate();
        $this->validate->addColumn('address')->isIp('请输入合法的IP地址');
        $validateResult = $this->validate->validate([ 'address' => '256.256.256.256' ]);
        $this->assertFalse($validateResult);
        $this->assertEquals('请输入合法的IP地址', $this->validate->getError()->__toString());
    }
}
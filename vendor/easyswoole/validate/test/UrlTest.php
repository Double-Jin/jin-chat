<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午3:55
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 链接测试用例
 * Class UrlTest
 * @package EasySwoole\Validate\test
 */
class UrlTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        $this->freeValidate();
        $this->validate->addColumn('url')->url();
        $bool = $this->validate->validate(['url' => 'https://www.baidu.com']);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('url')->url();
        $bool = $this->validate->validate(['url' => 'msg.com']);
        $this->assertFalse($bool);
        $this->assertEquals("url必须是合法的网址", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('url')->url('链接无效');
        $bool = $this->validate->validate(['url' => 'msg.com']);
        $this->assertFalse($bool);
        $this->assertEquals("链接无效", $this->validate->getError()->__toString());
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午2:02
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 不在枚举范围测试用例
 * Class NotInArrayTest
 * @package EasySwoole\Validate\test
 */
class NotInArrayTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        /*
         * strict true
         */
        $this->freeValidate();
        $this->validate->addColumn('fruit')->notInArray(['apple', 'grape', 'orange'], true);
        $bool = $this->validate->validate(['fruit' => 'Apple']);
        $this->assertTrue($bool);

        /*
         * strict false
         */
        $this->freeValidate();
        $this->validate->addColumn('fruit')->notInArray(['apple', 'grape', 'orange'], true);
        $bool = $this->validate->validate(['fruit' => 'banana']);
        $this->assertTrue($bool);

    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('fruit')->notInArray(['apple', 'grape', 'orange']);
        $bool = $this->validate->validate(['fruit' => 'apple']);
        $this->assertFalse($bool);
        $this->assertEquals("fruit不能在 [apple,grape,orange] 范围内", $this->validate->getError()->__toString());

    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('fruit')->notInArray(['apple', 'grape', 'orange'], false, '水果不能是苹果、葡萄以及橘子');
        $bool = $this->validate->validate(['fruit' => 'apple']);
        $this->assertFalse($bool);
        $this->assertEquals("水果不能是苹果、葡萄以及橘子", $this->validate->getError()->__toString());

    }
}
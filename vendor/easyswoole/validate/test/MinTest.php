<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午3:33
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 最小值测试用例
 * Class MinTest
 * @package EasySwoole\Validate\test
 */
class MinTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        /*
         * int 测试整数情况(最小值)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => 12]);
        $this->assertTrue($bool);

        /*
        * int 测试整数情况(相等)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => 10]);
        $this->assertTrue($bool);

        /*
         * float 测试浮点数情况(最小值)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => 10.9]);
        $this->assertTrue($bool);

        /*
        * float 测试浮点数情况(相等)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10.1);
        $bool = $this->validate->validate(['price' => 10.1]);
        $this->assertTrue($bool);

        /*
        * 字符串整数(最小值)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => '12']);
        $this->assertTrue($bool);

        /*
        * 字符串整数(相等)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => '10']);
        $this->assertTrue($bool);

        /*
         * 字符串浮点数(最小值)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10);
        $bool = $this->validate->validate(['price' => '10.9']);
        $this->assertTrue($bool);

        /*
         * 字符串浮点数(相等)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(10.1);
        $bool = $this->validate->validate(['price' => '10.1']);
        $this->assertTrue($bool);
    }

    /*
     * 默认错误信息
     */
    function testDefaultErrorMsgCase() {

        /*
         * int
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(20);
        $bool = $this->validate->validate(['price' => 10]);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能小于20", $this->validate->getError()->__toString());

        /*
         * float
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(20);
        $bool = $this->validate->validate(['price' => 11.1]);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能小于20", $this->validate->getError()->__toString());

        /*
        * 字符串整数
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(20);
        $bool = $this->validate->validate(['price' => '11']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能小于20", $this->validate->getError()->__toString());

        /*
         * 字符串整数小数
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(20);
        $bool = $this->validate->validate(['price' => '11.1']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能小于20", $this->validate->getError()->__toString());

        /*
         * 非数字字符串
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->min(20);
        $bool = $this->validate->validate(['price' => '11.1.1']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能小于20", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('price')->min(20, '价钱至少20');
        $bool = $this->validate->validate(['price' => 11]);
        $this->assertFalse($bool);
        $this->assertEquals("价钱至少20", $this->validate->getError()->__toString());

    }
}
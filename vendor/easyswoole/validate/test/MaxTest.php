<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18-11-16
 * Time: 下午3:08
 */

namespace EasySwoole\Validate\test;

require_once "BaseTestCase.php";

/**
 * 最大值测试用例
 * Class MaxTest
 * @package EasySwoole\Validate\test
 */
class MaxTest extends BaseTestCase
{
    /*
     * 合法
     */
    function testValidCase() {

        /*
         * int 测试整数情况(不超过)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => 10]);
        $this->assertTrue($bool);

        /*
         * int 测试整数情况(相等)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => 12]);
        $this->assertTrue($bool);

        /*
         * float 测试浮点数情况(不超过)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => 10.9]);
        $this->assertTrue($bool);

        /*
         * float 测试浮点数情况(相等)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(10.9);
        $bool = $this->validate->validate(['price' => 10.9]);
        $this->assertTrue($bool);

        /*
        * 字符串整数 测试字符串整数情况(不超过)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => '10']);
        $this->assertTrue($bool);

        /*
        * 字符串整数 测试字符串整数情况(相等)
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => '12']);
        $this->assertTrue($bool);

        /*
         * 字符串整数小数 测试字符串浮点数情况(不超过)
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12);
        $bool = $this->validate->validate(['price' => '10.9']);
        $this->assertTrue($bool);

        /*
       * 字符串整数 测试字符串浮点数情况(相等)
       */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(12.1);
        $bool = $this->validate->validate(['price' => '12.1']);
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
        $this->validate->addColumn('price')->max(20);
        $bool = $this->validate->validate(['price' => 21]);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能大于20", $this->validate->getError()->__toString());

        /*
         * float
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(20);
        $bool = $this->validate->validate(['price' => 20.1]);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能大于20", $this->validate->getError()->__toString());

        /*
        * 字符串整数
        */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(20);
        $bool = $this->validate->validate(['price' => '21']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能大于20", $this->validate->getError()->__toString());

        /*
         * 字符串整数小数
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(20);
        $bool = $this->validate->validate(['price' => '21.1']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能大于20", $this->validate->getError()->__toString());

        /*
         * 非数字字符串
         */
        $this->freeValidate();
        $this->validate->addColumn('price')->max(20);
        $bool = $this->validate->validate(['price' => '21.1.1']);
        $this->assertFalse($bool);
        $this->assertEquals("price的值不能大于20", $this->validate->getError()->__toString());
    }

    /*
     * 自定义错误信息
     */
    function testCustomErrorMsgCase() {

        $this->freeValidate();
        $this->validate->addColumn('price')->max(20, '价钱不超过20');
        $bool = $this->validate->validate(['price' => 21]);
        $this->assertFalse($bool);
        $this->assertEquals("价钱不超过20", $this->validate->getError()->__toString());

    }
}
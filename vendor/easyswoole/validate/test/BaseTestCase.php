<?php
/**
 * Created by PhpStorm.
 * User: eValor
 * Date: 2018/11/16
 * Time: 上午9:31
 */

namespace EasySwoole\Validate\test;

use EasySwoole\Validate\Validate;

/**
 * 基础测试环境
 * Class BaseTestCase
 * @package EasySwoole\Validate\test
 */
class BaseTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Validate $validate */
    protected $validate;

    // 建立测试基境 引入必要文件
    function setUp()
    {
        require_once dirname(__FILE__) . '/../src/Rule.php';
        require_once dirname(__FILE__) . '/../src/Error.php';
        require_once dirname(__FILE__) . '/../src/Validate.php';
        $this->freeValidate();
        parent::setUp();
    }

    // 验证器是否已经实例化成功
    function testValidateClass()
    {
        $this->assertInstanceOf(Validate::class, $this->validate, 'validate is not instance of Validate class');
    }

    // 释放并初始化验证器
    function freeValidate()
    {
        $this->validate = new Validate;
    }
}
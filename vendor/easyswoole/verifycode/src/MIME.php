<?php
// +----------------------------------------------------------------------
// | easySwoole [ use swoole easily just like echo "hello world" ]
// +----------------------------------------------------------------------
// | WebSite: https://www.easyswoole.com
// +----------------------------------------------------------------------
// | Welcome Join QQGroup 633921431
// +----------------------------------------------------------------------

namespace EasySwoole\VerifyCode;

/**
 * 验证码MIME类型
 * Class MIME
 * @author : evalor <master@evalor.cn>
 * @package easySwoole\VerifyCode
 */
class MIME
{
    const JPG = 'image/jpeg';
    const PNG = 'image/png';
    const GIF = 'image/gif';

    /**
     * 获取对应Mime类型的后缀名称
     * @author : evalor <master@evalor.cn>
     * @param $Mime
     * @return mixed
     */
    static function getExtensionName($Mime)
    {
        $extension = [MIME::JPG => 'jpeg', MIME::PNG => 'png', MIME::GIF => 'gif'];
        return $extension[$Mime];
    }
}
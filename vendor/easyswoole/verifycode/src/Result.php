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
 * 验证码结果类
 * Class Result
 * @author : evalor <master@evalor.cn>
 * @package easySwoole\VerifyCode
 */
class Result
{
    private $CaptchaByte;  // 验证码图片
    private $CaptchaMime;  // 验证码类型
    private $CaptchaCode;  // 验证码内容
    private $CaptchaFile;  // 验证码文件

    function __construct($Byte, $Code, $Mime,$File)
    {
        $this->CaptchaByte = $Byte;
        $this->CaptchaMime = $Mime;
        $this->CaptchaCode = $Code;
        $this->CaptchaFile = $File;
    }

    /**
     * 获取验证码图片
     * @author : evalor <master@evalor.cn>
     * @return mixed
     */
    function getImageByte()
    {
        return $this->CaptchaByte;
    }

    /**
     * 返回图片Base64字符串
     * @author : evalor <master@evalor.cn>
     * @return string
     */
    function getImageBase64()
    {
        $base64Data = base64_encode($this->CaptchaByte);
        $Mime = $this->CaptchaMime;
        return "data:{$Mime};base64,{$base64Data}";
    }

    /**
     * 获取验证码内容
     * @author : evalor <master@evalor.cn>
     * @return mixed
     */
    function getImageCode()
    {
        return $this->CaptchaCode;
    }

    /**
     * 获取Mime信息
     * @author : evalor <master@evalor.cn>
     */
    function getImageMime()
    {
        return $this->CaptchaMime;
    }

    /**
     * 获取验证码文件路径
     * @author: eValor < master@evalor.cn >
     */
    function getImageFile()
    {
        if(!file_exists($this->CaptchaFile)){
            file_put_contents($this->CaptchaFile, $this->CaptchaByte);
        }
        return $this->CaptchaFile;
    }
}
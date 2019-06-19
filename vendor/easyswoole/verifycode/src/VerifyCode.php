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
 * 验证码处理类
 */
class VerifyCode
{
    protected $conf;
    protected $imInstance;

    /**
     * VerifyCode constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        // 传入了配置则使用配置文件
        $this->conf = $options instanceof Conf ? $options : new Conf;
        $assetsPath = __DIR__ . '/assets/';

        // 合并字体库
        $fonts = $this->loadFonts($assetsPath . 'ttf/');
        if ($this->fonts) $fonts = array_merge($fonts, $this->fonts);

        // 初始化配置项
        $this->useFont || $this->useFont = $fonts[array_rand($fonts)];
        $this->imageL || $this->imageL = $this->length * $this->fontSize * 1.5 + $this->fontSize / 2;
        $this->imageH || $this->imageH = $this->fontSize * 2;
        $this->fontColor || $this->fontColor = [mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150)];
        $this->backColor || $this->backColor = [255, 255, 255];
    }

    /**
     * 画验证码
     * @author : evalor <master@evalor.cn>
     * @param string $Code 画指定的验证码
     * @return Result
     */
    function DrawCode($Code = null)
    {
        // 如果传入了验证码则要重置参数
        if (!is_null($Code)) {
            $this->length = strlen($Code);
            $this->imageL || $this->imageL = $this->length * $this->fontSize * 1.5 + $this->fontSize / 2;
            $this->imageH || $this->imageH = $this->fontSize * 2;
        } else {
            $Code = substr(str_shuffle($this->charset), 0, $this->length);
        }

        $Code = strval($Code);

        // 创建空白画布
        $this->imInstance = imagecreate($this->imageL, $this->imageH);
        // 设置背景颜色
        $this->backColor = imagecolorallocate($this->imInstance, $this->backColor[0], $this->backColor[1], $this->backColor[2]);
        // 设置字体颜色
        $this->fontColor = imagecolorallocate($this->imInstance, $this->fontColor[0], $this->fontColor[1], $this->fontColor[2]);
        // 画干扰噪点
        if ($this->useNoise) $this->writeNoise();
        // 画干扰曲线
        if ($this->useCurve) $this->writeCurve();

        // 绘验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        for ($i = 0; $i < $this->length; $i++) {
            $codeNX += mt_rand($this->fontSize * 1.2, $this->fontSize * 1.4);
            // 写一个验证码字符
            imagettftext($this->imInstance, $this->fontSize, mt_rand(-50, 50), $codeNX, $this->fontSize * 1.5, $this->fontColor, $this->useFont, $Code[$i]);
        }

        // 输出验证码结果集
        $this->temp = rtrim(str_replace('\\', '/', $this->temp), '/') . '/';
        mt_srand();
        $filePath = $this->temp . date('YmdHis') . rand(1000,9999) .'.'.MIME::getExtensionName($this->mime);
        $func = 'image' . MIME::getExtensionName($this->mime);
        ob_start();
        $func($this->imInstance);
        $file = ob_get_contents();
        ob_end_clean();
        imagedestroy($this->imInstance);
        return new Result($file, $Code, $this->mime, $filePath);
    }

    /**
     * 加载字体资源文件
     * @param $fontsPath
     * @author : evalor <master@evalor.cn>
     * @return array
     */
    private function loadFonts($fontsPath)
    {
        $dir = dir($fontsPath);
        $fonts = [];
        while (false !== ($file = $dir->read())) {
            if ('.' != $file[0] && substr($file, -4) == '.ttf') {
                $fonts[] = $fontsPath . $file;
            }
        }
        $dir->close();
        return $fonts;
    }

    /**
     * 画干扰杂点
     * @author : evalor <master@evalor.cn>
     */
    private function writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            $noiseColor = imagecolorallocate($this->imInstance, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->imInstance, 5, mt_rand(-10, $this->imageL), mt_rand(-10, $this->imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * 画干扰曲线
     * @author : evalor <master@evalor.cn>
     */
    private function writeCurve()
    {
        $px = $py = 0;
        // 曲线前部分
        $A = mt_rand(1, $this->imageH / 2); // 振幅
        $b = mt_rand(-$this->imageH / 4, $this->imageH / 4); // Y轴方向偏移量
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageL * 2); // 周期
        $w = (2 * M_PI) / $T;
        $px1 = 0; // 曲线横坐标起始位置
        $px2 = mt_rand($this->imageL / 2, $this->imageL * 0.8); // 曲线横坐标结束位置
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
                    imagesetpixel($this->imInstance, $px + $i, $py + $i, $this->fontColor);
                    $i--;
                }
            }
        }
        // 曲线后部分
        $A = mt_rand(1, $this->imageH / 2); // 振幅
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageL * 2); // 周期
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageL;
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->imInstance, $px + $i, $py + $i, $this->fontColor);
                    $i--;
                }
            }
        }
    }

    function __get($name)
    {
        return $this->conf->$name;
    }

    function __set($name, $value)
    {
        $this->conf->$name = $value;
    }

}
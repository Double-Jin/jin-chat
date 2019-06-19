<?php

namespace EasySwoole\Template\TestCase\Engine;

use duncan3dc\Laravel\BladeInstance;
use \Throwable;
use EasySwoole\Template\RenderInterface;

/**
 * Laravel Blade Template Engine
 * Class Blade
 * @package EasySwoole\Template\Test
 */
class Blade implements RenderInterface
{
    private $engine;

    /**
     * Blade constructor.
     * @param $viewsDir
     * @param string $cacheDir
     */
    function __construct($viewsDir, $cacheDir = '')
    {
        if ($cacheDir == '') {
            $cacheDir = sys_get_temp_dir();
        }
        $this->engine = new BladeInstance($viewsDir, $cacheDir);
    }

    /**
     * 模板渲染
     * @param string $template
     * @param array $data
     * @param array $options
     * @return string|null
     */
    public function render(string $template, array $data = [], array $options = []): ?string
    {
        $content = $this->engine->render($template, $data);
        return $content;
    }

    /**
     * 每次渲染完成都会执行清理
     * @param string|null $result
     * @param string $template
     * @param array $data
     * @param array $options
     */
    public function afterRender(?string $result, string $template, array $data = [], array $options = [])
    {

    }

    /**
     * 异常处理
     * @param Throwable $throwable
     * @return string
     * @throws Throwable
     */
    public function onException(\Throwable $throwable): string
    {
        throw $throwable;
    }
}
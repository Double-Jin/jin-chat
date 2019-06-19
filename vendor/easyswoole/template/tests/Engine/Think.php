<?php

namespace EasySwoole\Template\TestCase\Engine;

use \Throwable;
use EasySwoole\Template\RenderInterface;
use think\Template as ThinkEngine;

/**
 * ThinkPHP Template Engine
 * Class Think
 * @package EasySwoole\Template\Test
 */
class Think implements RenderInterface
{
    private $engine;

    /**
     * Think constructor.
     * @param $viewsDir
     * @param string $cacheDir
     */
    function __construct($viewsDir, $cacheDir = '')
    {
        $config = [
            'view_path' => $viewsDir . '/',
            'cache_path' => $cacheDir . '/',
            'view_suffix' => 'html',
        ];
        $this->engine = new ThinkEngine($config);
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
        ob_start();
        $this->engine->fetch($template, $data);
        $content = ob_get_clean();
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
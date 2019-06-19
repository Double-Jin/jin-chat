<?php

namespace EasySwoole\Template\TestCase\Engine;

use \Throwable;
use EasySwoole\Template\RenderInterface;
use League\Plates\Engine as PlatesEngine;

/**
 * Plates PHP Template Engine
 * Class Plates
 * @package EasySwoole\Template\Test
 */
class Plates implements RenderInterface
{
    private $engine;

    /**
     * Plates constructor.
     * @param $viewsDir
     * @param string $cacheDir
     */
    function __construct($viewsDir, $cacheDir = '')
    {
        $this->engine = new PlatesEngine($viewsDir);
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
<?php

namespace EasySwoole\Template\TestCase\Engine;

use EasySwoole\Template\RenderInterface;
use SmartyException;
use Throwable;

class Smarty implements RenderInterface
{
    private $engine;

    /**
     * Smarty constructor.
     * @param $viewsDir
     * @param string $cacheDir
     */
    function __construct($viewsDir, $cacheDir = '')
    {
        if ($cacheDir == '') {
            $cacheDir = sys_get_temp_dir();
        }
        $this->engine = new \Smarty();
        $this->engine->setTemplateDir($viewsDir);
        $this->engine->setCacheDir($cacheDir);
        $this->engine->setCompileDir($cacheDir);
    }

    /**
     * 模板渲染
     * @param string $template
     * @param array $data
     * @param array $options
     * @return string|null
     * @throws SmartyException
     */
    public function render(string $template, array $data = [], array $options = []): ?string
    {
        foreach ($data as $key => $item) {
            $this->engine->assign($key, $item);
        }
        return $this->engine->fetch($template);
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
    public function onException(Throwable $throwable): string
    {
        throw $throwable;
    }
}
<?php


namespace EasySwoole\Template;


use EasySwoole\Component\Process\Socket\UnixProcessConfig;

class RenderProcessConfig extends UnixProcessConfig
{
    protected $render;

    public function getRender():RenderInterface
    {
        return $this->render;
    }

    public function setRender(RenderInterface $render): void
    {
        $this->render = $render;
    }
}
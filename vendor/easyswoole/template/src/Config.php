<?php


namespace EasySwoole\Template;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    protected $render;
    protected $tempDir;
    protected $workerNum = 3;
    protected $timeout = 3;

    /**
     * @return mixed
     */
    public function getRender():RenderInterface
    {
        return $this->render;
    }

    /**
     * @param mixed $render
     */
    public function setRender(RenderInterface $render): void
    {
        $this->render = $render;
    }

    /**
     * @return mixed
     */
    public function getTempDir()
    {
        return $this->tempDir;
    }

    /**
     * @param mixed $tempDir
     */
    public function setTempDir($tempDir): void
    {
        $this->tempDir = $tempDir;
    }

    /**
     * @return int
     */
    public function getWorkerNum(): int
    {
        return $this->workerNum;
    }

    /**
     * @param int $workerNum
     */
    public function setWorkerNum(int $workerNum): void
    {
        $this->workerNum = $workerNum;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return mixed
     */
    public function getSocketPrefix()
    {
        return md5(get_class($this->getRender()));
    }

    protected function initialize(): void
    {
        if(empty($this->tempDir)){
            $this->tempDir = sys_get_temp_dir();
        }
    }

}
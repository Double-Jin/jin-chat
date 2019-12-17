<?php


namespace EasySwoole\Component\Process;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    const PIPE_TYPE_NONE = 0;
    const PIPE_TYPE_SOCK_STREAM = 1;
    const PIPE_TYPE_SOCK_DGRAM = 2;

    protected $processName;
    protected $arg;
    protected $redirectStdinStdout = false;
    protected $pipeType = self::PIPE_TYPE_SOCK_DGRAM;
    protected $enableCoroutine = false;
    protected $maxExitWaitTime = 3;

    /**
     * @return mixed
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @param mixed $processName
     */
    public function setProcessName($processName): void
    {
        $this->processName = $processName;
    }

    /**
     * @return mixed
     */
    public function getArg()
    {
        return $this->arg;
    }

    /**
     * @param mixed $arg
     */
    public function setArg($arg): void
    {
        $this->arg = $arg;
    }

    /**
     * @return bool
     */
    public function isRedirectStdinStdout(): bool
    {
        return $this->redirectStdinStdout;
    }

    /**
     * @param bool $redirectStdinStdout
     */
    public function setRedirectStdinStdout(bool $redirectStdinStdout): void
    {
        $this->redirectStdinStdout = $redirectStdinStdout;
    }

    /**
     * @return int
     */
    public function getPipeType(): int
    {
        return $this->pipeType;
    }

    /**
     * @param int $pipeType
     */
    public function setPipeType(int $pipeType): void
    {
        $this->pipeType = $pipeType;
    }

    /**
     * @return bool
     */
    public function isEnableCoroutine(): bool
    {
        return $this->enableCoroutine;
    }

    /**
     * @param bool $enableCoroutine
     */
    public function setEnableCoroutine(bool $enableCoroutine): void
    {
        $this->enableCoroutine = $enableCoroutine;
    }

    /**
     * @return int
     */
    public function getMaxExitWaitTime(): int
    {
        return $this->maxExitWaitTime;
    }

    /**
     * @param int $maxExitWaitTime
     */
    public function setMaxExitWaitTime(int $maxExitWaitTime): void
    {
        $this->maxExitWaitTime = $maxExitWaitTime;
    }

}
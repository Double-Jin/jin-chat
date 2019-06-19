<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:19
 */

namespace EasySwoole\FastCache;


class ProcessConfig
{
    private $tempDir;
    private $processName;
    private $onTick;
    private $tickInterval = 5*1000;
    private $onStart;
    private $onShutdown;
    private $backlog;

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
    public function getOnTick()
    {
        return $this->onTick;
    }

    /**
     * @param mixed $onTick
     */
    public function setOnTick($onTick): void
    {
        $this->onTick = $onTick;
    }

    /**
     * @return float|int
     */
    public function getTickInterval()
    {
        return $this->tickInterval;
    }

    /**
     * @param float|int $tickInterval
     */
    public function setTickInterval($tickInterval): void
    {
        $this->tickInterval = $tickInterval;
    }

    /**
     * @return mixed
     */
    public function getOnStart()
    {
        return $this->onStart;
    }

    /**
     * @param mixed $onStart
     */
    public function setOnStart($onStart): void
    {
        $this->onStart = $onStart;
    }

    /**
     * @return mixed
     */
    public function getOnShutdown()
    {
        return $this->onShutdown;
    }

    /**
     * @param mixed $onShutdown
     */
    public function setOnShutdown($onShutdown): void
    {
        $this->onShutdown = $onShutdown;
    }

    /**
     * @return int
     */
    public function getBacklog(): int
    {
        return $this->backlog;
    }

    /**
     * @param int $backlog
     */
    public function setBacklog(int $backlog): void
    {
        $this->backlog = $backlog;
    }
}
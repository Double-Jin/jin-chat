<?php


namespace EasySwoole\Component\Process\Socket;


use EasySwoole\Component\Process\Config;

class TcpProcessConfig extends Config
{
    protected $listenAddress = '0.0.0.0';
    protected $listenPort;
    protected $asyncCallback = true;

    /**
     * @return string
     */
    public function getListenAddress(): string
    {
        return $this->listenAddress;
    }

    /**
     * @param string $listenAddress
     */
    public function setListenAddress(string $listenAddress): void
    {
        $this->listenAddress = $listenAddress;
    }

    /**
     * @return mixed
     */
    public function getListenPort()
    {
        return $this->listenPort;
    }

    /**
     * @param mixed $listenPort
     */
    public function setListenPort($listenPort): void
    {
        $this->listenPort = $listenPort;
    }
    /**
     * @return bool
     */
    public function isAsyncCallback(): bool
    {
        return $this->asyncCallback;
    }

    /**
     * @param bool $asyncCallback
     */
    public function setAsyncCallback(bool $asyncCallback): void
    {
        $this->asyncCallback = $asyncCallback;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/25
 * Time: 下午3:22
 */

namespace EasySwoole\Socket\Client;


class Tcp
{
    protected $reactorId;
    protected $fd;

    function __construct($fd = null,$reactorId = null)
    {
        $this->fd = $fd;
        $this->reactorId = $reactorId;
    }

    /**
     * @return mixed
     */
    public function getReactorId()
    {
        return $this->reactorId;
    }

    /**
     * @param mixed $reactorId
     */
    public function setReactorId($reactorId)
    {
        $this->reactorId = $reactorId;
    }

    /**
     * @return mixed
     */
    public function getFd()
    {
        return $this->fd;
    }

    /**
     * @param mixed $fd
     */
    public function setFd($fd)
    {
        $this->fd = $fd;
    }
}
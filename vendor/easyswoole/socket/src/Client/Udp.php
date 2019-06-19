<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/25
 * Time: 下午3:23
 */

namespace EasySwoole\Socket\Client;


class Udp
{
    private $server_socket = -1;
    private $address;
    private $port;

    function __construct($serSock,$address,$port)
    {
        $this->server_socket = $serSock;
        $this->address = $address;
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getServerSocket()
    {
        return $this->server_socket;
    }

    /**
     * @param mixed $server_socket
     */
    public function setServerSocket($server_socket)
    {
        $this->server_socket = $server_socket;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }
}
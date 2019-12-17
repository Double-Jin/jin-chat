<?php


namespace EasySwoole\RedisPool;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    protected $host = '127.0.0.1';
    protected $port = 6379;
    protected $auth;
    protected $options = [
        'serialize' => true
    ];
    protected $db = 0;

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
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
    public function setPort($port): void
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param mixed $auth
     */
    public function setAuth($auth): void
    {
        $this->auth = $auth;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return int
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param int $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

}
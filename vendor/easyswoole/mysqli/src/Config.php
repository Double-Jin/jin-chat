<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/20
 * Time: 上午11:28
 */

namespace EasySwoole\Mysqli;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    protected $host;
    protected $user;
    protected $password;
    protected $database;//数据库
    protected $port = 3306;
    protected $timeout = 30;
    protected $connect_timeout = 5;
    protected $charset = 'utf8';
    protected $strict_type =  false; //开启严格模式，返回的字段将自动转为数字类型
    protected $fetch_mode = false;//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
    protected $alias = '';
    protected $isSubQuery = false;
    protected $max_reconnect_times = 3;

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
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param mixed $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param mixed $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return bool
     */
    public function isStrictType()
    {
        return $this->strict_type;
    }

    /**
     * @param bool $strict_type
     */
    public function setStrictType($strict_type)
    {
        $this->strict_type = $strict_type;
    }

    /**
     * @return bool
     */
    public function isFetchMode()
    {
        return $this->fetch_mode;
    }

    /**
     * @param bool $fetch_mode
     */
    public function setFetchMode($fetch_mode)
    {
        $this->fetch_mode = $fetch_mode;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * @return bool
     */
    public function isSubQuery(): bool
    {
        return $this->isSubQuery;
    }

    /**
     * @param bool $isSubQuery
     */
    public function setIsSubQuery(bool $isSubQuery): void
    {
        $this->isSubQuery = $isSubQuery;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connect_timeout;
    }

    /**
     * @param int $connect_timeout
     */
    public function setConnectTimeout(int $connect_timeout): void
    {
        $this->connect_timeout = $connect_timeout;
    }

    /**
     * @return int
     */
    public function getMaxReconnectTimes(): int
    {
        return $this->max_reconnect_times;
    }

    /**
     * @param int $max_reconnect_times
     */
    public function setMaxReconnectTimes(int $max_reconnect_times): void
    {
        $this->max_reconnect_times = $max_reconnect_times;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/8/10
 * Time: 上午11:06
 */

namespace EasySwoole\Socket\Bean;


use EasySwoole\Spl\SplBean;

class Response extends SplBean
{
    const STATUS_RESPONSE_AND_CLOSE = 'RESPONSE_AND_CLOSE';//响应后关闭
    const STATUS_CLOSE = 'CLOSE';//不响应，直接关闭连接
    const STATUS_OK = 'OK';

    protected $status = self::STATUS_OK;
    protected $message = null;
    /*
     * 以下参数仅仅ws推送可用
     */
    protected $opCode = WEBSOCKET_OPCODE_TEXT;
    protected $finish = true;
    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getOpCode(): int
    {
        return $this->opCode;
    }

    /**
     * @param int $opCode
     */
    public function setOpCode(int $opCode): void
    {
        $this->opCode = $opCode;
    }

    /**
     * @return bool
     */
    public function isFinish(): bool
    {
        return $this->finish;
    }

    /**
     * @param bool $finish
     */
    public function setFinish(bool $finish): void
    {
        $this->finish = $finish;
    }

}
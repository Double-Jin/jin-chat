<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/26
 * Time: 下午2:25
 */

namespace EasySwoole\Http\Session;


use EasySwoole\Spl\SplFileStream;

class SessionHandler implements \SessionHandlerInterface
{
    private $sessionName = null;
    private $savePath = null;
    private $fp;
    public function close()
    {
        // TODO: Implement close() method.
        if($this->fp instanceof SplFileStream){
            $this->fp->unlock();
            $this->fp->close();
            $this->fp = null;
        }
    }

    public function destroy($session_id)
    {
        // TODO: Implement destroy() method.
        if($this->fp instanceof SplFileStream){
            $this->fp->truncate(0);
            $this->fp->seek(0);
        }
    }

    public function gc($maxlifetime)
    {
        // TODO: Implement gc() method.
        //后续实现。
    }

    public function open($save_path, $name)
    {
        // TODO: Implement open() method.
        $this->sessionName = $name;
        if($save_path){
            $this->savePath = $save_path;
        }else{
            $this->savePath = rtrim(sys_get_temp_dir(),'/');
        }
        return is_dir($this->savePath);
    }

    public function read($session_id)
    {
        // TODO: Implement read() method.
        $this->fp = new SplFileStream("{$this->savePath}/{$this->sessionName}_{$session_id}");
        $this->fp->lock();
        return $this->fp->__toString();
    }

    public function write($session_id, $session_data)
    {
        // TODO: Implement write() method.
        if($this->fp instanceof SplFileStream){
            $this->fp->truncate(0);
            $this->fp->seek(0);
            return (bool)$this->fp->write($session_data);
        }else{
            return false;
        }
    }
}
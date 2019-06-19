<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/7/26
 * Time: 下午1:36
 */

namespace EasySwoole\Http\Session;

use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Http\AbstractInterface\SessionDriverInterface;

class SessionDriver implements SessionDriverInterface
{
    private $handler = null;
    private $request;
    private $response;
    private $isStart = false;
    private $sid = null;
    private $sessionName = 'EasySwoole';
    private $savePath;
    private $data = [];
    private $expire = null;
    private $path = '/';
    private $domain = '';
    private $secure = false;
    private $httpOnly = false;

    function __construct(Request $request, Response $response, \SessionHandlerInterface $sessionHandler = null)
    {
        $this->request = $request;
        $this->response = $response;
        if ($sessionHandler) {
            $this->handler = $sessionHandler;
        } else {
            $this->handler = new SessionHandler();
        }
    }

    function savePath(string $path = null): ?string
    {
        if ($path) {
            if (!$this->isStart) {
                $this->savePath = rtrim($path, '/');
                return $this->savePath;
            } else {
                return null;
            }
        } else {
            return $this->savePath;
        }
    }

    function sid(?string $sid = null): ?string
    {
        if ($sid && $this->isStart) {  // 启动后不允许设置SID
            trigger_error('Session has started so cannot reset sid');
            return null;
        } else {  // 在启动前允许任意进行设置
            if ($sid) {
                $this->sid = $sid;
                return $sid;
            } else {
                return $this->sid;
            }
        }
    }

    function name(string $sessionName = null): ?string
    {
        if ($sessionName) {
            if (!$this->isStart) {
                $this->sessionName = $sessionName;
                return $sessionName;
            } else {
                return null;
            }
        } else {
            return $this->sessionName;
        }
    }

    /*
     * 注意，这里并不是同步写入。write close的时候，才真实写入（与php一致）。
     */
    function set($key, $val): bool
    {
        if ($this->isStart) {
            $this->data[$key] = $val;
            return true;
        } else {
            trigger_error('session is close now,please start session');
            return false;
        }
    }

    function exist($key): bool
    {
        if ($this->isStart) {
            return isset($this->data[$key]);
        } else {
            trigger_error('session is close now,please start session');
            return false;
        }
    }

    function get($key)
    {
        if ($this->isStart) {
            if (isset($this->data[$key])) {
                return $this->data[$key];
            } else {
                return null;
            }
        } else {
            trigger_error('session is close now,please start session');
            return null;
        }
    }

    /*
     * 根据规范，session_destroy() 销毁当前会话中的全部数据，
     * 但是不会重置当前会话所关联的全局变量， 也不会重置会话 cookie。 如果需要再次使用会话变量，
     *  必须重新调用 session_start() 函数。
     */
    function destroy(): bool
    {
        if ($this->isStart) {
            $this->data = [];
            $this->handler->destroy($this->sid);
            return true;
        } else {
            return false;
        }
    }

    function writeClose(): bool
    {
        if ($this->isStart) {
            $this->isStart = false;
            if (!$this->handler->write($this->sid, serialize($this->data))) {
                trigger_error("save session {$this->sessionName}@{$this->sid} fail");
            }
            $this->handler->close();
            $this->resetStatus();
            return true;
        }
        return false;
    }

    function start(?string $sid = null): bool
    {
        if ($sid) { // 如果指定了SID 进行预设
            $this->sid($sid);
        }
        if (!$this->isStart) {
            $this->isStart = $this->handler->open($this->savePath, $this->sessionName);
            if (!$this->isStart) {
                trigger_error("session open {$this->savePath}@{$this->sessionName} fail");
                return false;
            } else {
                //开启成功 如果当前没有设置SID则预生成;
                $this->sid = $this->generateSid();
                //载入数据,实现原则中，start后则对Session文件加锁
                $data = $this->handler->read($this->sid);
                if (!empty($data)) {
                    $data = unserialize($data);
                    if (is_array($data)) {
                        $this->data = $data;
                    }
                }
                return true;
            }
        }
        return true;
    }

    function __destruct()
    {
        $this->writeClose();
    }

    function gc($maxLifeTime): bool
    {
        $this->handler->gc($maxLifeTime);
        return true;
    }

    private function generateSid(): string
    {
        $sid = $this->request->getCookieParams($this->sessionName);
        if (!empty($sid) && !$this->sid) {
            return $sid;
        } else {
            $sid = $this->sid;
            if (!$this->sid) {
                $sid = md5(microtime(true) . $this->request->getSwooleRequest()->fd);
            }
            $this->request->withCookieParams([
                    $this->sessionName => $sid
                ]
                +
                $this->request->getCookieParams()
            );
            $this->response->setCookie($this->sessionName, $sid, $this->getExpire(), $this->path, $this->domain, $this->secure, $this->httpOnly);
            return $sid;
        }
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    public function setHttpOnly(bool $httpOnly): void
    {
        $this->httpOnly = $httpOnly;
    }

    public function setExpire(int $seconds): void
    {
        $this->expire = $seconds;
    }

    public function getExpire(): ?int
    {
        if (!$this->expire) {
            return null;
        }
        return time() + $this->expire;
    }

    private function resetStatus()
    {
        $this->sid = null;
        $this->sessionName = 'EasySwoole';
        $this->savePath = null;
        $this->data = [];
    }
}

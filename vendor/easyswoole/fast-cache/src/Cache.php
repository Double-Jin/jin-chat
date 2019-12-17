<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:05
 */

namespace EasySwoole\FastCache;

use EasySwoole\Component\Process\Exception;
use EasySwoole\Component\Singleton;
use EasySwoole\FastCache\Exception\RuntimeError;
use Swoole\Coroutine\Channel;
use swoole_server;

class Cache
{
    use Singleton;

    private $tempDir;
    private $serverName = 'EasySwoole';
    private $onTick;
    private $tickInterval = 5 * 1000;
    private $onStart;
    private $onShutdown;
    private $processNum = 3;
    private $run = false;
    private $backlog = 256;

    /**
     * Cache constructor.
     */
    function __construct()
    {
        $this->tempDir = getcwd();
    }

    /**
     * 设置临时目录
     * @param string $tempDir 临时目录路径(全路径)
     * @return Cache
     * @throws RuntimeError
     */
    public function setTempDir(string $tempDir): Cache
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    /**
     * 设置处理进程数量
     * @param int $num 进程数量
     * @return Cache
     * @throws RuntimeError
     */
    public function setProcessNum(int $num): Cache
    {
        $this->modifyCheck();
        $this->processNum = $num;
        return $this;
    }

    /**
     * 设置UnixSocket的Backlog队列长度
     * @param int|null $backlog
     * @return $this
     * @throws RuntimeError
     */
    public function setBacklog(?int $backlog = null)
    {
        $this->modifyCheck();
        if ($backlog != null) {
            $this->backlog = $backlog;
        }
        return $this;
    }

    /**
     * 设置Server名称
     * @param string $serverName
     * @return Cache
     * @throws RuntimeError
     */
    public function setServerName(string $serverName): Cache
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * 设置内部定时器的回调方法(用于数据落地)
     * @param $onTick
     * @return Cache
     * @throws RuntimeError
     */
    public function setOnTick($onTick): Cache
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }

    /**
     * 设置内部定时器的间隔时间(用于数据落地)
     * @param $tickInterval
     * @return Cache
     * @throws RuntimeError
     */
    public function setTickInterval($tickInterval): Cache
    {
        $this->modifyCheck();
        $this->tickInterval = $tickInterval;
        return $this;
    }

    /**
     * 设置进程启动时的回调(落地数据恢复)
     * @param $onStart
     * @return Cache
     * @throws RuntimeError
     */
    public function setOnStart($onStart): Cache
    {
        $this->modifyCheck();
        $this->onStart = $onStart;
        return $this;
    }

    /**
     * 设置推出前回调(退出时可落地)
     * @param callable $onShutdown
     * @return Cache
     * @throws RuntimeError
     */
    public function setOnShutdown(callable $onShutdown): Cache
    {
        $this->modifyCheck();
        $this->onShutdown = $onShutdown;
        return $this;
    }

    /**
     * 设置缓存
     * @param string $key 缓存key
     * @param string $value 需要缓存的内容(可序列化的内容都可缓存)
     * @param null $ttl 缓存有效时间
     * @param float $timeout socket等待超时 下同
     * @return bool|mixed|null
     */
    function set($key, $value, ?int $ttl = null, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_SET);
        $com->setValue($value);
        $com->setKey($key);
        $com->setOption($com::ACTION_TTL, $ttl);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 获取缓存
     * @param string $key 缓存key
     * @param float $timeout
     * @return mixed|null
     */
    function get($key, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_GET);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 删除一个key
     * @param string $key
     * @param float $timeout
     * @return bool|mixed|null
     */
    function unset($key, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_UNSET);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 获取当前全部的key
     * @param null $key
     * @param float $timeout
     * @return array|null
     */
    function keys($key = null, float $timeout = 1.0): ?array
    {
        if ($this->processNum <= 0) {
            return [];
        }
        $com = new Package();
        $com->setCommand($com::ACTION_KEYS);
        $com->setKey($key);
        $info = $this->broadcast($com, $timeout);
        if (is_array($info)) {
            $ret = [];
            foreach ($info as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub) {
                        $ret[] = $sub;
                    }
                }
            }
            return $ret;
        } else {
            return null;
        }
    }

    /**
     * 清空所有进程的数据
     * @param float $timeout
     * @return bool
     */
    function flush(float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_FLUSH);
        $this->broadcast($com, $timeout);
        return true;
    }

    /**
     * 推入队列
     * @param $key
     * @param $value
     * @param float $timeout
     * @return bool|mixed|null
     */
    public function enQueue($key, $value, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_ENQUEUE);
        $com->setValue($value);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 从队列中取出
     * @param $key
     * @param float $timeout
     * @return mixed|null
     */
    public function deQueue($key, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_DEQUEUE);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 队列当前长度
     * @param $key
     * @param float $timeout
     * @return mixed|null
     */
    public function queueSize($key, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_QUEUE_SIZE);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 释放队列
     * @param $key
     * @param float $timeout
     * @return bool|null
     */
    public function unsetQueue($key, $timeout = 1.0): ?bool
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_UNSET_QUEUE);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 返回当前队列的全部key名称
     * @param float $timeout
     * @return array|null
     */
    public function queueList($timeout = 1.0): ?array
    {
        if ($this->processNum <= 0) {
            return [];
        }
        $com = new Package();
        $com->setCommand($com::ACTION_QUEUE_LIST);
        $info = $this->broadcast($com, $timeout);
        if (is_array($info)) {
            $ret = [];
            foreach ($info as $item) {
                if (is_array($item)) {
                    foreach ($item as $sub) {
                        $ret[] = $sub;
                    }
                }
            }
            return $ret;
        } else {
            return null;
        }
    }

    /**
     * 清空所有队列
     * @param float $timeout
     * @return bool
     */
    function flushQueue(float $timeout = 1.0): bool
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_FLUSH_QUEUE);
        $this->broadcast($com, $timeout);
        return true;
    }

    /**
     * 设置一个key的过期时间
     * @param $key
     * @param int $ttl
     * @param float $timeout
     * @return mixed|null
     */
    function expire($key, int $ttl, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_EXPIRE);
        $com->setKey($key);
        $com->setValue($ttl);
        $com->setOption($com::ACTION_TTL, $ttl);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 移除一个key的过期时间
     * @param $key
     * @param float $timeout
     * @return mixed|null
     */
    function persist($key, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_PERSISTS);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 查看某个key的ttl
     * @param $key
     * @param float $timeout
     * @return mixed|null
     */
    function ttl($key, $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_TTL);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    /**
     * 投递消息任务
     * @param Job $job
     * @param float $timeout
     * @return int|null
     */
    public function putJob(Job $job,float $timeout = 1.0):?int
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_PUT_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    public function getJob(string $jobQueue, float $timeout = 1.0):?Job
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_GET_JOB);
        $com->setValue($jobQueue);
        return $this->sendAndRecv($this->generateSocket($jobQueue), $com, $timeout);
    }

    /**
     * 从延迟执行队列中拿取
     * @param string $queueName
     * @param float $timeout
     * @return Job|null
     */
    public function getDelayJob(string $queueName, float $timeout = 1.0):?Job
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_GET_DELAY_JOB);
        $com->setValue($queueName);
        return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
    }

    /**
     * 从保留队列中拿取
     * @param string $queueName
     * @param float $timeout
     * @return Job|null
     */
    public function getReserveJob(string $queueName, float $timeout = 1.0):?Job
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_GET_RESERVE_JOB);
        $com->setValue($queueName);
        return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
    }
    /**
     * 通过jobId将ready任务转为delay任务
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function delayJob(Job $job,float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_DELAY_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    /**
     * 任务重发
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function releaseJob(Job $job,float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_RELEASE_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    /**
     * 将ready 任务转为reserve任务
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function reserveJob(Job $job,float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_RESERVE_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    /**
     * 删除任务
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function deleteJob(Job $job,float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if (!$job->getJobId()){
            return false;
        }
        if (!$job->getQueue()){
            return false;
        }

        $com = new Package();
        $com->setCommand($com::ACTION_DELETE_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    /**
     * 将某个任务bury掉 直到kick
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function buryJob(Job $job,float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }
        // 必须传递queueName和jobId
        if (empty($job->getJobId())){
            return false;
        }
        if (empty($job->getQueue())){
            return false;
        }

        $com = new Package();
        $com->setCommand($com::ACTION_BURY_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    /**
     * 从bury状态中拿取一个任务
     * @param string $queueName
     * @param float $timeout
     * @return Job|null
     */
    public function getBuryJob(string $queueName, float $timeout = 1.0):?Job
    {
        if ($this->processNum <= 0) {
            return null;
        }

        $com = new Package();
        $com->setCommand($com::ACTION_GET_BURY_JOB);
        $com->setValue($queueName);
        return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
    }

    /**
     * 将bury任务恢复到ready中
     * @param Job $job
     * @param float $timeout
     * @return bool|null
     */
    public function kickJob(Job $job, float $timeout = 1.0):?bool
    {
        if ($this->processNum <= 0) {
            return null;
        }

        // 必须传递queueName和jobId
        if (empty($job->getJobId())){
            return false;
        }
        if (empty($job->getQueue())){
            return false;
        }

        $com = new Package();
        $com->setCommand($com::ACTION_KICK_JOB);
        $com->setValue($job);
        return $this->sendAndRecv($this->generateSocket($job->getQueue()), $com, $timeout);
    }

    public function jobQueues(float $timeout = 1.0):array
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_JOB_QUEUES);
        $info = $this->broadcast($com, $timeout);

        if (is_array($info)) {
            $ret = [];
            foreach ($info as $item) {
                if (is_array($item)) {
                    foreach ($item as $subKey => $sub) {
                        $ret[] = $sub;
                    }
                }
            }
            return $ret;
        } else {
            return null;
        }

    }

    public function flushJobQueue(string $jobQueue = null,float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if ($jobQueue !== null){
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_JOB);
            $com->setValue($jobQueue);
            return $this->sendAndRecv($this->generateSocket($jobQueue), $com, $timeout);
        }else{
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_JOB);
            $com->setValue($jobQueue);
            $info = $this->broadcast($com, $timeout);

            if (is_array($info)) {
                return $info;
            } else {
                return null;
            }
        }

    }

    /**
     * 只清空ready任务队列 可指定
     * @param string|NULL $queueName
     * @param float $timeout
     * @return array|mixed|null
     */
    public function flushReadyJobQueue(string $queueName = null,float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if ($queueName !== null){
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_READY_JOB);
            $com->setValue($queueName);
            return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
        }else{
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_READY_JOB);
            $com->setValue($queueName);
            $info = $this->broadcast($com, $timeout);

            if (is_array($info)) {
                return $info;
            } else {
                return null;
            }
        }
    }
    /**
     * 只清空reserve任务队列 可指定
     * @param string|NULL $queueName
     * @param float $timeout
     * @return array|mixed|null
     */
    public function flushReserveJobQueue(string $queueName = null,float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if ($queueName !== null){
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_RESERVE_JOB);
            $com->setValue($queueName);
            return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
        }else{
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_RESERVE_JOB);
            $com->setValue($queueName);
            $info = $this->broadcast($com, $timeout);

            if (is_array($info)) {
                return $info;
            } else {
                return null;
            }
        }
    }
    /**
     * 只清空BURY任务队列 可指定
     * @param string|NULL $queueName
     * @param float $timeout
     * @return array|mixed|null
     */
    public function flushBuryJobQueue(string $queueName = null,float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if ($queueName !== null){
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_BURY_JOB);
            $com->setValue($queueName);
            return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
        }else{
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_BURY_JOB);
            $com->setValue($queueName);
            $info = $this->broadcast($com, $timeout);

            if (is_array($info)) {
                return $info;
            } else {
                return null;
            }
        }
    }
    /**
     * 只清空delay任务队列 可指定
     * @param string|NULL $queueName
     * @param float $timeout
     * @return array|mixed|null
     */
    public function flushDelayJobQueue(string $queueName = null,float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }

        if ($queueName !== null){
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_DELAY_JOB);
            $com->setValue($queueName);
            return $this->sendAndRecv($this->generateSocket($queueName), $com, $timeout);
        }else{
            $com = new Package();
            $com->setCommand($com::ACTION_FLUSH_DELAY_JOB);
            $com->setValue($queueName);
            $info = $this->broadcast($com, $timeout);

            if (is_array($info)) {
                return $info;
            } else {
                return null;
            }
        }
    }

    public function jobQueueSize(string $jobQueue,float $timeout = 1.0):?array
    {
        if ($this->processNum <= 0) {
            return null;
        }

        $com = new Package();
        $com->setCommand($com::ACTION_JOB_QUEUE_SIZE);
        $com->setValue($jobQueue);
        return $this->sendAndRecv($this->generateSocket($jobQueue), $com, $timeout);
    }

    /**
     * 绑定到当前主服务
     * @param swoole_server $server
     * @throws Exception
     */
    function attachToServer(swoole_server $server)
    {
        $list = $this->initProcess();
        foreach ($list as $process) {
            /** @var $proces CacheProcess */
            $server->addProcess($process->getProcess());
        }
    }

    /**
     * 初始化缓存进程
     * @return array
     * @throws Exception
     */
    public function initProcess(): array
    {
        $this->run = true;
        $array = [];
        for ($i = 1; $i <= $this->processNum; $i++) {
            $config = new CacheProcessConfig();
            $config->setProcessName("{$this->serverName}.FastCacheProcess.{$i}");
            $config->setSocketFile($this->generateSocketByIndex($i));
            $config->setOnStart($this->onStart);
            $config->setOnShutdown($this->onShutdown);
            $config->setOnTick($this->onTick);
            $config->setTickInterval($this->tickInterval);
            $config->setTempDir($this->tempDir);
            $config->setBacklog($this->backlog);
            $config->setAsyncCallback(false);
            $config->setWorkerIndex($i);
            $array[$i] = new CacheProcess($config);
        }
        return $array;
    }

    /**
     * 根据操作的KEY指定Socket管道
     * @param $key
     * @return string
     */
    private function generateSocket($key): string
    {
        // 当以多维路径作为key的时候，以第一个路径为主
        $list = explode('.', $key);
        $key = array_shift($list);
        $index = (base_convert(substr(md5($key), 0, 2), 16, 10) % $this->processNum) + 1;
        return $this->generateSocketByIndex($index);
    }

    /**
     * 获取管道的文件名
     * @param $index
     * @return string
     */
    private function generateSocketByIndex($index)
    {
        return $this->tempDir . "/{$this->serverName}.FastCacheProcess.{$index}.sock";
    }

    /**
     * 发送并等待返回
     * @param $socketFile
     * @param Package $package
     * @param $timeout
     * @return mixed|null
     */
    private function sendAndRecv($socketFile, Package $package, $timeout)
    {
        $client = new UnixClient($socketFile);
        $client->send(Protocol::pack(serialize($package)));
        $ret = $client->recv($timeout);
        if (!empty($ret)) {
            $ret = unserialize(Protocol::unpack($ret));
            if ($ret instanceof Package) {
                return $ret->getValue();
            }else {
                return $ret;
            }
        }
        return null;
    }

    /**
     * 进程广播
     * @param Package $command
     * @param float $timeout
     * @return array|mixed
     */
    private function broadcast(Package $command, $timeout = 0.1)
    {
        $info = [];
        $channel = new Channel($this->processNum + 1);
        for ($i = 1; $i <= $this->processNum; $i++) {
            go(function () use ($command, $channel, $i, $timeout) {
                $ret = $this->sendAndRecv($this->generateSocketByIndex($i), $command, $timeout);
                $channel->push([
                    $i => $ret
                ]);
            });
        }
        $start = microtime(true);
        while (1) {
            if (microtime(true) - $start > $timeout) {
                break;
            }
            $temp = $channel->pop($timeout);
            if (is_array($temp)) {
                $info += $temp;
                if (count($info) == $this->processNum) {
                    break;
                }
            }
        }
        return $info;
    }

    /**
     * 启动后就不允许更改设置
     * @throws RuntimeError
     */
    private function modifyCheck()
    {
        if ($this->run) {
            throw new RuntimeError('you can not modify configure after init process check');
        }
    }

    function hset($key, $field, $value, ?int $ttl = null, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HSET);
        $com->setValue($value);
        $com->setField($field);
        $com->setKey($key);
        $com->setOption($com::ACTION_TTL, $ttl);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hget($key, $field=null, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return null;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HGET);
        $com->setKey($key);
        $com->setField($field);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hdel($key, $field=null, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HDEL);
        $com->setKey($key);
        $com->setField($field);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hflush(float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HFLUSH);
        $this->broadcast($com, $timeout);
        return true;
    }

    function hkeys($key, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HKEYS);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hscan($key, $cursor=0, $limit=10, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HSCAN);
        $com->setKey($key);
        $com->setCursor($cursor);
        $com->setLimit($limit);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hsetnx($key, $field, $value, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HSETNX);
        $com->setKey($key);
        $com->setField($field);
        $com->setValue($value);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hExists($key, $field, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HEXISTS);
        $com->setKey($key);
        $com->setField($field);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hLen($key, float $timeout = 1.0) {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HLEN);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hIncrby($key, $field, $value, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HINCRBY);
        $com->setKey($key);
        $com->setField($field);
        $com->setValue($value);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hMset($key, $fieldValues, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HMSET);
        $com->setKey($key);
        $com->setFieldValues($fieldValues);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hMget($key, $fields, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HMGET);
        $com->setKey($key);
        $com->setFields($fields);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hVals($key, float $timeout = 1.0)
    {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HVALS);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }

    function hGetAll($key, float $timeout = 1.0) {
        if ($this->processNum <=0 ) {
            return false;
        }
        $com = new Package();
        $com->setCommand($com::ACTION_HGETALL);
        $com->setKey($key);
        return $this->sendAndRecv($this->generateSocket($key), $com, $timeout);
    }
}

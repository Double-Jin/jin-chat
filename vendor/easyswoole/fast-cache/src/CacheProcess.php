<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 16:06
 */

namespace EasySwoole\FastCache;

use EasySwoole\Component\Process\Exception;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\Spl\SplArray;
use SplQueue;
use Swoole\Coroutine\Socket;
use Throwable;

class CacheProcess extends AbstractUnixProcess
{
    /**
     * Spl数组存放当前的缓存内容
     * @var SplArray
     */
    protected $splArray;

    /**
     * 存放Spl队列
     * @var array
     */
    protected $queueArray = [];

    /**
     * 带有过期时间的Key
     * @var array
     */
    protected $ttlKeys = [];

    /**
     * 分配的任务id
     * @var array
     */
    protected $jobIds = [];
    /**
     * 可以执行的任务
     * @var array
     */
    protected $readyJob = [];
    /**
     * 延迟执行的任务
     * @var array
     */
    protected $delayJob = [];
    /**
     * 保留任务（正在执行还未确认结果）
     * @var array
     */
    protected $reserveJob = [];
    /**
     * 埋藏状态的任务
     * @var array
     */
    protected $buryJob = [];

    /**
     * hash相关
     * @var array
     */
    protected $hashMap = [];

    /**
     * 进程初始化并开始监听Socket
     * @param $args
     * @throws Exception
     */
    public function run($args)
    {
        /** @var $processConfig CacheProcessConfig */
        $processConfig = $this->getConfig();
        ini_set('memory_limit',$processConfig->getMaxMem());
        $this->splArray = new SplArray();

        // 进程启动时执行
        if (is_callable($processConfig->getOnStart())) {
            try {
                $ret = call_user_func($processConfig->getOnStart(),$processConfig);
                if ($ret instanceof SyncData) {
                    $this->splArray   = $ret->getArray();
                    $this->queueArray = $ret->getQueueArray();
                    $this->ttlKeys    = $ret->getTtlKeys();
                    // queue 支持
                    $this->jobIds     = $ret->getJobIds();
                    $this->readyJob   = $ret->getReadyJob();
                    $this->delayJob   = $ret->getDelayJob();
                    $this->reserveJob = $ret->getReserveJob();
                    $this->buryJob    = $ret->getBuryJob();
                    $this->hashMap    = $ret->getHashMap();
                }
            } catch (Throwable $throwable) {
                $this->onException($throwable);
            }
        }

        // 设定落地时间定时器
        if (is_callable($processConfig->getOnTick())) {
            $this->addTick($processConfig->getTickInterval(), function () use ($processConfig) {
                try {
                    $data = new SyncData();
                    $data->setArray($this->splArray);
                    $data->setQueueArray($this->queueArray);
                    $data->setTtlKeys($this->ttlKeys);
                    // queue 支持
                    $data->setJobIds($this->jobIds);
                    $data->setReadyJob($this->readyJob);
                    $data->setReserveJob($this->reserveJob);
                    $data->setDelayJob($this->delayJob);
                    $data->setBuryJob($this->buryJob);
                    $data->setHashMap($this->hashMap);
                    call_user_func($processConfig->getOnTick(), $data,$processConfig);
                } catch (Throwable $throwable) {
                    $this->onException($throwable);
                }
            });
        }

        // 过期Key自动回收(至少499ms执行一次保证1秒内执行2次过期判断)
        $this->addTick(499, function () use ($processConfig) {
            try {
                if (!empty($this->ttlKeys)) {
                    mt_srand();
                    $keys = array_keys($this->ttlKeys);
                    shuffle($keys);
                    $checkKeys = array_slice($keys, 0, 100);  // 每次随机检查100个过期
                    if (is_array($checkKeys) && count($checkKeys) > 0) {
                        foreach ($checkKeys as $ttlKey) {
                            $ttlExpire = $this->ttlKeys[$ttlKey];
                            if ($ttlExpire < time()) {
                                unset($this->ttlKeys[$ttlKey], $this->splArray[$ttlKey]);
                            }
                        }
                    }
                }

                // 检测消息队列可执行性
                foreach ($this->delayJob as $queueName => $jobs){
                    /** @var Job $job */
                    foreach ($jobs as $jobKey => $job){
                        // 是否可以执行
                        if ($job->getNextDoTime() <= time()){
                            $canDo = $this->delayJob[$queueName][$jobKey];
                            unset($this->delayJob[$queueName][$jobKey]);
                            $this->readyJob[$queueName]["_".$job->getJobId()] = $canDo;
                        }
                    }
                }

                // 检测保留任务是否超时
                foreach ($this->reserveJob as $queueName => $jobs){
                    /** @var Job $job */
                    foreach ($jobs as $jobKey => $job){
                        // 取出时间 + 超时时间 < 当前时间 则放回ready
                        if ($job->getReserveTime() + $processConfig->getQueueReserveTime() < time()){
                            $readyJob = $this->reserveJob[$queueName][$jobKey];
                            unset($this->reserveJob[$queueName][$jobKey]);
                            // 判断最大重发次数
                            $releaseTimes = $job->getReleaseTimes();
                            if ($releaseTimes < $processConfig->getQueueMaxReleaseTimes()){
                                $job->setReleaseTimes(++$releaseTimes);
                                // 如果是延迟队列 更新nextDoTime
                                if ($job->getDelay() > 0){
                                    $job->setNextDoTime(time() + $job->getDelay());
                                }
                                $this->readyJob[$queueName]["_".$job->getJobId()] = $readyJob;
                            }
                        }
                    }
                }

            } catch (Throwable $throwable) {
                $this->onException($throwable);
            }
        });

        parent::run($processConfig);
    }

    /**
     * 初始化Spl队列池
     * @param $key
     * @return SplQueue
     */
    private function initQueue($key): SplQueue
    {
        if (!isset($this->queueArray[$key])) {
            $this->queueArray[$key] = new SplQueue();
        }
        return $this->queueArray[$key];
    }

    /**
     * 获取当前Spl队列池
     * @return array
     */
    public function getQueueArray(): array
    {
        return $this->queueArray;
    }

    /**
     * 设置当前Spl队列池
     * @param array $queueArray
     */
    public function setQueueArray(array $queueArray): void
    {
        $this->queueArray = $queueArray;
    }

    /**
     * 获取当前Spl数组
     * @return mixed
     */
    public function getSplArray()
    {
        return $this->splArray;
    }

    /**
     * 设置当前Spl数组
     * @param mixed $splArray
     */
    public function setSplArray($splArray): void
    {
        $this->splArray = $splArray;
    }

    /**
     * 进程退出时落地数据
     * @return void
     */
    public function onShutDown()
    {
        $onShutdown = $this->getConfig()->getOnShutdown();
        if (is_callable($onShutdown)) {
            try {
                $data = new SyncData();
                $data->setArray($this->splArray);
                $data->setQueueArray($this->queueArray);
                $data->setTtlKeys($this->ttlKeys);
                // queue 支持
                $data->setJobIds($this->jobIds);
                $data->setReadyJob($this->readyJob);
                $data->setReserveJob($this->reserveJob);
                $data->setDelayJob($this->delayJob);
                $data->setBuryJob($this->buryJob);
                $data->setHashMap($this->buryJob);
                call_user_func($onShutdown, $data,$this->getConfig());
            } catch (Throwable $throwable) {
                $this->onException($throwable);
            }
        }
    }

    /**
     * UnixClientAccept
     * @param Socket $socket
     */
    public function onAccept(Socket $socket)
    {
        // 收取包头4字节计算包长度 收不到4字节包头丢弃该包
        $header = $socket->recvAll(4, 1);
        if (strlen($header) != 4) {
            $socket->close();
            return;
        }

        // 收包头声明的包长度 包长一致进入命令处理流程
        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength, 1);
        if (strlen($data) == $allLength) {
            $replyPackage = $this->executeCommand($data);
            $socket->sendAll(Protocol::pack(serialize($replyPackage)));
            $socket->close();
        }

        // 否则丢弃该包不进行处理
        $socket->close();
        return;
    }

    /**
     * 异常处理
     * @param Throwable $throwable
     * @param mixed ...$args
     */
    protected function onException(Throwable $throwable, ...$args)
    {
        trigger_error("{$throwable->getMessage()} at file:{$throwable->getFile()} line:{$throwable->getLine()}");
    }

    /**
     * 执行命令
     * @param $commandPayload
     * @return mixed
     */
    protected function executeCommand(?string $commandPayload)
    {
        // $replyPackage = new Package();
        $replayData  = null;
        $fromPackage = unserialize($commandPayload);
        if ($fromPackage instanceof Package) { // 进入业务处理流程
            switch ($fromPackage->getCommand()) {
                case $fromPackage::ACTION_SET:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $value = $fromPackage->getValue();
                        // 按照redis的逻辑 当前key没有过期 set不会重置ttl 已过期则重新设置
                        $ttl = $fromPackage->getOption($fromPackage::ACTION_TTL);
                        if (!array_key_exists($key, $this->ttlKeys) || $this->ttlKeys[$key] < time()) {
                            if (!is_null($ttl)) {
                                $this->ttlKeys[$key] = time() + $ttl;
                            }
                        }
                        $this->splArray->set($key, $value);
                        break;
                    }
                case $fromPackage::ACTION_GET:
                    {
                        $key = $fromPackage->getKey();
                        // 取出之前需要先判断当前是否有ttl 如果有ttl设置并且已经过期 立刻删除key
                        if (array_key_exists($key, $this->ttlKeys) && $this->ttlKeys[$key] < time()) {
                            unset($this->ttlKeys[$key]);
                            $this->splArray->unset($key);
                            $replayData = null;
                        } else {
                            $replayData = $this->splArray->get($fromPackage->getKey());
                        }
                        break;
                    }
                case $fromPackage::ACTION_UNSET:
                    {
                        $replayData = true;
                        unset($this->ttlKeys[$fromPackage->getKey()]); // 同时移除TTL
                        $this->splArray->unset($fromPackage->getKey());
                        break;
                    }
                case $fromPackage::ACTION_KEYS:
                    {
                        $key = $fromPackage->getKey();
                        $keys = $this->splArray->keys($key);
                        $time = time();
                        foreach ($this->ttlKeys as $ttlKey => $ttl) {
                            if ($ttl < $time) {
                                unset($keys[$ttlKey], $this->ttlKeys[$ttlKey]);  // 立刻释放过期的ttlKey
                            }
                        }
                        $replayData = $this->splArray->keys($key);
                        break;
                    }
                case $fromPackage::ACTION_FLUSH:
                    {
                        $replayData = true;
                        $this->ttlKeys = [];  // 同时移除全部TTL时间
                        $this->splArray = new SplArray();
                        $this->buryJob = [];
                        $this->readyJob = [];
                        $this->delayJob = [];
                        $this->reserveJob = [];
                        break;
                    }
                case $fromPackage::ACTION_EXPIRE:
                    {
                        $replayData = false;
                        $key = $fromPackage->getKey();
                        $ttl = $fromPackage->getOption($fromPackage::ACTION_TTL);
                        // 不能给当前没有的Key设置TTL
                        if (array_key_exists($key, $this->splArray)) {
                            if (!is_null($ttl)) {
                                $this->ttlKeys[$key] = time() + $ttl;
                                $replayData = true;
                            }
                        }

                        break;
                    }
                case $fromPackage::ACTION_PERSISTS:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        unset($this->ttlKeys[$key]);
                        break;
                    }
                case $fromPackage::ACTION_TTL:
                    {
                        $replayData = null;
                        $key = $fromPackage->getKey();
                        $time = time();

                        // 不能查询当前没有的Key
                        if (array_key_exists($key, $this->splArray) && array_key_exists($key, $this->ttlKeys)) {
                            $expire = $this->ttlKeys[$key];
                            if ($expire > $time) {  // 有剩余时间时才会返回剩余ttl 否则返回null表示已经过期或未设置 不区分主动过期和key不存在的情况
                                $replayData = $expire - $time;
                            }
                        }
                        break;
                    }
                case $fromPackage::ACTION_ENQUEUE:
                    {
                        $que = $this->initQueue($fromPackage->getKey());
                        $data = $fromPackage->getValue();
                        if ($data !== null) {
                            $que->enqueue($fromPackage->getValue());
                            $replayData = true;
                        } else {
                            $replayData = false;
                        }
                        break;
                    }
                case $fromPackage::ACTION_DEQUEUE:
                    {
                        $que = $this->initQueue($fromPackage->getKey());
                        if ($que->isEmpty()) {
                            $replayData = null;
                        } else {
                            $replayData = $que->dequeue();
                        }
                        break;
                    }
                case $fromPackage::ACTION_QUEUE_SIZE:
                    {
                        $que = $this->initQueue($fromPackage->getKey());
                        $replayData = $que->count();
                        break;
                    }
                case $fromPackage::ACTION_UNSET_QUEUE:
                    {
                        if (isset($this->queueArray[$fromPackage->getKey()])) {
                            unset($this->queueArray[$fromPackage->getKey()]);
                            $replayData = true;
                        } else {
                            $replayData = false;
                        }
                        break;
                    }
                case $fromPackage::ACTION_QUEUE_LIST:
                    {
                        $replayData = array_keys($this->queueArray);
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_QUEUE:
                    {
                        $this->queueArray = [];
                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_PUT_JOB:
                    {
                        // 设置jobId 储存
                        /** @var Job $job */
                        $job       = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = $this->getJobId($queueName);

                        $job->setJobId($jobId);

                        $jobKey = "_".$jobId;
                        // 判断是否为延迟队列
                        if ($job->getDelay() > 0){
                            $job->setNextDoTime(time() + $job->getDelay());
                            $this->delayJob[$queueName][$jobKey] = $job;
                        }else{
                            $this->readyJob[$queueName][$jobKey] = $job;
                        }

                        $replayData = $jobId;
                        break;
                    }
                case $fromPackage::ACTION_GET_JOB:
                    {
                        $queueName = $fromPackage->getValue();
                        if (!empty($this->readyJob[$queueName])){
                            /** @var Job $job */
                            $job = array_shift($this->readyJob[$queueName]);
                            // 设置reserveTime 放到reserveJob队列
                            $job->setReserveTime(time());
                            $jobId = "_". $job->getJobId();
                            $this->reserveJob[$queueName][$jobId] = $job;
                        }else{
                            $job = null;
                        }
                        $replayData = $job;
                        break;
                    }

                case $fromPackage::ACTION_DELAY_JOB:
                    {
                        /** @var Job $job */
                        $job = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = "_".$job->getJobId();

                        $delay = $job->getDelay();

                        $job = $this->readyJob[$queueName][$jobId] ?? $this->reserveJob[$queueName][$jobId]
                            ?? $this->buryJob[$queueName][$jobId];


                        if (!$job){
                            $replayData = false;
                            break;
                        }
                        $job->setDelay($delay);
                        if ($job->getDelay() == 0){
                            $replayData = false;
                            break;
                        }
                        $job->setNextDoTime(time() + $job->getDelay());


                        $this->delayJob[$queueName][$jobId] = $job;

                        unset($this->readyJob[$queueName][$jobId]);
                        unset($this->reserveJob[$queueName][$jobId]);
                        unset($this->buryJob[$queueName][$jobId]);


                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_GET_DELAY_JOB:
                    {
                        /** @var Job $job */
                        $queueName = $fromPackage->getValue();

                        if (isset($this->delayJob[$queueName])){
                            $job = array_shift($this->delayJob[$queueName]);
                        }else{
                            $job = null;
                        }
                        $replayData = $job;
                        break;
                    }
                case $fromPackage::ACTION_GET_RESERVE_JOB:
                    {
                        // 从保留任务中拿取
                        /** @var Job $job */
                        $queueName = $fromPackage->getValue();

                        if (isset($this->reserveJob[$queueName])){
                            $job = array_shift($this->reserveJob[$queueName]);
                        }else{
                            $job = null;
                        }
                        $replayData = $job;
                        break;
                        break;
                    }
                case $fromPackage::ACTION_DELETE_JOB:
                    {
                        /** @var Job $job */
                        $job       = $fromPackage->getValue();
                        $jobId     = "_".$job->getJobId();
                        $queueName = $job->getQueue();
                        if (isset($this->readyJob[$queueName][$jobId])){
                            unset($this->readyJob[$queueName][$jobId]);
                            $replayData = true;
                            break;
                        }
                        if (isset($this->reserveJob[$queueName][$jobId])){
                            unset($this->reserveJob[$queueName][$jobId]);
                            $replayData = true;
                            break;
                        }
                        if (isset($this->delayJob[$queueName][$jobId])){
                            unset($this->delayJob[$queueName][$jobId]);
                            $replayData = true;
                            break;
                        }
                        if (isset($this->buryJob[$queueName][$jobId])){
                            unset($this->buryJob[$queueName][$jobId]);
                            $replayData = true;
                            break;
                        }
                        $replayData = false;
                        break;
                    }
                case $fromPackage::ACTION_JOB_QUEUES:
                    {
                        $readyJob   = array_keys($this->readyJob);
                        $delayJob   = array_keys($this->delayJob);
                        $reserveJob = array_keys($this->reserveJob);
                        $buryJob    = array_keys($this->buryJob);

                        $queue   = array_unique(array_merge($readyJob, $delayJob, $reserveJob, $buryJob));
                        $replayData = $queue;
                        break;
                    }
                case $fromPackage::ACTION_JOB_QUEUE_SIZE:
                    {
                        $queueName = $fromPackage->getValue();
                        $return = [
                            'ready'   => isset($this->readyJob[$queueName]) ? count($this->readyJob[$queueName]) : 0,
                            'delay'   => isset($this->delayJob[$queueName]) ? count($this->delayJob[$queueName]) : 0,
                            'reserve' => isset($this->reserveJob[$queueName]) ? count($this->reserveJob[$queueName]) : 0,
                            'bury'    => isset($this->buryJob[$queueName]) ? count($this->buryJob[$queueName])  :0,
                        ];
                        $replayData = $return;
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if ($queueName === null){
                            $this->readyJob    = [];
                            $this->delayJob    = [];
                            $this->reserveJob  = [];
                            $this->buryJob     = [];
                        }else{
                            unset($this->readyJob[$queueName]);
                            unset($this->delayJob[$queueName]);
                            unset($this->reserveJob[$queueName]);
                            unset($this->buryJob[$queueName]);
                        }

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_READY_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if ($queueName === null){
                            $this->readyJob    = [];
                        }else{
                            unset($this->readyJob[$queueName]);
                        }

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_RESERVE_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if ($queueName === null){
                            $this->reserveJob    = [];
                        }else{
                            unset($this->reserveJob[$queueName]);
                        }

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_BURY_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if ($queueName === null){
                            $this->buryJob    = [];
                        }else{
                            unset($this->buryJob[$queueName]);
                        }

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_FLUSH_DELAY_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if ($queueName === null){
                            $this->delayJob    = [];
                        }else{
                            unset($this->delayJob[$queueName]);
                        }

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_RELEASE_JOB:
                    {
                        /** @var Job $job */
                        $job = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = $job->getJobId();
                        $jobKey    = "_".$jobId;
                        $delay     = $job->getDelay();

                        // $job需要重新取 兼容手动提供queueNam和jobId来重发任务
                        $job = $this->readyJob[$queueName][$jobKey] ?? $this->delayJob[$queueName][$jobKey]
                            ?? $this->reserveJob[$queueName][$jobKey] ?? $this->buryJob[$queueName][$jobKey];

                        // 没有该任务
                        if (!$job){
                            $replayData = false;
                            break;
                        }
                        $job->setDelay($delay);

                        unset($this->readyJob[$queueName][$jobKey]);
                        unset($this->delayJob[$queueName][$jobKey]);
                        unset($this->reserveJob[$queueName][$jobKey]);
                        unset($this->buryJob[$queueName][$jobKey]);

                        // 是否达到最大重发次数
                        /** @var $processConfig CacheProcessConfig */
                        $processConfig = $this->getConfig();
                        if ($job->getReleaseTimes() > $processConfig->getQueueMaxReleaseTimes()){
                            $replayData = false;
                            break;
                        }

                        $releaseTimes = $job->getReleaseTimes();
                        $job->setReleaseTimes(++$releaseTimes);

                        // 判断是否为延迟队列
                        if ($job->getDelay() > 0){
                            $job->setNextDoTime(time() + $job->getDelay());
                            $this->delayJob[$queueName][$jobKey] = $job;
                        }else{
                            $this->readyJob[$queueName][$jobKey] = $job;
                        }

                        $replayData = $jobId;
                        break;
                    }
                case $fromPackage::ACTION_RESERVE_JOB:
                    {
                        /** @var Job $job */
                        $job = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = "_".$job->getJobId();

                        $job = $this->readyJob[$queueName][$jobId] ?? $this->delayJob[$queueName][$jobId]
                            ?? $this->buryJob[$queueName][$jobId];

                        if (!$job){
                            $replayData = false;
                            break;
                        }

                        $job->setReserveTime(time());

                        $this->reserveJob[$queueName][$jobId] = $job;

                        unset($this->readyJob[$queueName][$jobId]);
                        unset($this->delayJob[$queueName][$jobId]);
                        unset($this->buryJob[$queueName][$jobId]);

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_BURY_JOB:
                    {
                        /** @var Job $job */
                        $job = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = $job->getJobId();
                        $jobKey    = "_".$jobId;

                        // 重新拿job 兼容手动传递jobId来bury
                        $job = $this->readyJob[$queueName][$jobKey] ?? $this->delayJob[$queueName][$jobKey]
                            ?? $this->reserveJob[$queueName][$jobKey];

                        $this->buryJob[$queueName][$jobKey] = $job;

                        unset($this->readyJob[$queueName][$jobKey]);
                        unset($this->delayJob[$queueName][$jobKey]);
                        unset($this->reserveJob[$queueName][$jobKey]);

                        $replayData = true;
                        break;
                    }
                case $fromPackage::ACTION_GET_BURY_JOB:
                    {
                        $queueName = $fromPackage->getValue();

                        if (isset($this->buryJob[$queueName])){
                            $job = array_shift($this->buryJob[$queueName]);
                        }else{
                            $job = null;
                        }

                        $replayData = $job;
                        break;
                    }
                case $fromPackage::ACTION_KICK_JOB:
                    {
                        /** @var Job $job */
                        $job = $fromPackage->getValue();
                        $queueName = $job->getQueue();
                        $jobId     = $job->getJobId();
                        $jobKey    = "_".$jobId;

                        if (isset($this->buryJob[$queueName][$jobKey])){
                            $readyJob = $this->buryJob[$queueName][$jobKey];
                            unset($this->buryJob[$queueName][$jobKey]);
                            $this->readyJob[$queueName][$jobKey] = $readyJob;
                            $replayData = true;
                        }else{
                            $replayData = false;
                        }
                        break;

                    }
                case $fromPackage::ACTION_HSET:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        $value = $fromPackage->getValue();
                        if (empty($field) || empty($value)) {
                            $replayData = false;
                        } else {
                            $this->hashMap[$key][$field] = $value;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HGET:
                    {
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        if (empty($key)) {
                            $replayData = null;
                        } elseif (empty($field)) {
                            $replayData = $this->hashMap[$key];
                        } else {
                            $replayData = $this->hashMap[$key][$field];
                        }
                        break;
                    }
                case $fromPackage::ACTION_HDEL:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        unset($this->ttlKeys[$key]); // 同时移除TTL
                        if (empty($key)) {
                            $replayData = false;
                        } else if (empty($field)) {
                            unset($this->hashMap[$key]);
                        } else {
                            unset($this->hashMap[$key][$field]);
                        }
                        break;
                    }
                case $fromPackage::ACTION_HFLUSH:
                    {
                        foreach ($this->hashMap as $key => $val) {
                            unset($this->ttlKeys[$key]);
                        }
                        $this->hashMap = [];
                        break;
                    }
                case $fromPackage::ACTION_HKEYS:
                    {
                        $replayData=null;
                        $key = $fromPackage->getKey();
                        if (!empty($this->hashMap[$key])) {
                            $replayData = array_keys($this->hashMap[$key]);
                        }
                        break;
                    }
                case $fromPackage::ACTION_HSCAN:
                    {
                        $replayData=null;
                        $key = $fromPackage->getKey();
                        $limit = $fromPackage->getLimit();
                        $cursor = $fromPackage->getCursor();
                        if (!empty($this->hashMap[$key])) {
                            $replayData = array_slice($this->hashMap[$key], $cursor, $limit);
                            if (count($replayData) < $limit) {
                                $replayData = [
                                    'data' => $replayData,
                                    'cursor' => 0
                                ];
                            } else {
                                $replayData  = [
                                    'data' => $replayData,
                                    'cursor' => $cursor+$limit
                                ];
                            }
                        }
                        break;
                    }
                case $fromPackage::ACTION_HSETNX:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        $value = $fromPackage->getValue();
                        if (empty($this->hashMap[$key]) || empty($this->hashMap[$key][$value])) {
                            $this->hashMap[$key][$field] = $value;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HEXISTS:
                    {
                        $replayData = false;
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        if (isset($this->hashMap[$key][$field])) {
                            $replayData = true;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HLEN:
                    {
                        $replayData = 0;
                        $key = $fromPackage->getKey();
                        if (isset($this->hashMap[$key])) {
                            $replayData = count($this->hashMap[$key]);
                        }
                        break;
                    }
                case $fromPackage::ACTION_HINCRBY:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $field = $fromPackage->getField();
                        $value = $fromPackage->getValue();

                        if (isset($this->hashMap[$key][$field])) {
                            if (is_numeric($this->hashMap[$key][$field])) {
                                $this->hashMap[$key][$field] += $value;
                            } else {
                                $replayData = false;
                            }
                        } else {
                            $this->hashMap[$key][$field] = $value;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HMSET:
                    {
                        $replayData = true;
                        $key = $fromPackage->getKey();
                        $fieldValues = $fromPackage->getFieldValues();
                        foreach ($fieldValues as $field => $value) {
                            $this->hashMap[$key][$field] = $value;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HMGET:
                    {
                        $replayData = [];
                        $key = $fromPackage->getKey();
                        $fields = $fromPackage->getFields();
                        foreach ($fields as $field) {
                            $replayData[$field] = $this->hashMap[$key][$field]??null;
                        }
                        break;
                    }
                case $fromPackage::ACTION_HVALS:
                    {
                        $key = $fromPackage->getKey();
                        $replayData = array_values($this->hashMap[$key]??[]);
                        break;
                    }
                case $fromPackage::ACTION_HGETALL:
                    {
                        $replayData = [];
                        $key = $fromPackage->getKey();
                        foreach ($this->hashMap[$key]??[] as $field => $value) {
                            $replayData[] = $field;
                            $replayData[] = $value;
                        }
                        break;
                    }
            }
        }
        return $replayData;
    }

    /**
     * 根据队列名获取jobId
     * @param string $queueName
     * @return int
     */
    private function getJobId($queueName)
    {
        if (!isset($this->jobIds[$queueName])){
            $this->jobIds[$queueName] = 0;
        }

        return ++$this->jobIds[$queueName];
    }
}
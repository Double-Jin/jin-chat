<?php

namespace EasySwoole\EasySwoole;


use App\WebSocket\WebSocketEvents;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\FastCache\Cache;
use EasySwoole\FileWatcher\FileWatcher;
use EasySwoole\FileWatcher\WatchRule;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Socket\Dispatcher;
use App\WebSocket\WebSocketParser;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');

        /**
         * orm 注册
         */
        $dbConfig = new \EasySwoole\ORM\Db\Config(Config::getInstance()->getConf('MYSQL'));
        $dbConfig->setMaxObjectNum(20)->setMinObjectNum(5);
        $connection = new Connection($dbConfig);
        DbManager::getInstance()->addConnection($connection);

        /**
         * redis 注册
         */
        $rdConfig = new \EasySwoole\Redis\Config\RedisConfig(Config::getInstance()->getConf('REDIS'));
        \EasySwoole\RedisPool\RedisPool::getInstance()->register($rdConfig);

    }

    public static function mainServerCreate(EventRegister $register)
    {

        /**
         * **************** websocket控制器 **********************
         */
        // 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $conf->setParser(new WebSocketParser());
        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($conf);
        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\Swoole\Websocket\Server $server, \Swoole\WebSocket\Frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });
        // 注册服务事件
        $register->add(EventRegister::onOpen, [WebSocketEvents::class, 'onOpen']);
        $register->add(EventRegister::onClose, [WebSocketEvents::class, 'onClose']);

        /**
         * ****************   服务热重启    ****************
         */
        $fileWatcher = new FileWatcher();
        $rule = new WatchRule(EASYSWOOLE_ROOT . '/App');
        $fileWatcher->addRule($rule);
        $fileWatcher->setOnChange(function (){
            Logger::getInstance()->info('file change,reload!!!');
            ServerManager::getInstance()->getSwooleServer()->reload();
        });
        $fileWatcher->attachServer(ServerManager::getInstance()->getSwooleServer());

        /**
         * ****************   缓存服务    ****************
         */
        Cache::getInstance()->setTempDir(EASYSWOOLE_TEMP_DIR)->attachToServer(ServerManager::getInstance()->getSwooleServer());
    }
}

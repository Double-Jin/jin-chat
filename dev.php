<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-01
 * Time: 20:06
 */

return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9501,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SOCKET_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time' => 3,
            'document_root' => EASYSWOOLE_ROOT, // nginx 下 无需配置这两行
            'enable_static_handler' => true,
        ],
        'TASK' => [
            'workerNum' => 4,
            'maxRunningNum' => 128,
            'timeout' => 15
        ]
    ],
    'TEMP_DIR' => '/tmp/',
    'LOG_DIR' => null,

    'MYSQL' => [
        'host' => '127.0.0.1',//防止报错,就不切换数据库了
        'port' => '3306',
        'user' => 'root',//数据库用户名
        'password' => 'gaobinzhan',//数据库密码
        'database' => 'chat',//数据库
        'timeout' => '5',
        'charset' => 'utf8',
        'returnCollection' => true
    ],

    /*################ REDIS CONFIG ##################*/
    'REDIS' => [
        'host' => '127.0.0.1',
        'port' => '6379',
        'auth' => ''
    ],

    'FAST_CACHE' => [//fastCache组件
        'PROCESS_NUM' => 1//进程数,大于0才开启
    ],
];

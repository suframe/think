<?php

use think\swoole\websocket\room\TableRoom;
use think\swoole\websocket\socketio\Handler;
use think\swoole\websocket\socketio\Parser;

return [
    'server'           => [
        'host'      => '127.0.0.1', // 监听地址
        'port'      => 9506, // 监听端口
        'mode'      => SWOOLE_PROCESS, // 运行模式 默认为SWOOLE_PROCESS
        'sock_type' => SWOOLE_SOCK_TCP, // sock type 默认为SWOOLE_SOCK_TCP
        'options'   => [
            'pid_file'              => runtime_path() . 'swooleTcp.pid',
            'log_file'              => runtime_path() . 'swooleTcp.log',
            'daemonize'             => false,
            // Normally this value should be 1~4 times larger according to your cpu cores.
            'reactor_num'           => swoole_cpu_num(),
            'worker_num'            => swoole_cpu_num(),
            'task_worker_num'       => swoole_cpu_num(),
            'enable_static_handler' => true,
            'document_root'         => root_path('public'),
            'package_max_length'    => 20 * 1024 * 1024,
            'buffer_output_size'    => 10 * 1024 * 1024,
            'socket_buffer_size'    => 128 * 1024 * 1024,
            'max_request'           => 3000,
            'send_yield'            => true,
        ],
    ],
    'app' => [
        'path' => '/demo',
        'rpcPath' => app_path() . 'rpc' . DIRECTORY_SEPARATOR,
        'configPath' => config_path() . DIRECTORY_SEPARATOR,
        'apiNameSpace' => '\app\api\\',
        'rpcNameSpace' => '\app\rpc\\',
    ],
    'registerServer' => [
        'ip' => '127.0.0.1',
        'port' => 9500
    ],
    'hot_update'       => [
        'enable'  => env('app_debug', false),
        'name'    => ['*.php'],
        'include' => [app_path()],
        'exclude' => [],
    ],
    'enable_coroutine' => true,
    'resetters'        => [],
    'tables'           => [],
];

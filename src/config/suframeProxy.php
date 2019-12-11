<?php

return [
    'driver' => 'suframe',
    'path' => '/news',
    'name' => 'news', //用于接口生成命名空间
    'host' => '192.168.0.41',
    'port' => '8081',
    'rpcPort' => '9009',
    'timeout' => 2.5,
    'registerServer' => [
        'ip' => '127.0.0.1',
        'port' => 9500
    ],
    'services' => [
        'suframe' => \suframe\think\services\SuframeService::class
    ]
];

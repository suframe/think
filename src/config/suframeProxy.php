<?php

return [
    'driver' => 'suframe',
    'path' => '/user',
    'name' => 'user', //用于接口生成命名空间
    'host' => '127.0.0.1',
    'port' => '8081',
    'rpcPort' => '9009',
    'timeout' => 2.5,
    'apiGetway' => [
        'enable' => false,
        'host' => '',
        'port' => '',
    ],
    'registerServer' => [
        'host' => '127.0.0.1',
        'port' => 9009
    ],
    'services' => [
        'suframe' => \suframe\think\services\SuframeService::class
    ]
];

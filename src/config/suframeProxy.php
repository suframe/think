<?php

return [
    'driver' => 'suframe',
    'path' => env('suframeProxy.path', '/demo'),
    'name' => env('suframeProxy.name', 'demo'), //用于接口生成命名空间
    'host' => env('SWOOLE_HOST', '127.0.0.1'),
    'port' => env('SWOOLE_PORT', '8200'),
    'rpcPort' => env('SWOOLE_RPC_PORT', '9200'),
    'timeout' => 2.5,
    'apiGetway' => [
        'enable' => false,
        'host' => '',
        'port' => '',
    ],
    'registerServer' => [
        'host' => env('suframeProxy.registerHost', '127.0.0.1'),
        'port' => env('suframeProxy.registerPort', '9200')
    ],
    'services' => [
        'suframe' => \suframe\think\services\SuframeService::class
    ]
];

<?php

return [
    'driver' => 'suframe',
    'path' => '/user',
    'name' => 'user', //用于接口生成命名空间
    'host' => env('suframeProxy.host', '127.0.0.1'),
    'port' => env('suframeProxy.port', '8200'),
    'rpcPort' => env('suframeProxy.rpcPort', '9200'),
    'timeout' => 2.5,
    'apiGetway' => [
        'enable' => false,
        'host' => '',
        'port' => '',
    ],
    'registerServer' => [
        'host' => env('suframeProxy.registerServerHost', '127.0.0.1'),
        'port' => env('suframeProxy.registerServerPort', '9200')
    ],
    'services' => [
        'suframe' => \suframe\think\services\SuframeService::class
    ]
];

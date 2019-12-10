<?php

return [
    'driver' => 'suframe',
    'path' => '/news',
    'host' => '192.168.0.41',
    'port' => '8081',
    'rpcPort' => '9009',
    'registerServer' => [
        'ip' => '127.0.0.1',
        'port' => 9500
    ],
    'services' => [
        'suframe' => \suframe\think\services\SuframeService::class
    ]
];

<?php
$client = new swoole_client(SWOOLE_SOCK_TCP);
if (!$client->connect('127.0.0.1', 9506, -1))
{
    exit("connect failed. Error: {$client->errCode}\n");
}
$data = [
    'path' => '/rpc/Hello',
    'arguments' => [
        'shuai ge'
    ]
];
$data = json_encode($data);
$client->send($data);
echo $client->recv();
$client->close();
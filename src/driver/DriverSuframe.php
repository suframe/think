<?php

namespace suframe\think\driver;

use think\swoole\rpc\client\Client;
use think\swoole\rpc\JsonParser;

/**
 * suframe proxy driver
 * Class DriverSuframe
 * @package suframe\think\driver
 */
class DriverSuframe implements DriverInterface
{

    public function register(array $config): bool
    {
        $swoole = config('swoole');
        $server = $swoole['server'] ?? [];
        if (!$server) {
            return false;
        }
        //注册接口
        $post = [
            'path' => $config['path'],
            'name' => $config['name'],
            'host' => $config['host'],
            'port' => $config['port'],
            'rpcPort' => $config['rpcPort'],
        ];
        go(function () use ($config, $post) {
            $result = app()->invoke([app()->make($config['services']['suframe']), 'register'], ['data' => $post]);
            echo 'services register ' . $result . "\n";
        });
        return true;
    }

    /**
     * @param array $clients
     * @param array $data
     * @return bool
     * @throws \think\swoole\exception\RpcClientException
     */
    public function notify(array $clientss = [], array $data = []): bool
    {
        if (!$clientss) {
            return false;
        }
        foreach ($clientss as $name => $client) {
            $interface = "\\rpc\\contract\\{$name}\\SuframeInterface";

            if (!interface_exists($interface)) {
                //引入rpc接口文件
                if (file_exists($rpc = app()->getBasePath() . 'rpc.php')) {
                    include_once $rpc;
                }
            }
            go(function () use ($data, $client) {
                try{
                    $rpcClient = new Client($client['host'], $client['port']);
                    $param = [
                        'jsonrpc' => JsonParser::VERSION,
                        'method' => 'SuframeInterface' . JsonParser::DELIMITER . 'register',
                        'params' => ['data' => $data]
                    ];
                    $param = json_encode($param, JSON_UNESCAPED_UNICODE);
                    $response = $rpcClient->sendAndRecv($param);
                    $response = json_decode($response, true);
                    if (!$response) {
                        return false;
                    }
                    echo "services notify {$client['host']}:{$client['port']}:" . $response['result'] . "\n";
                } catch (\Exception $e) {
                    echo "services notify {$client['host']}:{$client['port']}: error\n";
                }

            });

        }
        return true;
        // TODO: Implement notify() method.
    }

    public function registerApiGateway(array $config): bool
    {
        // TODO: 接入summer网关
    }
}
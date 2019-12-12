<?php

namespace suframe\think\driver;

use suframe\think\services\SuframeService;
use think\exception\ErrorException;
use think\swoole\exception\RpcClientException;
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
        $this->send($config['registerServer']['host'], $config['registerServer']['port'], $post, 'register');
        return true;
    }

    /**
     * @param array $clients
     * @param array $data
     * @return bool
     * @throws \think\swoole\exception\RpcClientException
     */
    public function notify(array $clients = []): bool
    {
        if (!$clients) {
            return false;
        }

        if ($clients) {
            foreach ($clients as $name => $client) {
                $interface = "\\rpc\\contract\\{$name}\\SuframeInterface";
                $this->send($client['host'], $client['port'], $clients, 'notify');
            }
        }
        return true;
    }

    protected function send($host, $port, $data, $action)
    {
        go(function () use ($host, $port, $data, $action) {
            try {
                $rpcClient = new Client($host, $port);
                $param = [
                    'jsonrpc' => JsonParser::VERSION,
                    'method' => 'SuframeInterface' . JsonParser::DELIMITER . $action,
                    'params' => ['data' => $data]
                ];
                $param = json_encode($param, JSON_UNESCAPED_UNICODE);
                $response = $rpcClient->sendAndRecv($param);
                $response = json_decode($response, true);
                if (!$response) {
                    return null;
                }
                echo "services notify {$host}:{$port}:" . $response['result'] . "\n";
                return $response;
            } catch (\Exception | ErrorException | RpcClientException $e) {
                echo "services {$action} {$host}:{$port}: error\n";
                return null;
            }
        });
    }

    public function registerApiGateway(array $config): bool
    {
        // TODO: 接入summer网关
    }
}
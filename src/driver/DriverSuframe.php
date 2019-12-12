<?php

namespace suframe\think\driver;

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
        foreach ($clients as $name => $client) {
            $interface = "\\rpc\\contract\\{$name}\\SuframeInterface";

            if (!interface_exists($interface)) {
                //引入rpc接口文件
                if (file_exists($rpc = app()->getBasePath() . 'rpc.php')) {
                    include_once $rpc;
                }
            }
            $this->send($client['host'], $client['port'], $clients, 'notify');
        }
        return true;
        // TODO: Implement notify() method.
    }

    protected function send($host, $port, $data, $action)
    {
        go(function () use ($data, $host, $port, $action) {
            try{
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
                    return false;
                }
                echo "services notify {$host}:{$port}:" . $response['result'] . "\n";
            } catch (\Exception | ErrorException | RpcClientException $e) {
                echo "services notify {$host}:{$port}: error\n";
            }
        });
    }

    public function registerApiGateway(array $config): bool
    {
        // TODO: 接入summer网关
    }
}
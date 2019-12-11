<?php

namespace suframe\think\driver;
use think\swoole\rpc\client\Client;
use think\swoole\rpc\client\Proxy;
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
    public function notify(array $clients, array $data): bool
    {
        if (!$clients) {
            return false;
        }

        foreach ($clients as $name => $client) {
            $interface = "rpc\\contract\\{$name}\\SuframeInterface";
            if(!interface_exists($interface)){
                continue;
            }
            $className = Proxy::getClassName($interface);
            $result = app()->invoke([app()->make($className), 'register'], ['data' => $data]);
            echo "services notify {$client['host']}:{$client['port']}:" . $result . "\n";
        }
        return true;
        // TODO: Implement notify() method.
    }

    public function registerApiGateway(array $config): bool
    {
        // TODO: 接入summer网关
    }
}
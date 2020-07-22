<?php

namespace suframe\think\driver;

use Swoole\Client;
use think\exception\ErrorException;
use think\swoole\exception\RpcClientException;
use think\swoole\rpc\JsonParser;
use think\swoole\rpc\Packer;

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
                $this->send($client['host'], $client['port'], $clients, 'notify');
            }
        }
        return true;
    }

    protected function send($host, $port, $data, $action)
    {
        $rpcClient = new Client(SWOOLE_SOCK_TCP);
        if (!$rpcClient->connect($host, $port, -1)) {
            return null;
        }
        $param = [
            'method' => 'SuframeInterface' . JsonParser::DELIMITER . $action,
            'params' => ['data' => $data]
        ];
        $param = json_encode($param, JSON_UNESCAPED_UNICODE);
        $param = Packer::pack($param);
        $response = '';
        $rpcClient->send($param);
        $response = $rpcClient->recv();
        [$header, $response] = Packer::unpack($response);

        $response = json_decode($response, true);
        $rpcClient->close();
        if (!$response) {
            echo "register services {$action} {$host}:{$port}:fail\n";
            return null;
        }
        echo "services {$action} {$host}:{$port}:" . $response['result'] . "\n";
        return $response;

    }

    public function registerApiGateway(array $config): bool
    {
        // TODO: 接入summer网关
    }
}
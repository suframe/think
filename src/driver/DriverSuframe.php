<?php
namespace suframe\think\driver;

/**
 * suframe proxy driver
 * Class DriverSuframe
 * @package suframe\think\driver
 */
class DriverSuframe implements DriverInterface
{

    public function run(array $config): bool
    {
        $swoole = config('swoole');
        $server = $swoole['server'] ?? [];
        if(!$server){
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
        go(function () use ($config, $post){
            $result = app()->invoke([app()->make($config['services']['suframe']), 'register'], ['data' => $post]);
            echo 'services register ' . $result . "\n";
        });
        return true;
    }

    public function register(array $data): bool
    {
        // TODO: Implement register() method.
        return true;
    }
}
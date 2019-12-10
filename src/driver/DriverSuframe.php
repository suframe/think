<?php
namespace suframe\think\driver;

/**
 * suframe proxy driver
 * Class DriverSuframe
 * @package suframe\think\driver
 */
class DriverSuframe
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
            'host' => $config['host'],
            'port' => $config['port'],
            'rpcPort' => $config['rpcPort'],
        ];
        go(function () use ($config, $post){
            $result = app()->invoke([app()->make($config['services']['suframe']), 'register'], ['data' => $post]);
            var_dump($result);
        });
        return true;
    }

}
<?php

namespace suframe\think;

use suframe\think\driver\DriverInterface;
use Swoole\Server as TcpServer;
use suframe\think\command\Rpc;

class Service extends \think\Service
{

    /**
     * @var TcpServer
     */
    protected static $server;

    /**
     * 注册服务
     */
    public function register()
    {
        if (!$this->app->runningInConsole()) {
            return true;
        }
        $this->commands(Rpc::class);
        $swoole = config('swoole');
        if(!isset($swoole['rpc'])){
            return false;
        }
        //增加suframe interface
        if (!$swoole['rpc']['server']['enable'] === true) {
            return false;
        }

        $suframeService = config('suframeProxy.services');
        $swoole['rpc']['server']['services'] = array_merge($swoole['rpc']['server']['services'], $suframeService);
        config($swoole, 'swoole');
    }

    public function boot()
    {
    }

    /**
     * @return DriverInterface
     * @throws \Exception
     */
    public static function getDirver() : DriverInterface
    {
        $driver = config('suframeProxy.driver');
        $dirverClass = __NAMESPACE__ . '\\driver\\Driver' . ucfirst($driver);
        if (!class_exists($dirverClass)) {
            throw new \Exception('need dirver');
        }
        return new $dirverClass;
    }

}

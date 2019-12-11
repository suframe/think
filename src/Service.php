<?php

namespace suframe\think;

use suframe\think\driver\DriverInterface;
use Swoole\Server as TcpServer;
use suframe\think\command\Server as ServerCommand;

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
        $swoole = config('swoole');
        //增加suframe interface
        if (!$swoole['rpc']['server']['enable'] === true) {
            return false;
        }

        $suframeService = config('suframeProxy.services');
        $swoole['rpc']['server']['services'] = array_merge($swoole['rpc']['server']['services'], $suframeService);
        config($swoole, 'swoole');
        //注册服务启动监听事件
        app()->event->listen('swoole.start', function () {
            //注册
            $driver = Service::getDirver();
            $config = config('suframeProxy');
            $driver->run($config);
        });
    }

    public function boot()
    {
        $this->commands(ServerCommand::class);
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

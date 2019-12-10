<?php

namespace suframe\think;

use Swoole\Server as TcpServer;
use suframe\think\command\Server as ServerCommand;
use think\facade\Event;


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
            file_put_contents(__DIR__ . '/test', 1111);
            $config = config('suframeProxy');
            $dirver = __NAMESPACE__ . '\\driver\\Driver' . ucfirst($config['driver']);
            if (class_exists($dirver)) {
                (new $dirver)->run($config);
            }
        });
    }

    public function boot()
    {
        $this->commands(ServerCommand::class);
    }

}

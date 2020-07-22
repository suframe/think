<?php

namespace suframe\think\command;

use suframe\think\Service;
use think\console\Command;

class Rpc extends Command
{
    public function configure()
    {
        $this->setName('rpc:register')
            ->setDescription('Swoole RPC Register');
    }

    public function handle()
    {
        //注册服务启动监听事件
        //注册
        $driver = Service::getDirver();
        $config = config('suframeProxy');
        $driver->register($config);
    }

}

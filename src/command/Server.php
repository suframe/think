<?php

namespace suframe\think\command;

use suframe\core\components\register\Client;
use think\console\input\Argument;
use think\helper\Arr;
use suframe\think\Swoole;

use think\console\Command;
/**
 * Swoole HTTP 命令行，支持操作：start|stop|reload}sync
 * 支持应用配置目录下的swoole.php文件进行参数配置
 */
class Server extends Command
{
    /**
     * The configs for this package.
     *
     * @var array
     */
    protected $config;

    public function configure()
    {
        $this->setName('swooleTcp')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|reload|sync", 'start')
            ->setDescription('Swoole TCP Server for ThinkPHP');
    }

    public function handle()
    {

    }

}

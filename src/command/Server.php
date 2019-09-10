<?php

namespace suframe\think\command;

use suframe\core\components\register\Client;
use think\console\input\Argument;
use think\helper\Arr;
use suframe\think\Swoole;

/**
 * Swoole HTTP 命令行，支持操作：start|stop|reload}sync
 * 支持应用配置目录下的swoole.php文件进行参数配置
 */
class Server extends \think\swoole\command\Server
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
        $this->checkEnvironment();
        $this->loadConfig();

        $action = $this->input->getArgument('action');

        if (in_array($action, ['start', 'stop', 'reload', 'sync'])) {
            $this->$action();
        } else {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|reload|sync .</error>");
        }
    }

    /**
     * 加载配置
     */
    protected function loadConfig()
    {
        $this->config = $this->app->config->get('swooleTcp');
    }

    /**
     * 启动server
     * @access protected
     * @return void
     */
    protected function start()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->output->writeln('<error>swoole tcp server process is already running.</error>');
            return;
        }

        $this->output->writeln('Starting swoole tcp server...');

        /** @var Swoole $swoole */
        $swoole = $this->app->make(Swoole::class);
        if (Arr::get($this->config, 'hot_update.enable', false)) {
            //热更新
            /** @var \Swoole\Server $server */
            $server = $this->app->make(\suframe\think\facade\Server::class);

            $server->addProcess($this->getHotUpdateProcess($server));
        }

        $host = $this->config['server']['host'];
        $port = $this->config['server']['port'];

        $this->output->writeln("Swoole tcp server started: <{$host}:{$port}>");
        $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        $swoole->run();
    }

    /**
     * 柔性重启server
     * @access protected
     * @return void
     */
    protected function reload()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole tcp server process running.</error>');
            return;
        }

        $this->output->writeln('Reloading swoole tcp server...');

        $isRunning = $this->killProcess($pid, SIGUSR1);

        if (!$isRunning) {
            $this->output->error('> failure');

            return;
        }

        $this->output->writeln('> success');
    }

    /**
     * 停止server
     * @access protected
     * @return void
     */
    protected function stop()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole tcp server process running.</error>');
            return;
        }

        $this->output->writeln('Stopping swoole tcp server...');

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->output->error('Unable to stop the swoole_tcp_server process.');
            return;
        }

        $this->removePid();

        $this->output->writeln('> success');
    }

    /**
     * 同步注释文件
     * @throws \Exception
     */
    protected function sync()
    {
        $out = $this->output;
        go(function () use ($out){
            try{
                $rs = Client::getInstance()->commandUpdateServers();
                $rs = $rs && Client::getInstance()->syncRpc();
                if($rs){
                    $out->writeln('success');
                } else {
                    $out->error('fail');
                }
            } catch (\Exception $e){
                $out->error('fail:' . $e->getMessage());
            }
        });
    }
}

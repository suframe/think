<?php
namespace suframe\think;

use Swoole\Server as TcpServer;
use think\App;
use suframe\think\command\Server as ServerCommand;
use suframe\think\facade\Server;

class Service extends \think\Service
{

    /**
     * @var TcpServer
     */
    protected static $server;

    public function register()
    {

        $this->app->bind(Server::class, function () {
            if (is_null(static::$server)) {
                $this->createSwooleServer();
            }

            return static::$server;
        });

        $this->app->bind('swooleTcp.server', Server::class);

        $this->app->bind(Swoole::class, function (App $app) {
            return new Swoole($app);
        });

        $this->app->bind('swooleTcp', Swoole::class);

        $this->app->bind(Proxy::class, function (App $app) {
            return new Proxy($app);
        });

    }

    public function boot()
    {
        $this->commands(ServerCommand::class);
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $server     = \Swoole\Server::class;
        $config     = $this->app->config;
        $host       = $config->get('swooleTcp.server.host');
        $port       = $config->get('swooleTcp.server.port');
        $socketType = $config->get('swooleTcp.server.socket_type', SWOOLE_SOCK_TCP);
        $mode       = $config->get('swooleTcp.server.mode', SWOOLE_PROCESS);
        static::$server = new $server($host, $port, $mode, $socketType);

        $options = $config->get('swooleTcp.server.options');

        static::$server->set($options);
    }
}

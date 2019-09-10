<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace suframe\think;

use Exception;
use suframe\core\components\Config;
use suframe\core\components\register\Client as RegisterClient;
use suframe\core\components\rpc\RpcUnPack;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process;
use Swoole\Runtime;
use Swoole\Server\Task;
use think\App;
use think\console\Output;
use think\exception\Handle;
use think\helper\Str;
use think\swoole\App as SwooleApp;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\InteractsWithWebsocket;
use suframe\think\facade\Server;
use Throwable;

/**
 * Class Manager
 */
class Swoole
{
    use InteractsWithSwooleTable, InteractsWithWebsocket;

    /**
     * @var App
     */
    protected $container;

    /**
     * @var SwooleApp
     */
    protected $app;

    /**
     * Server events.
     *
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'workerStart',
        'workerStop',
        'packet',
        'bufferFull',
        'bufferEmpty',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStart',
        'managerStop',
        'receive',
    ];

    /**
     * Manager constructor.
     * @param App $container
     */
    public function __construct(App $container)
    {
        $this->container = $container;
        $this->initialize();
    }

    /**
     * Run swoole server.
     */
    public function run()
    {
        $this->container->make(Server::class)->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        $this->container->make(Server::class)->shutdown();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->createTables();
        $this->setSwooleServerListeners();
    }

    /**
     * Set swoole server listeners.
     */
    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = Str::camel("on_$event");
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->container->event->trigger("swooleTcp.$event", func_get_args());
            };

            $this->container->make(Server::class)->on($event, $callback);
        }
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master process');
        $this->createPidFile();

        $this->container->event->trigger('swooleTcp.start', func_get_args());

        go(function () {
            //注册到summer-proxy
            $config = Config::getInstance();
            RegisterClient::getInstance()->register([
                'path' => $config->get('app.path'),
                'ip' => $config->get('server.host'),
                'port' => $config->get('server.port'),
            ]);
        });
    }

    /**
     * The listener of "managerStart" event.
     *
     * @return void
     */
    public function onManagerStart()
    {
        $this->setProcessName('manager process');
        $this->container->event->trigger('swooleTcp.managerStart', func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     *
     * @param \Swoole\Http\Server|mixed $server
     *
     * @throws Exception
     */
    public function onWorkerStart($server)
    {
        if ($this->container->config->get('swooleTcp.enable_coroutine', false)) {
            Runtime::enableCoroutine(true);
        }

        $this->clearCache();

        $this->container->event->trigger('swooleTcp.workerStart', func_get_args());

        // don't init app in task workers
        if ($server->taskworker) {
            $this->setProcessName('task process');

            return;
        }

        $this->setProcessName('worker process');

        $this->prepareApplication();

        if ($this->isServerWebsocket) {
            $this->prepareWebsocketHandler();
            $this->loadWebsocketRoutes();
        }
    }

    protected function prepareApplication()
    {
        if (!$this->app instanceof SwooleApp) {
            $this->app = new SwooleApp();
            $this->app->initialize();
        }

        $this->bindSandbox();
        $this->bindSwooleTable();

        if ($this->isServerWebsocket) {
            $this->bindRoom();
            $this->bindWebsocket();
        }
    }

    /**
     * "onRequest" listener.
     *
     * @param Request $req
     * @param Response $res
     */
    public function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {
        $this->app->event->trigger('swooleTcp.request');

        /** @var Sandbox $sandbox */
        $sandbox = $this->app->make(Sandbox::class);
        $pack = new RpcUnPack($data ?: []);
        try {
            $request = $pack->get();
            $sandbox->setRpcRequest($request);
            $sandbox->init();
            $response = $sandbox->runRpc($request);
            $rs = [
                'code' => 200,
                'data' => $response,
            ];
            $server->send($fd, json_encode($rs));
        } catch (Throwable $e) {
            try {
                $rs = [
                    'code' => 500,
                    'msg' => $e->getMessage(),
                    'data' => [],
                ];
                $server->send($fd, json_encode($rs));
                throw $e;
            } catch (Throwable $e) {
                $this->logServerError($e);
            }
        } finally {
            $sandbox->clear();
        }
    }

    /**
     * Set onTask listener.
     *
     * @param mixed $server
     * @param string|Task $taskId or $task
     * @param string $srcWorkerId
     * @param mixed $data
     */
    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        $this->container->event->trigger('swooleTcp.task', func_get_args());

        try {
            // push websocket message
            if ($this->isWebsocketPushPayload($data)) {
                $this->pushMessage($server, $data['data']);
                // push async task to queue
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Set onFinish listener.
     *
     * @param mixed $server
     * @param string $taskId
     * @param mixed $data
     */
    public function onFinish($server, $taskId, $data)
    {
        // task worker callback
        $this->container->event->trigger('swooleTcp.finish', func_get_args());

        return;
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();
    }

    /**
     * Bind sandbox to Laravel app container.
     */
    protected function bindSandbox()
    {
        $this->app->bind(Sandbox::class, function (App $app) {
            return new Sandbox($app);
        });

        $this->app->bind('swoole.sandbox', Sandbox::class);
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container->make('config')->get('swooleTcp.server.options.pid_file');
    }

    /**
     * Create pid file.
     */
    protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid = $this->container->make(Server::class)->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    protected function removePidFile()
    {
        $pidFile = $this->getPidFile();

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (extension_loaded('apc')) {
            apc_clear_cache();
        }

        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @codeCoverageIgnore
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        // Mac OSX不支持进程重命名
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }

        $serverName = 'swoole_tcp_server';
        $appName = $this->container->config->get('app.name', 'ThinkPHP');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);

        swoole_set_process_name($name);
    }

    /**
     * Add process to http server
     *
     * @param Process $process
     */
    public function addProcess(Process $process): void
    {
        $this->container->make(Server::class)->addProcess($process);
    }

    /**
     * Log server error.
     *
     * @param Throwable|Exception $e
     */
    public function logServerError(Throwable $e)
    {
        /** @var Handle $handle */
        $handle = $this->app->make(Handle::class);

        $handle->renderForConsole(new Output(), $e);

        $handle->report($e);
    }
}

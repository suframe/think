<?php

namespace suframe\think;

use RuntimeException;
use think\App;
use think\Config;
use think\Container;
use think\Event;
use think\Http;
use think\Request;
use think\Response;
use think\swoole\coroutine\Context;
use think\swoole\resetters\BindRequest;
use think\swoole\resetters\ClearInstances;
use think\swoole\resetters\RebindHttpContainer;
use think\swoole\resetters\RebindRouterContainer;
use think\swoole\resetters\ResetConfig;
use think\swoole\resetters\ResetDumper;
use think\swoole\resetters\ResetEvent;
use think\swoole\resetters\ResetterContract;

class Sandbox extends \think\swoole\Sandbox
{
    protected $rpcConfig = [];
    protected function initialize()
    {
        parent::initialize();
        $rpcConfig = $this->app->config->get('swooleTcp.rpcConfig');
        $this->rpcConfig = [
            'path' => $rpcConfig['path'] ?? $this->app->getAppPath() . 'rpc' . DIRECTORY_SEPARATOR,
            'ns' => "\\app\\rpc\\"
        ];
        return $this;
    }

    /**
     * @param array $request
     * @return string
     * @throws \Exception
     */
    public function runRpc($request)
    {
        if(!isset($request['path'])){
            throw new \Exception('rpc path not fount');
        }
        return $this->app->make(Proxy::class)->dispatch($request);
    }

    public function setRpcRequest($request)
    {
        Context::setData('_rcp_request', $request);

        return $this;
    }

    /**
     * Get current request.
     */
    public function getRpcRequest()
    {
        return Context::getData('_rpc_request');
    }
}

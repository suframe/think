<?php

namespace suframe\think;

use think\App;
use think\Container;

class Proxy
{
    /** @var App */
    protected $app;
    protected $rpcConfig = [];
    public function __construct($app = null)
    {
        if (!$app instanceof Container) {
            return;
        }

        $this->setBaseApp($app);
        $this->initialize();
    }

    public function setBaseApp(Container $app)
    {
        $this->app = $app;

        return $this;
    }

    public function getBaseApp()
    {
        return $this->app;
    }

    protected function initialize()
    {
        $rpcConfig = $this->app->config->get('swooleTcp.rpcConfig');
        $this->rpcConfig = [
            'path' => $rpcConfig['path'] ?? $this->app->getAppPath() . 'rpc' . DIRECTORY_SEPARATOR,
            'rpc' => "\\app\\rpc\\",
            'api' => "\\app\\api\\"
        ];
        return $this;
    }

    /**
     * 转发
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function dispatch($data){
        if(!isset($data['path'])){
            throw new \Exception('rpc path not fount');
        }

        $path = ltrim($data['path'], '/');
        $apiName = explode('/', $path);
        $isRpc = false;
        if ($apiName[0] === 'summer') {
            $nameSpace = '\suframe\think\api\\';
        } else if($apiName[0] === 'rpc') {
            $isRpc = true;
            $nameSpace = $this->rpcConfig['rpc'];
        } else {
            $nameSpace = $this->rpcConfig['api'];
        }
        array_shift($apiName);
        $className = array_pop($apiName);
        $className = ucfirst($className);
        $apiName[] = $className;
        $className = implode('\\', $apiName);
        $apiClass = $nameSpace . $className;

        if (class_exists($apiClass)) {
            $methodName = 'run';
        } else {
            $methodName = array_pop($apiName);
            $className = implode('\\', $apiName);
            $apiClass = $nameSpace . $className;
            if (!class_exists($apiClass)) {
                throw new \Exception('api class not found:' . $apiClass);
            }
        }

        $api = new $apiClass;
        if (!method_exists($api, $methodName)) {
            throw new \Exception('api method not found:' . $methodName);
        }
        if($isRpc){
            $arguments = $data['arguments'] ?? [];
            $rs = $api->$methodName(...$arguments);
            return $rs;
        }

        $rs = $api->$methodName($data);
        return $rs;
    }
}
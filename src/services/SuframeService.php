<?php

namespace suframe\think\services;

use suframe\think\Service;
use think\facade\Config;
use think\facade\Console;
use think\swoole\Manager;
use think\swoole\PidManager;

class SuframeService implements SuframeInterface
{

    /**
     * 注册
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function register($data): string
    {
        if (!$data) {
            return 'fail';
        }

        $clients = config('swoole.rpc.client');
        $path = $data['path'] ?? '';
        $host = $data['host'] ?? '';
        $port = $data['port'] ?? '';
        $name = $data['name'] ?? '';
        $rpcPort = $data['rpcPort'] ?? '';
        if (!$path || !$host || !$name || (!$port && !$rpcPort)) {
            return 'fail';
        }
        $post = [
            'path' => $path,
            'name' => $name,
            'host' => $host,
            'port' => $port,
            'rpcPort' => $rpcPort,
        ];
        /*if(isset($hosts[$name])){
            $hosts[$name][] = $post;
        } else {
            $hosts[$name] = [$post];
        }*/
        //目前好像只支持一个，已经反馈社区增加多个，增加负载算法
        // todo 带官方增加多个后修改,目前以最后一个为有效
        $clients[$name] = $post;
        $this->storeToFile($clients);

        go(function () use ($clients) {
            //重置swoole配置
            Config::load(app()->getConfigPath() . 'swoole.php', 'swoole');
            //生成接口
            Console::call('rpc:interface');
            $dirver = Service::getDirver();
            //自己不用通知了
            $name = config('suframeProxy.name');
            if (isset($clients[$name])) {
                unset($clients[$name]);
            }
            $dirver->notify($clients);
            //注册接口到第三方网关代理
            if (config('suframeProxy.apiGetway.enable')) {
                $dirver->registerApiGateway($clients);
            }
        });
        //执行
        return 'ok';
    }

    /**
     * 保存文件
     * @param $clients
     * @return bool
     */
    protected function storeToFile($clients)
    {
        $suframeRpcClient = config('suframeRpcClient', []);
        $suframeRpcClient = array_merge($clients, $suframeRpcClient);
        if (!$suframeRpcClient) {
            return false;
        }
        $suframeRpcClient = var_export($suframeRpcClient, true);
        $configPath = app()->getConfigPath() . 'suframeRpcClient.php';
        $content = <<<EOE
<?php
return {$suframeRpcClient};
EOE;
        $rs = file_put_contents($configPath, $content . PHP_EOL);
        if (!$rs) {
            return false;
        }
        return $rs;
    }

    /**
     * 通知更新
     * @param $clients
     * @return bool
     */
    public function notify($clients): bool
    {
        $rs = $this->storeToFile($clients);
        echo "new notify update clients " . ($rs ? 'success' : 'fail') . "\n";
        return $rs;
    }
}
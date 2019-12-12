<?php

namespace suframe\think\services;

use suframe\think\Service;
use think\exception\ErrorException;
use think\facade\Config;
use think\facade\Console;
use think\swoole\exception\RpcClientException;
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
            'apiPort' => $port,
            'port' => $rpcPort,
        ];
        /*if(isset($hosts[$name])){
            $hosts[$name][] = $post;
        } else {
            $hosts[$name] = [$post];
        }*/
        //目前好像只支持一个，已经反馈社区增加多个，增加负载算法
        // todo 带官方增加多个后修改,目前以最后一个为有效
        $clients[$name] = $post;
        $clients = $this->checkClients($clients);

        $dirver = Service::getDirver();
        //自己不用通知了
        $name = config('suframeProxy.name');
        if (isset($clients[$name])) {
            unset($clients[$name]);
        }
        if (!$clients) {
            return 'ok';
        }
        $clients = $dirver->notify($clients);
        //注册接口到第三方网关代理
        if (config('suframeProxy.apiGetway.enable')) {
            $dirver->registerApiGateway($clients);
        }
        //生成接口
        Console::call('rpc:interface');
        //执行
        return 'ok';
    }

    public function checkClients($clients)
    {
        $name = config('suframeProxy.name');
        $interface = "\\rpc\\contract\\{$name}\\SuframeInterface";
        if (!interface_exists($interface)) {
            //引入rpc接口文件
            if (file_exists($rpc = app()->getBasePath() . 'rpc.php')) {
                include_once $rpc;
            }
        }

        //clients 检测是否有效,否则后面生成接口会报错
        foreach ($clients as $name => $client) {
            $swooleClient = new \Swoole\Client(SWOOLE_SOCK_TCP);
            try {
                if (!$swooleClient->connect($client['host'], $client['port'], 0.5)) {
                    unset($clients[$name]);
                }
            } catch (\Exception $e) {

                unset($clients[$name]);
            }
        }
        static::storeToFile($clients);
        return $clients;
    }

    /**
     * 保存文件
     * @param $clients
     * @return bool
     */
    public static function storeToFile($clients)
    {
        $clients = $clients ?: [];
        $suframeRpcClient = var_export($clients, true);
        $configPath = app()->getConfigPath() . 'suframeRpcClient.php';
        $content = <<<EOE
<?php
return {$suframeRpcClient};
EOE;
        $rs = file_put_contents($configPath, $content . PHP_EOL);
        if (!$rs) {
            return false;
        }
        //重置swoole配置
        Config::load(app()->getConfigPath() . 'swoole.php', 'swoole');
        return $rs;
    }

    /**
     * 通知更新
     * @param $clients
     * @return bool
     */
    public function notify($clients): bool
    {
        $rs = static::storeToFile($clients);
        echo "new notify update clients " . ($rs ? 'success' : 'fail') . "\n";
        return $rs;
    }
}
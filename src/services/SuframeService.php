<?php

namespace suframe\think\services;

use suframe\think\Service;
use think\facade\Config;
use think\facade\Console;

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

        $configPath = app()->getConfigPath() . 'suframeRpcClient.php';
        $file = fopen($configPath, 'r+');
        if (flock($file, LOCK_EX)) {
            $clients = include($configPath);
            $clients = $clients ?: [];
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
            //目前好像只支持一个，已经反馈社区增加多个，增加负载算法
            // todo 带官方增加多个后修改,目前以最后一个为有效
            $clients[$name] = $post;
            $clients = $this->checkClients($clients);
            $dirver = Service::getDirver();
            $dirver->notify($clients);
            //注册接口到第三方网关代理
            if (config('suframeProxy.apiGetway.enable')) {
                $dirver->registerApiGateway($clients);
            }

            flock($file, LOCK_UN);
        } else {
            echo "no lock\n";
            \co::sleep(1);
        }
        fclose($file);

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
        Config::load(app()->getConfigPath() . 'swoole.php', 'swoole');
        return $rs;
    }

    /**
     * 通知更新
     * @param $data
     * @return bool
     */
    public function notify($data): string
    {
        $rs = static::storeToFile($data);
        echo "- update local clients " . ($rs ? 'success' : 'fail') . "\n";
        //生成接口
        Console::call('rpc:interface');
        return $rs ? 'success' : 'fail';
    }
}
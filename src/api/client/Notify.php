<?php
namespace suframe\think\api\client;

use suframe\core\components\register\Client as ClientAlias;
use suframe\core\components\swoole\ProcessTools;

class Notify
{

    public function run($params){
        go(function () use ($params){
            $command = $params['command'] ?? '';

            if(!$command || !method_exists($this, $command)){
                return false;
            }
            return $this->$command($params);
        });
        return true;
    }

    /**
     * @throws \Exception
     */
    protected function updateServers(){
        $rs = ClientAlias::getInstance()->commandUpdateServers();
        //重启服务
        ProcessTools::kill();
        echo 'restart success', "\n";
        return $rs;
    }

}
<?php
/**
 * +----------------------------------------------------------------------
 * | 九正科技实业有限公司
 * +----------------------------------------------------------------------
 * | Copyright (c) 2017 http://www.jc001.cn All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: 钱进 <330576744@qq.com>  2019/12/10 15:26
 * +----------------------------------------------------------------------
 */

namespace suframe\think\services;


use think\facade\Cache;

class SuframeService implements SuframeInterface
{

    protected $cacheKey = 'suframe.hosts';

    public function notify($data)
    {
        // TODO: Implement notify() method.
        return 'ok';
    }

    /**
     * 注册
     */
    public function register($data)
    {
        if(!$data){
            return 'fail';
        }
        //缓存
        $hosts = cache($this->cacheKey);
        $path = $data['path'] ?? '';
        $host = $data['host'] ?? '';
        $port = $data['port'] ?? '';
        $rpcPort = $data['rpcPort'] ?? '';
        if(!$path || !$host || (!$port && !$rpcPort)){
            return 'fail';
        }

        if(isset($hosts[$path])){
            $hosts[$path][] = $data;
        } else {
            $hosts[$path] = [$data];
        }
        cache($this->cacheKey, $hosts);
        return 'ok';
    }
}
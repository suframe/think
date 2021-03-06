<?php

namespace suframe\think\middlewares;

use think\Exception;
use think\file\UploadedFile;
use think\Request;
use think\Response;

class Gateway
{

    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed|Response
     * @throws Exception
     */
    public function handle($request, \Closure $next)
    {
        $pathInfo = $request->pathinfo();
        if (strpos($pathInfo, 'gateway/') === 0) {
            return $next($request);
        }

        //代理网关
        $clients = config('suframeRpcClient');
        if (!$clients) {
            throw new Exception('api not found:' . $pathInfo);
        }
        $route = explode('/', $pathInfo);
        if (!$name = $request->header('--app-id--')) {
            if (!$pathInfo) {
                return $next($request);
            }
            $name = array_shift($route);
        }

        $clientConfig = $clients[$name] ?? null;
        if (!$clientConfig) {
            throw new Exception('api name not found:' . $name);
        }
        $route = implode('/', $route);
        $route = '/' . $route;

        $authClass = "\\app\\auth\\" . ucfirst($name);
        $uid = 0;
        $param = $request->param();
        if (class_exists($authClass)) {
            $auth = $authClass::getInstance();
            $rs = $auth->handle($uid, $route, $param, $request, $next);
            if ($rs) {
                return $rs;
            }
        }
        $client = new \Swoole\Coroutine\Http\Client($clientConfig['host'], $clientConfig['apiPort']);
        $header = $request->header();
        $header['--request-id--'] = session_create_id();
        if ($uid) {
            $header['--uid--'] = $uid;
        }
        $client->setHeaders($header);
        $client->set(['timeout' => 1]);
        $client->setMethod($request->method());

        if ($request->isGet()) {
            if ($param) {
                $param = http_build_query($param);
                $route .= '?' . $param;
            }
//            trace('GATEWAY ' . $name .  ' GET ' . $route . ' ' . date('Y-m-d H:i:s'));
            $client->get($route);
        } else {
            if ($files = $request->file()) {
                /** @var UploadedFile $file */
                foreach ($files as $file) {
                    $client->addFile($file->getRealPath(), $file->getOriginalName(), $file->getType(), $file->getFilename());
                }
            }
            $client->post($route, $param);
//            trace('GATEWAY ' . $name .  ' POST ' . $route . ' ' . date('Y-m-d H:i:s') . ' data: ' . json_encode($param, JSON_UNESCAPED_UNICODE));
        }

        $rs = $client->body;
        $client->close();
        return response($rs);
    }

    protected function response($data)
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        return response($data);
    }

}
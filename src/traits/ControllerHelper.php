<?php

namespace suframe\think\traits;

/**
 * Trait ControllerHelper
 * @package suframe\think\traits
 * @property \think\Request $request
 */
trait ControllerHelper
{

    protected function getRpcExtParams($params = [])
    {
        if ($this->request->header('--request_id--')) {
            $params['--request_id--'] = $params;
        }
        return $params;
    }

}
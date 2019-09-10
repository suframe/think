<?php

namespace suframe\think\facade;

use think\Facade;

class Server extends Facade
{
    protected static function getFacadeClass()
    {
        return 'swooleTcp.server';
    }
}

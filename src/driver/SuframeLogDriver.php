<?php

namespace suframe\think\driver;


class SuframeLogDriver extends \think\log\driver\File
{

    public function save(array $log): bool
    {
        var_dump($log);
        return true;
    }

}
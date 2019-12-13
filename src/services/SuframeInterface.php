<?php

namespace suframe\think\services;


interface SuframeInterface
{

    /**
     * 注册
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function register($data): string;

    /**
     * 通知更新
     * @param $data
     * @return bool
     */
    public function notify($data): bool;

}
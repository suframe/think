<?php

namespace suframe\think\driver;


interface DriverInterface
{

    public function run(array $config): bool;

    public function register(array $data): bool;

}
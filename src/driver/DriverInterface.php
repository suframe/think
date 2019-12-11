<?php

namespace suframe\think\driver;


interface DriverInterface
{

    public function registerApiGateway(array $config): bool;

    public function register(array $config): bool;

    public function notify(array $clientss = [], array $data = []): bool;

}
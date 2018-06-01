<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class DelegatorServiceFactory1
{

    public function __invoke(ContainerInterface $container, $name, callable $callback): ServiceInterface
    {
        $service = $callback();
        $service->inject(static::class);

        return $service;
    }
}

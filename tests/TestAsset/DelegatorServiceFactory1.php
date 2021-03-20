<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class DelegatorServiceFactory1
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ServiceInterface
    {
        $service = $callback();
        $service->inject(static::class);

        return $service;
    }
}

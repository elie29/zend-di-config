<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class DelegatorServiceFactory
{

    public function __invoke(ContainerInterface $container, $name, callable $callback): DelegatorService
    {
        return new DelegatorService($callback());
    }
}

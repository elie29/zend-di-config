<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class DelegatorServiceFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback): DelegatorService
    {
        return new DelegatorService($callback());
    }
}

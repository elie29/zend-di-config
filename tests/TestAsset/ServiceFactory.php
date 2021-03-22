<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container): ServiceInterface
    {
        return new Service();
    }
}

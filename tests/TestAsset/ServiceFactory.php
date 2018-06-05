<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class ServiceFactory
{

    public function __invoke(ContainerInterface $container)
    {
        return new Service();
    }
}

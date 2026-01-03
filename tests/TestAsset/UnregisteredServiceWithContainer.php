<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class UnregisteredServiceWithContainer
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}

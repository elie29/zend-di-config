<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

class InvokableWithContainerDependency
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

    public function hasService(string $name): bool
    {
        return $this->container->has($name);
    }
}

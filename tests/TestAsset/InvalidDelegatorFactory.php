<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;
use Exception;

class InvalidDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback)
    {
        throw new Exception('Delegator error: This delegator always fails.');
    }
} 
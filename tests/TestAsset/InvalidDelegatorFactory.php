<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Exception;
use Psr\Container\ContainerInterface;

class InvalidDelegatorFactory
{
    /**
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback)
    {
        throw new Exception('Delegator error: This delegator always fails.');
    }
}

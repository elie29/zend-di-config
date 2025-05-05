<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;
use Exception;

class InvalidServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        throw new Exception('Factory error: This factory always fails.');
    }
} 
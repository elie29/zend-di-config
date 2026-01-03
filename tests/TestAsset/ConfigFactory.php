<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

/**
 * Factory that returns scalar values or arrays.
 */
class ConfigFactory
{
    public function __invoke(ContainerInterface $container): array
    {
        return [
            'db_host' => 'localhost',
            'db_port' => 3306,
            'db_name' => 'mydb',
        ];
    }
}

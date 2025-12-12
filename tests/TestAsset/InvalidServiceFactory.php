<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Exception;
use Psr\Container\ContainerInterface;

class InvalidServiceFactory
{
    /**
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container)
    {
        throw new Exception('Factory error: This factory always fails.');
    }
}

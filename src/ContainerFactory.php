<?php

declare(strict_types=1);

namespace Elie\PHPDI\Config;

use DI\ContainerBuilder;
use Exception;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    /**
     * @throws Exception
     */
    public function __invoke(ConfigInterface $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config->configureContainer($builder);

        return $builder->build();
    }
}

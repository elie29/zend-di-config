<?php

declare(strict_types = 1);

namespace Elie\PHPDI\Config;

use DI\ContainerBuilder;
use Elie\PHPDI\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

class ContainerFactory
{

    public function __invoke(ConfigInterface $config): ContainerInterface
    {
        $builder = new ContainerBuilder(ContainerWrapper::class);
        $config->configureContainer($builder);

        return $builder->build();
    }
}

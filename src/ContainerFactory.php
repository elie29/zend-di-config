<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;
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

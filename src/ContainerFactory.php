<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Zend\DI\Config\ConfigInterface;

class ContainerFactory
{

    public function __invoke(ConfigInterface $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config->configureContainer($builder);

        return $builder->build();
    }
}

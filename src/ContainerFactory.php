<?php

declare(strict_types = 1);

namespace Zend\PHPDI\Config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Zend\PHPDI\Config\ConfigInterface;

class ContainerFactory
{

    public function __invoke(ConfigInterface $config): ContainerInterface
    {
        $builder = new ContainerBuilder(ContainerWrapper::class);
        $config->configureContainer($builder);

        return $builder->build();
    }
}

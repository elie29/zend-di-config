<?php

declare(strict_types=1);

namespace Zend\DI\Config;

use DI\Container;
use DI\ContainerBuilder;
use Zend\DI\Config\ConfigInterface;

class ContainerFactory
{
    public function __invoke(ConfigInterface $config) : Container
    {
        $builder = new ContainerBuilder();
        $config->configureContainer($builder);

        return $builder->build();
    }
}

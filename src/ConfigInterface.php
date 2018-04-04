<?php

declare(strict_types=1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;

interface ConfigInterface
{
    const DI_CACHE_PATH = 'di_cache_path';
    const DI_CACHE_DEFINITION = 'di_cache_definition';

    public function configureContainer(ContainerBuilder $builder): void;
}

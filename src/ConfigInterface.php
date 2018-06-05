<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;

interface ConfigInterface
{

    const CONFIG = 'config';
    const DI_CACHE_PATH = 'di_cache_path';
    const ENABLE_CACHE_DEFINITION = 'enable_cache_definition';

    public function configureContainer(ContainerBuilder $builder): void;
}

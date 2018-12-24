<?php

declare(strict_types = 1);

namespace Zend\DI\Config;

use DI\ContainerBuilder;

interface ConfigInterface
{

    public const CONFIG = 'config';
    public const DI_CACHE_PATH = 'di_cache_path';
    public const ENABLE_CACHE_DEFINITION = 'enable_cache_definition';
    public const USE_AUTOWIRE = 'use_autowire';

    public function configureContainer(ContainerBuilder $builder): void;
}

<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

/**
 * Factory that uses container configuration to customize service.
 */
class ConfigurableServiceFactory
{
    public function __invoke(ContainerInterface $container): Service
    {
        $service = new Service();
        
        // Get configuration from container if available
        if ($container->has('config')) {
            $config = $container->get('config');
            if (isset($config['service_name'])) {
                $service->inject($config['service_name']);
            }
        }
        
        return $service;
    }
}

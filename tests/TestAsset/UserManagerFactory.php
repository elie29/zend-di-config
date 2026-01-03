<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

/**
 * Factory that creates UserManager with dependencies from the container.
 */
class UserManagerFactory
{
    public function __invoke(ContainerInterface $container): UserManager
    {
        return new UserManager($container->get(Mailer::class));
    }
}

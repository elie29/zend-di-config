<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Psr\Container\ContainerInterface;

/**
 * Factory that returns a Mailer instance.
 */
class MailerFactory
{
    public function __invoke(ContainerInterface $container): Mailer
    {
        return new Mailer();
    }
}

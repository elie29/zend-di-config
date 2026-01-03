<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class UnregisteredService
{
    public function getName(): string
    {
        return 'UnregisteredService';
    }
}

<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class InvokableWithoutConstructor
{
    public function getName(): string
    {
        return 'InvokableWithoutConstructor';
    }
}

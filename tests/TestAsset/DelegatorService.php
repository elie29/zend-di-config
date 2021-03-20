<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class DelegatorService
{
    public function __construct(public ServiceInterface $service)
    {
    }
}

<?php

declare(strict_types = 1);

namespace ElieTest\PHPDI\Config\TestAsset;

class DelegatorService
{

    public $service;

    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }
}

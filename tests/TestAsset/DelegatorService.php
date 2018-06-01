<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

class DelegatorService
{

    public $service;

    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }
}

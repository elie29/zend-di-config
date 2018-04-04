<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

class Service implements ServiceInterface
{

    protected $time;

    public function __construct()
    {
        $this->time = microtime(true);
    }

    public function getTime(): string
    {
        return '' . $this->time;
    }
}

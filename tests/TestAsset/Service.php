<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use function microtime;

class Service implements ServiceInterface
{
    protected float $time;

    protected array $injected = [];

    public function __construct()
    {
        $this->time = microtime(true);
    }

    public function getTime(): string
    {
        return '' . $this->time;
    }

    public function inject(string $name): void
    {
        $this->injected[] = $name;
    }

    public function getInjected(): array
    {
        return $this->injected;
    }
}

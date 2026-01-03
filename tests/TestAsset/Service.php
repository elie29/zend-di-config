<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

use Random\RandomException;

class Service implements ServiceInterface
{
    protected string $uniqid;

    protected array $injected = [];

    /**
     * @throws RandomException
     */
    public function __construct()
    {
        $this->uniqid = uniqid(bin2hex(random_bytes(8)), true);
    }

    public function getUnique(): string
    {
        return $this->uniqid;
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

<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class InvokableWithOptionalParameters
{
    private string $name;
    private int $count;

    public function __construct(string $name = 'default', int $count = 0)
    {
        $this->name = $name;
        $this->count = $count;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}

<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class InvokableWithEmptyConstructor
{
    private string $id;

    public function __construct()
    {
        $this->id = uniqid('invokable_', true);
    }

    public function getId(): string
    {
        return $this->id;
    }
}

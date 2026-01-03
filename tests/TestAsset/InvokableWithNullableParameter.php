<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class InvokableWithNullableParameter
{
    private ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function hasValue(): bool
    {
        return $this->value !== null;
    }
}

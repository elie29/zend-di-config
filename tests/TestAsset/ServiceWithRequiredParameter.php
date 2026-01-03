<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

/**
 * This class should NOT be used as an invokable since it has required parameters.
 * It's used for negative testing.
 */
class ServiceWithRequiredParameter
{
    private string $requiredValue;

    public function __construct(string $requiredValue)
    {
        $this->requiredValue = $requiredValue;
    }

    public function getRequiredValue(): string
    {
        return $this->requiredValue;
    }
}

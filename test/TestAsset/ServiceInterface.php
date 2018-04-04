<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

interface ServiceInterface
{

    public function getTime(): string;

    public function inject(string $name): void;

    public function getInjected(): array;
}

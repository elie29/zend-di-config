<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class Mailer
{
    public function mail(string $recipient, string $content): bool
    {
        return true;
    }
}

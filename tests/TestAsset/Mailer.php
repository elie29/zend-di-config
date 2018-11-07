<?php

declare(strict_types = 1);

namespace ElieTest\PHPDI\Config\TestAsset;

class Mailer
{

    public function mail($recipient, $content): bool
    {
        return true;
    }
}

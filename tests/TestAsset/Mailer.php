<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

class Mailer
{

    public function mail($recipient, $content): bool
    {
        return true;
    }
}

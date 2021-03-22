<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class UserManager
{
    public function __construct(private Mailer $mailer)
    {
    }

    public function register(string $email): void
    {
        $this->mailer->mail($email, 'Hello and welcome!');
    }
}

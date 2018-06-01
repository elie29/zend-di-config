<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\TestAsset;

class UserManager
{

    private $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function register($email, $password)
    {
        $this->mailer->mail($email, 'Hello and welcome!');
    }
}

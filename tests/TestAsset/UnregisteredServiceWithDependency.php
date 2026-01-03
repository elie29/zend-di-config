<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\TestAsset;

class UnregisteredServiceWithDependency
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): Mailer
    {
        return $this->mailer;
    }
}

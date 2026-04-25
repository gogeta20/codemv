<?php

namespace App\Identity\Application\PasswordRecovery\Request;

final readonly class RequestPasswordRecoveryCommand
{
    public function __construct(
        public string $email,
    ) {
    }
}

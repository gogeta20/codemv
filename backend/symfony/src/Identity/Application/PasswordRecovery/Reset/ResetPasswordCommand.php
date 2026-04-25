<?php

namespace App\Identity\Application\PasswordRecovery\Reset;

final readonly class ResetPasswordCommand
{
    public function __construct(
        public string $token,
        public string $newPassword,
    ) {
    }
}

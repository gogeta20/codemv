<?php

namespace App\Identity\Application\PasswordRecovery\ListTokens;

final readonly class ListTokensQuery
{
    public function __construct(
        public bool $showAll = false,
    ) {
    }
}

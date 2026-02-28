<?php

namespace App\ActiveDirectory\Application\User\GetUser;

final readonly class GetUserQuery
{
    public function __construct(
        public string $samAccountName,
    ) {}
}

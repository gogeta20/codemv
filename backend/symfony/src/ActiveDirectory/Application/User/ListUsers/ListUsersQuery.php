<?php

namespace App\ActiveDirectory\Application\User\ListUsers;

final readonly class ListUsersQuery
{
    public function __construct(
        public string $organizationCode,
    ) {}
}

<?php

namespace App\ActiveDirectory\Application\User\SearchUsers;

final readonly class SearchUsersQuery
{
    public function __construct(
        public string $query,
    ) {}
}

<?php

namespace App\ActiveDirectory\Application\Organization\SearchOrganizations;

final readonly class SearchOrganizationsQuery
{
    public function __construct(
        public string $query,
    ) {}
}

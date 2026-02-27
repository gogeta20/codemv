<?php

namespace App\ActiveDirectory\Application\Organization\GetOrganization;

final readonly class GetOrganizationQuery
{
    public function __construct(
        public string $code,
    ) {}
}

<?php

namespace App\ActiveDirectory\Domain\Repository;

use App\ActiveDirectory\Domain\ValueObject\OrganizationCode;

interface OrganizationRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function findAll(): array;

    /** @return array<string, mixed>|null */
    public function findByCodeWithFullData(OrganizationCode $code): ?array;

    /** @return array<int, array<string, mixed>> */
    public function search(string $query): array;
}

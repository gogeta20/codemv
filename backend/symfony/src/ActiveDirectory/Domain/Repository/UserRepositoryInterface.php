<?php

namespace App\ActiveDirectory\Domain\Repository;

interface UserRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findBySamAccountName(string $samAccountName): ?array;

    /** @return array<int, array<string, mixed>> */
    public function findByOrganization(string $organizationCode): array;

    /** @return array<int, array<string, mixed>> */
    public function search(string $query): array;
}

<?php

namespace App\Identity\Domain\Repository;

interface OutboxRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function findRecent(int $limit): array;
}

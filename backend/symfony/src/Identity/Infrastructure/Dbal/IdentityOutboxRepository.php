<?php

namespace App\Identity\Infrastructure\Dbal;

use App\Identity\Domain\Repository\OutboxRepositoryInterface;
use Doctrine\DBAL\Connection;

final class IdentityOutboxRepository implements OutboxRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function findRecent(int $limit): array
    {
        $sql = <<<SQL
            SELECT id, event_id, event_key, source_service, aggregate_type, aggregate_id,
                   status, occurred_at, created_at, published_at, attempts, last_error
            FROM public.outbox
            ORDER BY occurred_at DESC
            LIMIT :limit
        SQL;

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, ['limit' => $limit]);

        return $rows;
    }
}

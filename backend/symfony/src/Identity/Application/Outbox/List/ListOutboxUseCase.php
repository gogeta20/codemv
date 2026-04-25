<?php

namespace App\Identity\Application\Outbox\List;

use App\Identity\Domain\Repository\OutboxRepositoryInterface;

final class ListOutboxUseCase
{
    public function __construct(
        private readonly OutboxRepositoryInterface $repository,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function execute(ListOutboxQuery $query): array
    {
        return $this->repository->findRecent($query->limit);
    }
}

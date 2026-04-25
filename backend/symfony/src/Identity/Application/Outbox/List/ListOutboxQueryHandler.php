<?php

namespace App\Identity\Application\Outbox\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListOutboxQueryHandler
{
    public function __construct(
        private readonly ListOutboxUseCase $useCase,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function __invoke(ListOutboxQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}

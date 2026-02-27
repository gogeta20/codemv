<?php

namespace App\Study\Application\List;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListStudiesQueryHandler
{
    public function __construct(
        private readonly ListStudiesUseCase $useCase,
    ) {}

    public function __invoke(ListStudiesQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}

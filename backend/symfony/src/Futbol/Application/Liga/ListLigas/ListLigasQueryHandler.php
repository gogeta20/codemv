<?php

namespace App\Futbol\Application\Liga\ListLigas;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListLigasQueryHandler
{
    public function __construct(private readonly ListLigasUseCase $useCase) {}

    public function __invoke(ListLigasQuery $query): array
    {
        return $this->useCase->execute();
    }
}

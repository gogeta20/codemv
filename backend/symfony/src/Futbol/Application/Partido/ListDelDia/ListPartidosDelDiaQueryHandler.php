<?php

namespace App\Futbol\Application\Partido\ListDelDia;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListPartidosDelDiaQueryHandler
{
    public function __construct(
        private readonly ListPartidosDelDiaUseCase $useCase,
    ) {}

    public function __invoke(ListPartidosDelDiaQuery $query): array
    {
        return $this->useCase->execute($query);
    }
}

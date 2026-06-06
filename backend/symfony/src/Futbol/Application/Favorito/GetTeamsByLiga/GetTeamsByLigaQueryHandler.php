<?php

namespace App\Futbol\Application\Favorito\GetTeamsByLiga;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTeamsByLigaQueryHandler
{
    public function __construct(private readonly GetTeamsByLigaUseCase $useCase) {}

    public function __invoke(GetTeamsByLigaQuery $query): array
    {
        return $this->useCase->execute($query->ligaCode);
    }
}

<?php

namespace App\Futbol\Application\Equipo\GetAnalisis;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEquipoAnalisisQueryHandler
{
    public function __construct(private readonly GetEquipoAnalisisUseCase $useCase) {}

    public function __invoke(GetEquipoAnalisisQuery $query): array
    {
        return $this->useCase->execute($query->teamId, $query->liga, $query->season);
    }
}
